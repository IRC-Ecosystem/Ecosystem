import os
import jwt
from datetime import datetime, timedelta
from dotenv import load_dotenv

load_dotenv()

SECRET_KEY = os.getenv("SECRET_KEY", "")
if len(SECRET_KEY) < 32:
    raise RuntimeError("SECRET_KEY wajib diatur minimal 32 karakter")
ALGORITHM = os.getenv("ALGORITHM", "HS256")


def verifikasi_token(token: str):
    """Cek keaslian dan masa berlaku JWT."""
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        return {
            "valid": True,
            "user_id": payload.get("user_id"),
            "role": payload.get("role", ""),
            "pesan": "Token valid",
        }
    except jwt.ExpiredSignatureError:
        return {"valid": False, "pesan": "Token kadaluarsa"}
    except jwt.InvalidTokenError:
        return {"valid": False, "pesan": "Token tidak valid atau format salah"}


def bikin_token(user_id: str, role: str):
    """Buat JWT token dengan role."""
    batas_waktu = datetime.utcnow() + timedelta(minutes=30)
    payload = {"user_id": user_id, "role": role, "exp": batas_waktu}
    return jwt.encode(payload, SECRET_KEY, algorithm=ALGORITHM)
