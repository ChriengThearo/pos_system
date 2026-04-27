import argparse
import json
import os
import sys
import time
from dataclasses import dataclass
from datetime import datetime
from pathlib import Path
from typing import Dict, List
from urllib import parse, request

try:
    import oracledb
except ModuleNotFoundError:
    print("Missing Python package 'oracledb'. Install with: python -m pip install -r python/telegram/requirements.txt", file=sys.stderr)
    sys.exit(1)


SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = SCRIPT_DIR.parent.parent
DEFAULT_ENV_FILE = PROJECT_ROOT / ".env"
DEFAULT_STATE_FILE = SCRIPT_DIR / ".stock_alert_state.json"


@dataclass
class AlertRow:
    product_no: str
    product_name: str
    qty_on_hand: float
    lower_qty: float
    higher_qty: float
    status_group: str


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Monitor product stock alerts and send Telegram notifications.")
    parser.add_argument("--env-file", default=str(DEFAULT_ENV_FILE), help="Path to .env file (default: project .env).")
    parser.add_argument(
        "--state-file",
        default=str(DEFAULT_STATE_FILE),
        help="Deprecated and ignored (kept for backward compatibility).",
    )
    parser.add_argument("--interval", type=int, default=0, help="Polling interval in seconds. 0 = use env/default.")
    parser.add_argument("--once", action="store_true", help="Run one check and exit.")
    parser.add_argument("--dry-run", action="store_true", help="Do not send message to Telegram, only print result.")
    parser.add_argument("--discover-chat-id", action="store_true", help="List available chat IDs from bot updates.")
    return parser.parse_args()


def load_env_file(path: Path) -> None:
    if not path.is_file():
        return

    for line in path.read_text(encoding="utf-8").splitlines():
        stripped = line.strip()
        if not stripped or stripped.startswith("#") or "=" not in stripped:
            continue

        key, raw_value = stripped.split("=", 1)
        key = key.strip()
        if key == "":
            continue

        value = parse_env_value(raw_value.strip())
        os.environ.setdefault(key, value)


def parse_env_value(raw: str) -> str:
    if raw == "":
        return ""

    if raw[0] in ("'", '"'):
        quote = raw[0]
        chars: List[str] = []
        escaped = False
        for ch in raw[1:]:
            if escaped:
                chars.append(ch)
                escaped = False
                continue
            if ch == "\\":
                escaped = True
                continue
            if ch == quote:
                break
            chars.append(ch)
        return "".join(chars).strip()

    comment_index = raw.find(" #")
    if comment_index >= 0:
        raw = raw[:comment_index]
    return raw.strip()


def env_bool(name: str, default: bool) -> bool:
    value = os.getenv(name)
    if value is None:
        return default
    normalized = value.strip().lower()
    return normalized in {"1", "true", "yes", "on"}


def env_int(name: str, default: int) -> int:
    value = os.getenv(name)
    if value is None or value.strip() == "":
        return default
    try:
        return int(value)
    except ValueError:
        return default


def env_choice(name: str, default: str, allowed: set[str]) -> str:
    value = os.getenv(name, "").strip().lower()
    if value in allowed:
        return value
    return default


def env_required(name: str) -> str:
    value = os.getenv(name, "").strip()
    if value == "":
        raise RuntimeError(f"Missing required environment variable: {name}")
    return value


def build_dsn() -> str:
    host = env_required("DB_HOST")
    port = os.getenv("DB_PORT", "1521").strip() or "1521"
    service = (
        os.getenv("DB_SERVICE_NAME", "").strip()
        or os.getenv("DB_SERVICE", "").strip()
        or os.getenv("DB_DATABASE", "").strip()
    )
    if service == "":
        raise RuntimeError("Missing DB service name. Set DB_SERVICE_NAME (or DB_SERVICE/DB_DATABASE).")
    return f"{host}:{port}/{service}"


def fetch_alert_rows() -> List[AlertRow]:
    user = env_required("DB_USERNAME")
    password = env_required("DB_PASSWORD")
    dsn = build_dsn()

    sql = """
        SELECT
            p.PRODUCT_NO,
            p.PRODUCT_NAME,
            NVL(p.QTY_ON_HAND, 0) AS QTY_ON_HAND,
            NVL(a.LOWER_QTY, 0) AS LOWER_QTY,
            NVL(a.HIGHER_QTY, 0) AS HIGHER_QTY,
            'UNDERSTOCK' AS STATUS_GROUP
        FROM PRODUCTS p
        LEFT JOIN ALERT_STOCKS a ON a.PRODUCT_NO = p.PRODUCT_NO
        WHERE UPPER(NVL(p.STATUS, 'UNKNOWN')) = 'UNDERSTOCK'
        ORDER BY p.PRODUCT_NAME, p.PRODUCT_NO
    """

    rows: List[AlertRow] = []
    connection = oracledb.connect(user=user, password=password, dsn=dsn)
    try:
        with connection.cursor() as cursor:
            cursor.execute(sql)
            for product_no, product_name, qty_on_hand, lower_qty, higher_qty, status_group in cursor:
                rows.append(
                    AlertRow(
                        product_no=str(product_no or "").strip(),
                        product_name=str(product_name or "").strip(),
                        qty_on_hand=float(qty_on_hand or 0),
                        lower_qty=float(lower_qty or 0),
                        higher_qty=float(higher_qty or 0),
                        status_group=str(status_group or "ENOUGH").strip().upper(),
                    )
                )
    finally:
        connection.close()

    return rows


