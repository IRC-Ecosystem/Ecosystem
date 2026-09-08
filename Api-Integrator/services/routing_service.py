import httpx
import os
import time
from typing import Optional
from dotenv import load_dotenv

from services.plugin_service import get_active_plugin_by_app_id, list_api_plugins, plugin_headers

load_dotenv()

SMARTBANK_BASE_URL = os.getenv("SMARTBANK_BASE_URL", "http://smartbank-gateway:4000")
MARKETPLACE_BASE_URL = os.getenv("MARKETPLACE_BASE_URL", "http://marketplace:4002")
POS_BASE_URL = os.getenv("POS_BASE_URL", "http://warungpos:4003")
SUPPLIERHUB_BASE_URL = os.getenv("SUPPLIERHUB_BASE_URL", "http://supplierhub:4004")
LOGISTIKITA_BASE_URL = os.getenv("LOGISTIKITA_BASE_URL", "http://logistikita:4005")
UMKM_INSIGHT_BASE_URL = os.getenv("UMKM_INSIGHT_BASE_URL", "http://umkm-insight")
SMARTBANK_MIRROR_URL = os.getenv("SMARTBANK_MIRROR_URL", "")
MARKETPLACE_MIRROR_URL = os.getenv("MARKETPLACE_MIRROR_URL", "")
POS_MIRROR_URL = os.getenv("POS_MIRROR_URL", "")
SUPPLIERHUB_MIRROR_URL = os.getenv("SUPPLIERHUB_MIRROR_URL", "")
LOGISTIKITA_MIRROR_URL = os.getenv("LOGISTIKITA_MIRROR_URL", "")
UMKM_INSIGHT_MIRROR_URL = os.getenv("UMKM_INSIGHT_MIRROR_URL", "")
APP_API_KEYS = {
    "smartbank": os.getenv("SMARTBANK_API_KEY", ""),
    "marketplace": os.getenv("MARKETPLACE_API_KEY", ""),
    "pos": os.getenv("POS_API_KEY", ""),
    "supplierhub": os.getenv("SUPPLIERHUB_API_KEY", ""),
    "logistikita": os.getenv("LOGISTIKITA_API_KEY", ""),
    "umkminsight": os.getenv("UMKM_INSIGHT_API_KEY", ""),
}


def parse_json_response(response: httpx.Response):
    try:
        return response.json()
    except Exception:
        return {"raw": response.text}


def build_headers(target_app: str):
    headers = {"Accept": "application/json"}
    api_key = APP_API_KEYS.get(target_app)
    if api_key:
        headers["X-API-Key"] = api_key
    return headers


def normalize_payload_for_target(target_app: str, data: dict):
    payload = dict(data or {})
    if target_app == "umkminsight":
        payload.setdefault("external_id", f"GW-{int(time.time() * 1000)}-{payload.get('user_id', 'external')}")
        payload.setdefault("source", payload.get("source_app") or payload.get("source") or "API_Gateway")
        payload.setdefault("type", "Income")
        payload.setdefault("status", "Success")
        payload.setdefault("description", "Transaksi diterima dari API Integrator")
    return payload


async def resolve_route(target_app: str, endpoint: Optional[str] = None):
    plugin = await get_active_plugin_by_app_id(target_app)
    if plugin:
        return {
            "source": "database_plugin",
            "app_id": plugin["app_id"],
            "base_url": plugin["base_url"],
            "endpoint": endpoint or plugin.get("default_endpoint") or "/",
            "method": (plugin.get("method") or "POST").upper(),
            "timeout": float(plugin.get("timeout_seconds") or 10),
            "headers": plugin_headers(plugin),
            "plugin": plugin,
        }

    base_url = APP_URLS.get(target_app)
    if not base_url:
        return None
    return {
        "source": "static_config",
        "app_id": target_app,
        "base_url": base_url,
        "endpoint": endpoint or APP_DEFAULT_ENDPOINTS.get(target_app, "/"),
        "method": "POST",
        "timeout": 10.0,
        "headers": build_headers(target_app),
        "plugin": None,
    }

# ==========================================
# DAFTAR URL SEMUA APLIKASI EKOSISTEM UMKM
# Ganti IP/port sesuai teman kelompok masing-masing
# ==========================================
APP_URLS = {
    "smartbank":    SMARTBANK_BASE_URL,       # Kelompok 1
    "marketplace":  MARKETPLACE_BASE_URL,     # Kelompok 2
    "pos":          POS_BASE_URL,             # Kelompok 3
    "supplierhub":  SUPPLIERHUB_BASE_URL,     # Kelompok 4
    "logistikita":  LOGISTIKITA_BASE_URL,     # Kelompok 5
    "umkminsight":  UMKM_INSIGHT_BASE_URL,    # Kelompok 6
}

