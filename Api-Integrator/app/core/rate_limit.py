import time
from collections import defaultdict

_rate_limit_store: dict[str, list[float]] = defaultdict(list)
RATE_LIMIT_MAX = 30
RATE_LIMIT_WINDOW = 60


def check_rate_limit(identifier: str):
    now = time.time()
    window_start = now - RATE_LIMIT_WINDOW
    _rate_limit_store[identifier] = [
        ts for ts in _rate_limit_store[identifier] if ts > window_start
    ]
    count = len(_rate_limit_store[identifier])
    if count >= RATE_LIMIT_MAX:
        reset_in = int(_rate_limit_store[identifier][0] + RATE_LIMIT_WINDOW - now)
        return False, (
            f"Rate limit tercapai. Maks {RATE_LIMIT_MAX} request per "
            f"{RATE_LIMIT_WINDOW} detik. Reset dalam {reset_in} detik."
        )
    _rate_limit_store[identifier].append(now)
    return True, f"OK ({count + 1}/{RATE_LIMIT_MAX})"

