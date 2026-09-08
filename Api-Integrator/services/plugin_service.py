import re
import time
from datetime import datetime
from typing import Optional
from urllib.parse import urlparse

import aiomysql
import httpx

from services.log_service import get_conn

APP_ID_RE = re.compile(r"^[a-z0-9][a-z0-9_-]{1,78}[a-z0-9]$")
ALLOWED_METHODS = {"GET", "POST", "PUT", "PATCH", "DELETE"}


def normalize_base_url(value: str) -> str:
    url = (value or "").strip().rstrip("/")
    parsed = urlparse(url)
    if parsed.scheme not in {"http", "https"} or not parsed.netloc:
        raise ValueError("base_url wajib URL lengkap, contoh: http://localhost:4003")
    return url


def normalize_endpoint(value: str) -> str:
    endpoint = (value or "/").strip()
    if not endpoint.startswith("/"):
        endpoint = "/" + endpoint
    return endpoint


def validate_plugin_payload(data: dict) -> dict:
    app_id = (data.get("app_id") or "").strip().lower()
    if not APP_ID_RE.match(app_id):
        raise ValueError("app_id hanya boleh huruf kecil, angka, underscore, dan dash. Minimal 3 karakter.")

    app_name = (data.get("app_name") or "").strip()
    if not app_name:
        raise ValueError("app_name wajib diisi.")

    method = (data.get("method") or "POST").strip().upper()
    if method not in ALLOWED_METHODS:
        raise ValueError(f"method harus salah satu dari: {', '.join(sorted(ALLOWED_METHODS))}")

    timeout = int(data.get("timeout_seconds") or 10)
    if timeout < 1 or timeout > 60:
        raise ValueError("timeout_seconds harus di antara 1 sampai 60.")

    health_endpoint = data.get("health_endpoint")
    return {
        "app_id": app_id,
        "app_name": app_name,
        "base_url": normalize_base_url(data.get("base_url") or ""),
        "default_endpoint": normalize_endpoint(data.get("default_endpoint") or "/"),
        "method": method,
        "health_endpoint": normalize_endpoint(health_endpoint) if health_endpoint else None,
        "auth_header_name": (data.get("auth_header_name") or "").strip() or None,
        "auth_header_value": (data.get("auth_header_value") or "").strip() or None,
        "timeout_seconds": timeout,
        "is_active": bool(data.get("is_active", True)),
        "description": (data.get("description") or "").strip() or None,
    }


def serialize_plugin(row: dict, include_secret: bool = False) -> dict:
    data = dict(row)
    data["is_active"] = bool(data.get("is_active"))
    for key in ("created_at", "updated_at"):
        if data.get(key) and hasattr(data[key], "isoformat"):
            data[key] = data[key].isoformat()
    data["auth_configured"] = bool(data.get("auth_header_value"))
    data["auth_header_value_masked"] = "********" if data["auth_configured"] else None
    if not include_secret:
        data["auth_header_value"] = None
    return data


async def list_api_plugins(include_inactive: bool = True) -> list[dict]:
    conn = await get_conn()
    try:
        async with conn.cursor(aiomysql.DictCursor) as cur:
            if include_inactive:
                await cur.execute("SELECT * FROM api_plugins ORDER BY app_name ASC")
            else:
                await cur.execute("SELECT * FROM api_plugins WHERE is_active = 1 ORDER BY app_name ASC")
            rows = await cur.fetchall()
        return [serialize_plugin(row) for row in rows]
    finally:
        conn.close()


async def get_api_plugin(plugin_id: int, include_secret: bool = False) -> Optional[dict]:
    conn = await get_conn()
    try:
        async with conn.cursor(aiomysql.DictCursor) as cur:
            await cur.execute("SELECT * FROM api_plugins WHERE id = %s", (plugin_id,))
            row = await cur.fetchone()
        return serialize_plugin(row, include_secret=include_secret) if row else None
    finally:
        conn.close()


async def get_active_plugin_by_app_id(app_id: str) -> Optional[dict]:
    conn = await get_conn()
    try:
        async with conn.cursor(aiomysql.DictCursor) as cur:
            await cur.execute(
                "SELECT * FROM api_plugins WHERE app_id = %s AND is_active = 1",
                ((app_id or "").strip().lower(),),
            )
            row = await cur.fetchone()
        return serialize_plugin(row, include_secret=True) if row else None
    finally:
        conn.close()