# Default endpoint masing-masing aplikasi
APP_DEFAULT_ENDPOINTS = {
    "smartbank":    "/smartbank/pembayaran_transaksi",
    "marketplace":  "/marketplace/checkout",
    "pos":          "/pos/transaksi",
    "supplierhub":  "/supplier/order_bahan",
    "logistikita":  "/logistik/request_pengiriman",
    "umkminsight":  "/api/transactions.php",
}

# Deskripsi singkat tiap app untuk dokumentasi
APP_INFO = {
    "smartbank":    {"kelompok": "Kelompok 1", "peran": "Core banking, ledger, payment processor"},
    "marketplace":  {"kelompok": "Kelompok 2", "peran": "Jual beli produk UMKM (PasarKita)"},
    "pos":          {"kelompok": "Kelompok 3", "peran": "Transaksi kasir offline (WarungPOS)"},
    "supplierhub":  {"kelompok": "Kelompok 4", "peran": "Supply chain B2B bahan baku"},
    "logistikita":  {"kelompok": "Kelompok 5", "peran": "Layanan pengiriman dan ongkir"},
    "umkminsight":  {"kelompok": "Kelompok 6", "peran": "Analytics dashboard (read-only)"},
}


async def teruskan_ke_app(target_app: str, data: dict, endpoint: Optional[str] = None):
    """
    Forward request ke aplikasi target di ekosistem UMKM.
    
    Args:
        target_app : ID aplikasi tujuan (lihat APP_URLS)
        data       : Payload yang akan dikirim
        endpoint   : Override endpoint tujuan (opsional, pakai default kalau None)
    
    Returns:
        dict response dari aplikasi tujuan, atau dict error
    """
    route = await resolve_route(target_app, endpoint)
    if not route:
        return {
            "status": "gagal",
            "pesan": f"Aplikasi '{target_app}' tidak dikenali oleh Gateway.",
            "pilihan": list(APP_URLS.keys())
        }

    target_endpoint = route["endpoint"]
    full_url = f"{route['base_url']}{target_endpoint}"
    method = route["method"]
    payload = normalize_payload_for_target(target_app, data)

    async with httpx.AsyncClient(timeout=route["timeout"]) as client:
        try:
            if method == "GET":
                response = await client.get(full_url, params=payload, headers=route["headers"])
            elif method == "PUT":
                response = await client.put(full_url, json=payload, headers=route["headers"])
            elif method == "PATCH":
                response = await client.patch(full_url, json=payload, headers=route["headers"])
            elif method == "DELETE":
                response = await client.delete(full_url, json=payload, headers=route["headers"])
            else:
                response = await client.post(full_url, json=payload, headers=route["headers"])
            body = parse_json_response(response)
            if 200 <= response.status_code < 300:
                return {
                    "status": "success",
                    "http_status": response.status_code,
                    "route_source": route["source"],
                    "method": method,
                    "url": full_url,
                    "data": body,
                }
            return {
                "status": "error",
                "http_status": response.status_code,
                "route_source": route["source"],
                "method": method,
                "url": full_url,
                "pesan": f"Service '{target_app}' mengembalikan HTTP {response.status_code}.",
                "data": body,
            }
        except httpx.ConnectError:
            return {
                "status": "error",
                "http_status": 503,
                "pesan": f"Gagal konek ke '{target_app}' ({full_url}). Pastikan service sudah running."
            }
        except httpx.TimeoutException:
            return {
                "status": "error",
                "http_status": 504,
                "pesan": f"Timeout! '{target_app}' tidak merespons dalam 10 detik."
            }
        except Exception as e:
            return {
                "status": "error",
                "http_status": 502,
                "pesan": f"Error routing ke '{target_app}': {str(e)}"
            }


async def teruskan_ke_smartbank(data_transaksi: dict):
    """Backward compatible — forward ke SmartBank endpoint pembayaran"""
    return await teruskan_ke_app("smartbank", data_transaksi, "/smartbank/pembayaran_transaksi")


def get_daftar_app():
    """Kembalikan daftar lengkap app yang terdaftar di routing table"""
    return [
        {
            "app_id": app_id,
            "kelompok": APP_INFO[app_id]["kelompok"],
            "peran": APP_INFO[app_id]["peran"],
            "base_url": base_url,
            "default_endpoint": APP_DEFAULT_ENDPOINTS.get(app_id, "/"),
            "url_lengkap": f"{base_url}{APP_DEFAULT_ENDPOINTS.get(app_id, '/')}"
        }
        for app_id, base_url in APP_URLS.items()
    ]


