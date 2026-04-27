# Telegram Stock Alert Monitor

This worker checks product understock state and sends Telegram alerts.

## 1) Install dependency

```powershell
python -m pip install -r python/telegram/requirements.txt
```

## 2) Configure `.env`

Add these keys to project `.env`:

```dotenv
TELEGRAM_BOT_TOKEN=
TELEGRAM_CHAT_ID=
TELEGRAM_STOCK_ALERT_ENABLED=false
TELEGRAM_STOCK_ALERT_RUN_SOURCE=popup
TELEGRAM_STOCK_ALERT_INTERVAL=60
TELEGRAM_STOCK_ALERT_INCLUDE_OVERSTOCK=false
TELEGRAM_STOCK_ALERT_MAX_ITEMS=30
TELEGRAM_STOCK_ALERT_NAME="CVT STORE"
```

Notes:
- `TELEGRAM_CHAT_ID` can be a numeric chat ID (user/group/channel) or channel username like `@my_channel`.
- If you do not know chat ID yet, message your bot first, then run discover command below.

## 3) Discover chat ID (optional)

```powershell
python python/telegram/stock_alert_monitor.py --discover-chat-id
```

## 4) Run one check

```powershell
python python/telegram/stock_alert_monitor.py --once
```

`--state-file` remains accepted for backward compatibility but is ignored in popup mode.

## 5) Run continuously

```powershell
python python/telegram/stock_alert_monitor.py
```

## 6) Dry run (no Telegram send)

```powershell
python python/telegram/stock_alert_monitor.py --once --dry-run
```

## Optional: automatic run via Laravel scheduler

1. Set `TELEGRAM_STOCK_ALERT_ENABLED=true` and `TELEGRAM_STOCK_ALERT_RUN_SOURCE=scheduler` in `.env`
2. Start scheduler worker:

```powershell
php artisan schedule:work
```

## Popup-trigger integration

When `TELEGRAM_STOCK_ALERT_RUN_SOURCE=popup` (default), the web UI popup `Stock Alert` is the source of truth.
Laravel calls Python immediately when popup conditions are met, with global dedupe via server cache (one Telegram send per count increase).

No `.stock_alert_state.json` file is required in popup mode.

---

# Telegram Payment Alert Sender

This sender pushes a payment message to Telegram immediately when Laravel calls it.

## Configure `.env`

```dotenv
TELEGRAM_PAYMENT_ALERT_ENABLED=true
TELEGRAM_PAYMENT_BOT_TOKEN=
TELEGRAM_PAYMENT_CHAT_ID=
```

Notes:
- `TELEGRAM_PAYMENT_BOT_TOKEN` is usually a different bot token from stock alerts.
- `TELEGRAM_PAYMENT_CHAT_ID` can be a private/group/channel numeric chat ID.
- If payment keys are not set, the sender falls back to `TELEGRAM_BOT_TOKEN` and `TELEGRAM_CHAT_ID`.
- If you get `401 Unauthorized`, regenerate bot token in BotFather and update `.env`.

## Discover chat ID for payment bot

```powershell
python python/telegram/payment_alert_sender.py --discover-chat-id
```

## Dry run payment message format

```powershell
python python/telegram/payment_alert_sender.py --once --dry-run --customer-name "John" --paid-by cash --total 120 --paid 100 --debt 20 --currency USD
```

Message format:

```text
From: John
Paid by: Cash
Total: $120.00
Paid: $100.00
Debt: $20.00
```