def discover_chat_ids(token: str) -> List[Dict[str, str]]:
    url = f"https://api.telegram.org/bot{token}/getUpdates?timeout=5"
    payload = http_get_json(url)
    updates = payload.get("result", [])
    found: Dict[str, Dict[str, str]] = {}

    for item in updates:
        for key in ("message", "edited_message", "channel_post", "edited_channel_post"):
            message = item.get(key)
            if not isinstance(message, dict):
                continue
            chat = message.get("chat", {})
            chat_id = str(chat.get("id", "")).strip()
            if chat_id == "":
                continue
            title = (
                str(chat.get("title", "")).strip()
                or str(chat.get("username", "")).strip()
                or (str(chat.get("first_name", "")).strip() + " " + str(chat.get("last_name", "")).strip()).strip()
                or "unknown"
            )
            chat_type = str(chat.get("type", "")).strip() or "unknown"
            found[chat_id] = {"chat_id": chat_id, "title": title, "type": chat_type}

    return list(found.values())


def format_number(value: float) -> str:
    if value.is_integer():
        return str(int(value))
    return f"{value:.2f}".rstrip("0").rstrip(".")


def build_message(alert_name: str, rows: List[AlertRow], max_items: int) -> str:
    _ = alert_name  # kept for compatibility with existing call sites
    lines = [f"Stock Alert: {len(rows)} products are running low on stock."]

    if not rows:
        return "\n".join(lines)

    lines.append("")
    limited_rows = rows[:max_items]
    for index, row in enumerate(limited_rows, start=1):
        lines.append(f"{index}. {row.product_name} = {format_number(row.qty_on_hand)}")
    if len(rows) > len(limited_rows):
        lines.append(f"... and {len(rows) - len(limited_rows)} more item(s)")
    return "\n".join(lines)


def send_telegram_message(token: str, chat_id: str, text: str) -> None:
    if len(text) > 3900:
        text = text[:3900] + "\n... message truncated"
    url = f"https://api.telegram.org/bot{token}/sendMessage"
    body = parse.urlencode(
        {
            "chat_id": chat_id,
            "text": text,
            "disable_web_page_preview": "true",
        }
    ).encode("utf-8")
    payload = http_post_json(url, body)
    if not payload.get("ok"):
        raise RuntimeError(f"Telegram API responded with error: {payload}")


def http_get_json(url: str) -> Dict[str, object]:
    req = request.Request(url, method="GET")
    with request.urlopen(req, timeout=20) as response:
        content = response.read().decode("utf-8")
    return json.loads(content)


def http_post_json(url: str, body: bytes) -> Dict[str, object]:
    req = request.Request(url, data=body, method="POST")
    with request.urlopen(req, timeout=20) as response:
        content = response.read().decode("utf-8")
    return json.loads(content)


def run_check(_state_file: Path, dry_run: bool) -> None:
    alert_name = os.getenv("TELEGRAM_STOCK_ALERT_NAME", "POS SYSTEM").strip() or "POS SYSTEM"
    max_items = max(1, env_int("TELEGRAM_STOCK_ALERT_MAX_ITEMS", 30))

    rows = fetch_alert_rows()
    rows = sorted(rows, key=lambda row: (row.product_name, row.product_no))
    if not rows:
        print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] no understock products")
        return

    message = build_message(alert_name, rows, max_items=max_items)
    if dry_run:
        print("DRY RUN - message would be sent:")
        print(message)
        return

    token = env_required("TELEGRAM_BOT_TOKEN")
    chat_id = env_required("TELEGRAM_CHAT_ID")
    send_telegram_message(token, chat_id, message)
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] sent Telegram stock alert update")


def main() -> int:
    args = parse_args()
    env_file = Path(args.env_file).resolve()
    state_file = Path(args.state_file).resolve()

    load_env_file(env_file)

    if args.discover_chat_id:
        token = env_required("TELEGRAM_BOT_TOKEN")
        chats = discover_chat_ids(token)
        if not chats:
            print("No chats found in getUpdates yet. Send at least one message to the bot, then run again.")
            return 0
        print("Found chats:")
        for chat in chats:
            print(f"- chat_id={chat['chat_id']} | type={chat['type']} | title={chat['title']}")
        return 0

    if os.getenv("TELEGRAM_CHAT_ID", "").strip() == "" and not args.dry_run:
        print("Missing TELEGRAM_CHAT_ID in .env.", file=sys.stderr)
        print("Run with --discover-chat-id after messaging your bot to see available chat IDs.", file=sys.stderr)
        return 1

    interval = args.interval if args.interval > 0 else env_int("TELEGRAM_STOCK_ALERT_INTERVAL", 60)
    interval = max(15, interval)

    if args.once:
        run_check(state_file, dry_run=args.dry_run)
        return 0

    print(f"Stock alert monitor started (interval={interval}s)")
    while True:
        try:
            run_check(state_file, dry_run=args.dry_run)
        except KeyboardInterrupt:
            print("Stopped by user")
            return 0
        except Exception as exc:
            print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] error: {exc}", file=sys.stderr)
        time.sleep(interval)


if __name__ == "__main__":
    raise SystemExit(main())