async def get_daftar_app_with_plugins():
    static_apps = get_daftar_app()
    plugins = await list_api_plugins(include_inactive=False)
    static_ids = {app["app_id"] for app in static_apps}
    merged = []
    for app in static_apps:
        plugin = next((p for p in plugins if p["app_id"] == app["app_id"]), None)
        if plugin:
            merged.append({
                **app,
                "base_url": plugin["base_url"],
                "default_endpoint": plugin["default_endpoint"],
                "url_lengkap": f"{plugin['base_url']}{plugin['default_endpoint']}",
                "method": plugin["method"],
                "source": "database_plugin",
            })
        else:
            merged.append({**app, "method": "POST", "source": "static_config"})

    for plugin in plugins:
        if plugin["app_id"] not in static_ids:
            merged.append({
                "app_id": plugin["app_id"],
                "kelompok": "Plugin",
                "peran": plugin.get("description") or "Endpoint tambahan dari API Plugin Manager",
                "base_url": plugin["base_url"],
                "default_endpoint": plugin["default_endpoint"],
                "url_lengkap": f"{plugin['base_url']}{plugin['default_endpoint']}",
                "method": plugin["method"],
                "source": "database_plugin",
            })
    return merged


# ==========================================
# BACKTRACKING ROUTING FAILOVER
# Algoritma Backtracking untuk resilient routing
# ==========================================

# Routing table dengan MULTIPLE candidates per app
# Setiap app punya primary, mirror, dan fallback route
ROUTE_CANDIDATES = {
    "smartbank": [
        {"label": "primary",  "url": SMARTBANK_BASE_URL, "endpoint": "/smartbank/pembayaran_transaksi", "priority": 1},
        {"label": "mirror",   "url": SMARTBANK_MIRROR_URL or SMARTBANK_BASE_URL, "endpoint": "/smartbank/pembayaran_transaksi", "priority": 2},
        {"label": "fallback", "url": SMARTBANK_BASE_URL, "endpoint": "/health",                             "priority": 3},
    ],
    "marketplace": [
        {"label": "primary",  "url": MARKETPLACE_BASE_URL, "endpoint": "/marketplace/checkout",  "priority": 1},
        {"label": "mirror",   "url": MARKETPLACE_MIRROR_URL or MARKETPLACE_BASE_URL, "endpoint": "/marketplace/checkout",  "priority": 2},
        {"label": "fallback", "url": MARKETPLACE_BASE_URL, "endpoint": "/marketplace/fallback",  "priority": 3},
    ],
    "pos": [
        {"label": "primary",  "url": POS_BASE_URL, "endpoint": "/pos/transaksi",  "priority": 1},
        {"label": "mirror",   "url": POS_MIRROR_URL or POS_BASE_URL, "endpoint": "/pos/transaksi",  "priority": 2},
        {"label": "fallback", "url": POS_BASE_URL, "endpoint": "/pos/fallback",   "priority": 3},
    ],
    "supplierhub": [
        {"label": "primary",  "url": SUPPLIERHUB_BASE_URL, "endpoint": "/supplier/order_bahan",  "priority": 1},
        {"label": "mirror",   "url": SUPPLIERHUB_MIRROR_URL or SUPPLIERHUB_BASE_URL, "endpoint": "/supplier/order_bahan",  "priority": 2},
        {"label": "fallback", "url": SUPPLIERHUB_BASE_URL, "endpoint": "/supplier/fallback",     "priority": 3},
    ],
    "logistikita": [
        {"label": "primary",  "url": LOGISTIKITA_BASE_URL, "endpoint": "/logistik/request_pengiriman", "priority": 1},
        {"label": "mirror",   "url": LOGISTIKITA_MIRROR_URL or LOGISTIKITA_BASE_URL, "endpoint": "/logistik/request_pengiriman", "priority": 2},
        {"label": "fallback", "url": LOGISTIKITA_BASE_URL, "endpoint": "/logistik/fallback",          "priority": 3},
    ],
    "umkminsight": [
        {"label": "primary",  "url": UMKM_INSIGHT_BASE_URL, "endpoint": "/api/transactions.php", "priority": 1},
        {"label": "mirror",   "url": UMKM_INSIGHT_MIRROR_URL or UMKM_INSIGHT_BASE_URL, "endpoint": "/api/transactions.php", "priority": 2},
        {"label": "fallback", "url": UMKM_INSIGHT_BASE_URL, "endpoint": "/api/health.php",       "priority": 3, "method": "GET"},
    ],
}


