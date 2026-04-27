import argparse
import json
import os
import sys
from pathlib import Path
from typing import Dict, List
from urllib import error
from urllib import parse, request


SCRIPT_DIR = Path(__file__).resolve().parent
PROJECT_ROOT = SCRIPT_DIR.parent.parent
DEFAULT_ENV_FILE = PROJECT_ROOT / ".env"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Send Telegram payment alerts.")
    parser.add_argument("--env-file", default=str(DEFAULT_ENV_FILE), help="Path to .env file (default: project .env).")
    parser.add_argument("--once", action="store_true", help="Run once and exit (default behavior for this sender).")
    parser.add_argument("--dry-run", action="store_true", help="Do not send message to Telegram, only print result.")
    parser.add_argument("--discover-chat-id", action="store_true", help="List available chat IDs from bot updates.")
    parser.add_argument("--customer-name", default="", help="Customer name.")
    parser.add_argument("--paid-by", default="cash", help="Payment method: cash or qr.")
    parser.add_argument("--total", default="0", help="Total amount.")
    parser.add_argument("--paid", default="0", help="Paid amount.")
    parser.add_argument("--debt", default="0", help="Debt amount.")
    parser.add_argument("--currency", default="USD", help="Currency code (USD, EUR, KHR, ...).")
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


def env_required_any(*names: str) -> str:
    for name in names:
        value = os.getenv(name, "").strip()
        if value != "":
            return value
    joined = ", ".join(names)
    raise RuntimeError(f"Missing required environment variable. Set one of: {joined}")


def parse_amount(raw: str) -> float:
    try:
        return round(max(0.0, float(raw.strip())), 2)
    except Exception:
        return 0.0


def normalize_paid_by(raw: str) -> str:
    value = raw.strip().lower()
    if value == "qr":
        return "QR"
    return "Cash"


def format_amount(amount: float, currency_code: str) -> str:
    code = currency_code.strip().upper() or "USD"
    number = f"{amount:,.2f}"

    if code == "USD":
        return f"${number}"
    if code == "EUR":
        return f"EUR {number}"
    if code in {"KHR", "RIEL"}:
        return f"Riel {number}"
    return f"{code} {number}"


def build_message(customer_name: str, paid_by: str, total: float, paid: float, debt: float, currency_code: str) -> str:
    lines = [
        f"From: {customer_name}",
        f"Paid by: {normalize_paid_by(paid_by)}",
        f"Total: {format_amount(total, currency_code)}",
        f"Paid: {format_amount(paid, currency_code)}",
    ]

    if debt > 0:
        lines.append(f"Debt: {format_amount(debt, currency_code)}")

    return "\n".join(lines)


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
    try:
        with request.urlopen(req, timeout=20) as response:
            content = response.read().decode("utf-8")
        return json.loads(content)
    except error.HTTPError as exc:
        detail = exc.read().decode("utf-8", errors="ignore").strip()
        if detail == "":
            detail = str(exc.reason)
        raise RuntimeError(f"Telegram HTTP {exc.code}: {detail}") from exc


def http_post_json(url: str, body: bytes) -> Dict[str, object]:
    req = request.Request(url, data=body, method="POST")
    try:
        with request.urlopen(req, timeout=20) as response:
            content = response.read().decode("utf-8")
        return json.loads(content)
    except error.HTTPError as exc:
        detail = exc.read().decode("utf-8", errors="ignore").strip()
        if detail == "":
            detail = str(exc.reason)
        raise RuntimeError(f"Telegram HTTP {exc.code}: {detail}") from exc


def main() -> int:
    args = parse_args()
    env_file = Path(args.env_file).resolve()
    load_env_file(env_file)

    token = env_required_any("TELEGRAM_PAYMENT_BOT_TOKEN", "TELEGRAM_BOT_TOKEN")

    if args.discover_chat_id:
        try:
            chats = discover_chat_ids(token)
            if not chats:
                print("No chats found in getUpdates yet. Send at least one message to the bot, then run again.")
                return 0
            print("Found chats:")
            for chat in chats:
                print(f"- chat_id={chat['chat_id']} | type={chat['type']} | title={chat['title']}")
            return 0
        except Exception as exc:
            print(f"Failed to discover chat IDs: {exc}", file=sys.stderr)
            return 1

    enabled = os.getenv("TELEGRAM_PAYMENT_ALERT_ENABLED", "false").strip().lower() in {"1", "true", "yes", "on"}
    if not enabled:
        print("Payment alert sender is disabled (TELEGRAM_PAYMENT_ALERT_ENABLED=false).")
        return 0

    chat_id = env_required_any("TELEGRAM_PAYMENT_CHAT_ID", "TELEGRAM_CHAT_ID")

    customer_name = args.customer_name.strip() or "Walk-in Customer"
    paid_by = args.paid_by.strip() or "cash"
    total = parse_amount(args.total)
    paid = parse_amount(args.paid)
    debt = parse_amount(args.debt)
    currency = args.currency.strip().upper() or "USD"

    message = build_message(
        customer_name=customer_name,
        paid_by=paid_by,
        total=total,
        paid=paid,
        debt=debt,
        currency_code=currency,
    )

    if args.dry_run:
        print("DRY RUN - message would be sent:")
        print(message)
        return 0

    try:
        send_telegram_message(token, chat_id, message)
        print("Telegram payment alert sent")
        return 0
    except Exception as exc:
        print(f"Failed to send Telegram payment alert: {exc}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    raise SystemExit(main())