async def save_api_plugin(data: dict) -> dict:
    payload = validate_plugin_payload(data)
    conn = await get_conn()
    try:
        async with conn.cursor(aiomysql.DictCursor) as cur:
            await cur.execute("""
                INSERT INTO api_plugins
                    (app_id, app_name, base_url, default_endpoint, method, health_endpoint,
                     auth_header_name, auth_header_value, timeout_seconds, is_active, description)
                VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE
                    app_name = VALUES(app_name),
                    base_url = VALUES(base_url),
                    default_endpoint = VALUES(default_endpoint),
                    method = VALUES(method),
                    health_endpoint = VALUES(health_endpoint),
                    auth_header_name = VALUES(auth_header_name),
                    auth_header_value = VALUES(auth_header_value),
                    timeout_seconds = VALUES(timeout_seconds),
                    is_active = VALUES(is_active),
                    description = VALUES(description),
                    updated_at = CURRENT_TIMESTAMP
            """, (
                payload["app_id"], payload["app_name"], payload["base_url"],
                payload["default_endpoint"], payload["method"], payload["health_endpoint"],
                payload["auth_header_name"], payload["auth_header_value"],
                payload["timeout_seconds"], payload["is_active"], payload["description"],
            ))
            await cur.execute("SELECT * FROM api_plugins WHERE app_id = %s", (payload["app_id"],))
            row = await cur.fetchone()
        return serialize_plugin(row)
    finally:
        conn.close()


async def update_api_plugin(plugin_id: int, data: dict) -> Optional[dict]:
    payload = validate_plugin_payload(data)
    conn = await get_conn()
    try:
        async with conn.cursor(aiomysql.DictCursor) as cur:
            await cur.execute("""
                UPDATE api_plugins
                SET app_id = %s, app_name = %s, base_url = %s, default_endpoint = %s,
                    method = %s, health_endpoint = %s, auth_header_name = %s,
                    auth_header_value = %s, timeout_seconds = %s, is_active = %s,
                    description = %s, updated_at = CURRENT_TIMESTAMP
                WHERE id = %s
            """, (
                payload["app_id"], payload["app_name"], payload["base_url"],
                payload["default_endpoint"], payload["method"], payload["health_endpoint"],
                payload["auth_header_name"], payload["auth_header_value"],
                payload["timeout_seconds"], payload["is_active"], payload["description"],
                plugin_id,
            ))
            await cur.execute("SELECT * FROM api_plugins WHERE id = %s", (plugin_id,))
            row = await cur.fetchone()
        return serialize_plugin(row) if row else None
    finally:
        conn.close()


async def delete_api_plugin(plugin_id: int) -> bool:
    conn = await get_conn()
    try:
        async with conn.cursor() as cur:
            await cur.execute("DELETE FROM api_plugins WHERE id = %s", (plugin_id,))
            return cur.rowcount > 0
    finally:
        conn.close()


def plugin_headers(plugin: dict) -> dict:
    headers = {"Accept": "application/json"}
    if plugin.get("auth_header_name") and plugin.get("auth_header_value"):
        headers[plugin["auth_header_name"]] = plugin["auth_header_value"]
    return headers


async def test_api_plugin(plugin: dict) -> dict:
    endpoint = plugin.get("health_endpoint") or plugin.get("default_endpoint") or "/"
    url = f"{plugin['base_url']}{endpoint}"
    start = time.time()
    try:
        async with httpx.AsyncClient(timeout=float(plugin.get("timeout_seconds") or 10)) as client:
            response = await client.get(url, headers=plugin_headers(plugin))
        elapsed_ms = int((time.time() - start) * 1000)
        return {
            "status": "online" if response.status_code < 500 else "degraded",
            "url": url,
            "http_status": response.status_code,
            "response_time_ms": elapsed_ms,
            "checked_at": datetime.now().isoformat(),
        }
    except httpx.TimeoutException:
        return {
            "status": "timeout",
            "url": url,
            "http_status": 504,
            "response_time_ms": int((time.time() - start) * 1000),
            "checked_at": datetime.now().isoformat(),
        }
    except Exception as exc:
        return {
            "status": "offline",
            "url": url,
            "http_status": 503,
            "error": str(exc),
            "response_time_ms": int((time.time() - start) * 1000),
            "checked_at": datetime.now().isoformat(),
        }