async def backtracking_route(app_id: str, data: dict, candidates: list, index: int = 0, trace: list = None):
    """
    ========================================
    ALGORITMA BACKTRACKING — Routing Failover
    ========================================

    Cara kerja:
    1. Coba candidate[index] (kirim HTTP request)
    2. Jika SUKSES → return hasil + trace perjalanan
    3. Jika GAGAL (timeout/connection error) → BACKTRACK
       → Panggil rekursif dengan index + 1
    4. BASE CASE: index >= len(candidates) → semua gagal

    Pruning:
    - Skip candidates yang diketahui mati (opsional, bisa ditambahkan)

    Complexity: O(n) dimana n = jumlah kandidat route per app

    Args:
        app_id     : ID aplikasi target (misal "smartbank")
        data       : Payload request yang akan dikirim
        candidates : List route candidates [{label, url, endpoint, priority}]
        index      : Index kandidat saat ini (untuk rekursi)
        trace      : Riwayat percobaan routing (untuk logging)

    Returns:
        dict berisi status, data response, trace backtracking, dll
    """
    if trace is None:
        trace = []

    # ── BASE CASE ──────────────────────────────────
    # Semua kandidat sudah dicoba, semuanya gagal
    if index >= len(candidates):
        return {
            "status": "gagal",
            "pesan": f"Semua {len(candidates)} route untuk '{app_id}' gagal setelah backtracking.",
            "kode": "ALL_ROUTES_EXHAUSTED",
            "trace": trace,
            "total_attempts": len(candidates),
            "algoritma": "backtracking"
        }

    candidate = candidates[index]
    full_url = f"{candidate['url']}{candidate['endpoint']}"

    # Step info untuk trace
    step = {
        "step": index + 1,
        "label": candidate["label"],
        "url": full_url,
        "priority": candidate["priority"],
    }

    try:
        # ── CONSTRAINT CHECK ──────────────────────
        # Coba kirim request ke candidate ini
        method = candidate.get("method", "POST").upper()
        headers = candidate.get("headers") or build_headers(app_id)
        timeout = float(candidate.get("timeout") or 5.0)
        payload = normalize_payload_for_target(app_id, data)
        async with httpx.AsyncClient(timeout=timeout) as client:
            if method == "GET":
                response = await client.get(full_url, params=payload, headers=headers)
            elif method == "PUT":
                response = await client.put(full_url, json=payload, headers=headers)
            elif method == "PATCH":
                response = await client.patch(full_url, json=payload, headers=headers)
            elif method == "DELETE":
                response = await client.delete(full_url, json=payload, headers=headers)
            else:
                response = await client.post(full_url, json=payload, headers=headers)
            result = parse_json_response(response)
            response.raise_for_status()

        # ── SOLUSI DITEMUKAN ──────────────────────
        step["status"] = "SUKSES"
        step["response_code"] = response.status_code
        step["aksi"] = "Route berhasil — solusi ditemukan!"
        trace.append(step)

        return {
            "status": "sukses",
            "data": result,
            "route_used": candidate["label"],
            "route_url": full_url,
            "trace": trace,
            "total_attempts": index + 1,
            "total_candidates": len(candidates),
            "algoritma": "backtracking"
        }

    except httpx.ConnectError:
        # ── BACKTRACK: Connection refused ─────────
        step["status"] = "GAGAL_BACKTRACK"
        step["error"] = "ConnectError"
        step["error_detail"] = f"Tidak bisa konek ke {full_url}"
        if index + 1 < len(candidates):
            next_candidate = candidates[index + 1]
            step["aksi"] = f"BACKTRACK → coba kandidat #{index + 2} ({next_candidate['label']})"
        else:
            step["aksi"] = "Semua kandidat habis — tidak ada lagi yang bisa dicoba"
        trace.append(step)

        # ── RECURSIVE CALL: Backtrack ke kandidat berikutnya ──
        return await backtracking_route(app_id, data, candidates, index + 1, trace)

    except httpx.TimeoutException:
        # ── BACKTRACK: Timeout ────────────────────
        step["status"] = "GAGAL_BACKTRACK"
        step["error"] = "TimeoutException"
        step["error_detail"] = f"Timeout setelah 5 detik menunggu {full_url}"
        if index + 1 < len(candidates):
            next_candidate = candidates[index + 1]
            step["aksi"] = f"BACKTRACK → coba kandidat #{index + 2} ({next_candidate['label']})"
        else:
            step["aksi"] = "Semua kandidat habis — tidak ada lagi yang bisa dicoba"
        trace.append(step)

        # ── RECURSIVE CALL: Backtrack ke kandidat berikutnya ──
        return await backtracking_route(app_id, data, candidates, index + 1, trace)

    except Exception as e:
        # ── BACKTRACK: Error lain ─────────────────
        step["status"] = "GAGAL_BACKTRACK"
        step["error"] = type(e).__name__
        step["error_detail"] = str(e)
        if index + 1 < len(candidates):
            next_candidate = candidates[index + 1]
            step["aksi"] = f"BACKTRACK → coba kandidat #{index + 2} ({next_candidate['label']})"
        else:
            step["aksi"] = "Semua kandidat habis — tidak ada lagi yang bisa dicoba"
        trace.append(step)

        # ── RECURSIVE CALL: Backtrack ke kandidat berikutnya ──
        return await backtracking_route(app_id, data, candidates, index + 1, trace)


