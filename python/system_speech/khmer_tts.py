import asyncio
import ctypes
import os
import sys
import tempfile

try:
    import edge_tts
except ModuleNotFoundError:
    print(
        "Missing Python package 'edge-tts'. Install it with: python -m pip install edge-tts",
        file=sys.stderr,
    )
    sys.exit(1)


async def speak(text: str, output_file: str) -> None:
    communicate = edge_tts.Communicate(text=text, voice="km-KH-SreymomNeural")
    await communicate.save(output_file)


def play_mp3(file_path: str) -> None:
    """Play an MP3 file using Windows built-in MCI."""
    winmm = ctypes.windll.winmm
    winmm.mciSendStringW(f'open "{file_path}" type mpegvideo alias payment_audio', None, 0, None)
    winmm.mciSendStringW("play payment_audio wait", None, 0, None)
    winmm.mciSendStringW("close payment_audio", None, 0, None)


def format_amount(amount_str: str, currency: str) -> str:
    try:
        amount = float(amount_str)
        if currency == "KHR" or amount == int(amount):
            return str(int(amount))
        return f"{amount:.2f}"
    except ValueError:
        return amount_str


def payment_text(amount_str: str, currency: str, debt_str: str) -> str:
    formatted = format_amount(amount_str, currency)
    try:
        debt = float(debt_str)
    except ValueError:
        debt = 0.0

    currency_name = "ដុល្លារ" if currency == "USD" else "រៀល"
    if debt > 0:
        formatted_debt = format_amount(str(round(debt, 2)), currency)
        return f"បានទទួល {formatted} {currency_name} និងជំពាក់ {formatted_debt} {currency_name}"

    return f"បានទទួល {formatted} {currency_name} សូមអរគុណ!"


if __name__ == "__main__":
    amount_arg = sys.argv[1] if len(sys.argv) > 1 else "0"
    currency_arg = sys.argv[2].upper() if len(sys.argv) > 2 else "KHR"
    debt_arg = sys.argv[3] if len(sys.argv) > 3 else "0"
    currency_arg = "USD" if currency_arg == "USD" else "KHR"

    output_file = os.path.join(tempfile.gettempdir(), "pos_payment_receive.mp3")
    asyncio.run(speak(payment_text(amount_arg, currency_arg, debt_arg), output_file))
    play_mp3(output_file)