async def route_dengan_backtracking(target_app: str, data: dict, custom_endpoint: str = None):
    """
    Wrapper utama — panggil backtracking_route() dengan candidates dari ROUTE_CANDIDATES.
    
    Jika target_app tidak ada di ROUTE_CANDIDATES, fallback ke single-route dari APP_URLS.
    """
    plugin = await get_active_plugin_by_app_id(target_app)
    if plugin:
        candidates = [
            {
                "label": "plugin-primary",
                "url": plugin["base_url"],
                "endpoint": custom_endpoint or plugin["default_endpoint"],
                "priority": 1,
                "headers": plugin_headers(plugin),
                "timeout": float(plugin.get("timeout_seconds") or 10),
                "method": (plugin.get("method") or "POST").upper(),
            }
        ]
    else:
        candidates = ROUTE_CANDIDATES.get(target_app)

    if not candidates:
        # Fallback: app tidak punya multi-route, pakai APP_URLS biasa
        base_url = APP_URLS.get(target_app)
        if not base_url:
            return {
                "status": "gagal",
                "pesan": f"Aplikasi '{target_app}' tidak dikenali oleh Gateway.",
                "pilihan": list(APP_URLS.keys()),
                "algoritma": "backtracking",
                "trace": []
            }
        # Buat single candidate dari APP_URLS
        endpoint = custom_endpoint or APP_DEFAULT_ENDPOINTS.get(target_app, "/")
        candidates = [
            {"label": "primary", "url": base_url, "endpoint": endpoint, "priority": 1, "headers": build_headers(target_app), "timeout": 5.0, "method": "POST"}
        ]

    # Override endpoint kalau user kasih custom
    if custom_endpoint:
        candidates = [
            {**c, "endpoint": custom_endpoint} for c in candidates
        ]

    # Sortir berdasarkan priority (ascending)
    candidates = sorted(candidates, key=lambda c: c["priority"])

    # Jalankan algoritma backtracking
    return await backtracking_route(target_app, data, candidates)


def get_route_candidates_info():
    """Kembalikan info routing table backtracking untuk frontend"""
    result = []
    for app_id, candidates in ROUTE_CANDIDATES.items():
        info = APP_INFO.get(app_id, {})
        result.append({
            "app_id": app_id,
            "kelompok": info.get("kelompok", "—"),
            "peran": info.get("peran", "—"),
            "total_candidates": len(candidates),
            "candidates": [
                {
                    "label": c["label"],
                    "url": f"{c['url']}{c['endpoint']}",
                    "priority": c["priority"]
                }
                for c in sorted(candidates, key=lambda x: x["priority"])
            ]
        })
    return result


async def get_route_candidates_info_with_plugins():
    result = get_route_candidates_info()
    plugins = await list_api_plugins(include_inactive=False)
    known = {item["app_id"] for item in result}

    for item in result:
        plugin = next((p for p in plugins if p["app_id"] == item["app_id"]), None)
        if plugin:
            item["source"] = "database_plugin"
            item["total_candidates"] = 1
            item["candidates"] = [{
                "label": "plugin-primary",
                "url": f"{plugin['base_url']}{plugin['default_endpoint']}",
                "priority": 1,
            }]
        else:
            item["source"] = "static_config"

    for plugin in plugins:
        if plugin["app_id"] not in known:
            result.append({
                "app_id": plugin["app_id"],
                "kelompok": "Plugin",
                "peran": plugin.get("description") or "Endpoint tambahan dari API Plugin Manager",
                "source": "database_plugin",
                "total_candidates": 1,
                "candidates": [{
                    "label": "plugin-primary",
                    "url": f"{plugin['base_url']}{plugin['default_endpoint']}",
                    "priority": 1,
                }],
            })
    return result
