# type: ignore
import time
from typing import Optional

from fastapi import APIRouter, Depends, Header, HTTPException, Request
from pydantic import BaseModel

from models.schemas import ApiPluginPayload, ProxyRequest, RequestFormat, ResponseFormat
from app.core.rate_limit import check_rate_limit
from app.core.responses import api_error, api_success
from app.core.validation import validasi_amount
from services.jwt_service import bikin_token, verifikasi_token
from services.log_service import (
    add_financial_entry,
    autentikasi_user,
    catat_backtracking_log,
    catat_health_log,
    catat_legacy_log,
    catat_request_log,
    cek_akses_app,
    delete_financial_entry,
    get_all_paket,
    get_backtracking_stats,
    get_financial_ledger,
    get_financial_summary,
    get_health_stats,
    get_request_stats,
    get_status_app,
    hitung_fee_gateway,
    kurangi_quota,
    registrasi_app,
    tambah_fee_app,
    upgrade_paket_app,
)
from services.plugin_service import (
    delete_api_plugin,
    get_api_plugin,
    list_api_plugins,
    save_api_plugin,
    test_api_plugin,
    update_api_plugin,
)
from services.routing_service import (
    get_daftar_app_with_plugins,
    get_route_candidates_info_with_plugins,
    route_dengan_backtracking,
    teruskan_ke_app,
    teruskan_ke_smartbank,
)

router = APIRouter()

# ==========================================
# DEPENDENCY — CEK ROLE DARI JWT BEARER TOKEN
# ==========================================

def cek_role(required_roles: list):
    """
    Factory dependency untuk cek role user dari Bearer token.
    Contoh pemakaian: Depends(cek_role(["admin", "operator"]))
    """
    def _dependency(authorization: Optional[str] = Header(default=None)):
        if not authorization or not authorization.startswith("Bearer "):
            raise HTTPException(status_code=401, detail="Token tidak ditemukan. Silakan login terlebih dahulu.")
        token = authorization.split(" ", 1)[1]
        auth = verifikasi_token(token)
        if not auth["valid"]:
            raise HTTPException(status_code=401, detail=auth["pesan"])
        role = (auth.get("role") or "").lower()
        if role not in [r.lower() for r in required_roles]:
            raise HTTPException(
                status_code=403,
                detail=f"Akses ditolak. Halaman ini hanya untuk: {', '.join(required_roles)}."
            )
        return {"user_id": auth["user_id"], "role": role}
    return _dependency


class LoginCredentials(BaseModel):
    username: str
    password: str


class FinancialEntrySchema(BaseModel):
    tipe: str        # pemasukan | pengeluaran | aset | kewajiban
    kategori: str
    deskripsi: str
    jumlah: float
    user_id: str
    tanggal: str = None  # opsional, default hari ini


# ==========================================
# AUTH
# ==========================================

@router.post("/api/login")
async def login_api(creds: LoginCredentials, request: Request):
    ip_address = request.client.host if request.client else "unknown"
    user_agent = request.headers.get("user-agent", "unknown")
    hasil = await autentikasi_user(
        username=creds.username,
        password_raw=creds.password,
        ip_address=ip_address,
        user_agent=user_agent
    )
    if hasil["sukses"]:
        user_role = hasil["user"].get("role", "")
        token = bikin_token(hasil["user"]["username"], user_role)
        hasil["token"] = token
    return hasil



# ==========================================
# INTEGRATOR ROUTING
# ==========================================

@router.post("/integrator/routing_api", response_model=ResponseFormat)
async def routing_api(req: RequestFormat):
    start_time = time.time()

    rl_ok, rl_pesan = check_rate_limit(req.user_id)
    if not rl_ok:
        return ResponseFormat(status="gagal", data={"pesan": rl_pesan, "kode": "RATE_LIMIT"})

    token = req.parameter.get("token", "") if req.parameter else ""
    auth = verifikasi_token(token)
    if not auth["valid"]:
        return ResponseFormat(status="gagal", data={"pesan": auth["pesan"]})

    await catat_legacy_log(req.user_id, "/integrator/routing_api", req.parameter)

    raw_amount = req.parameter.get("amount", 0) if req.parameter else 0
    amount_valid, amount_pesan, amount = validasi_amount(raw_amount)
    if not amount_valid:
        return ResponseFormat(status="gagal", data={"pesan": amount_pesan, "kode": "INVALID_AMOUNT"})

    api_key = req.parameter.get("api_key", "") if req.parameter else ""
    fee = hitung_fee_gateway(amount)
    if api_key:
        akses = await cek_akses_app(api_key, "/integrator/routing_api")
        if not akses.get("boleh"):
            return ResponseFormat(status="gagal", data={"pesan": akses.get("pesan")})
        try:
            fee_persen = float(akses.get("fee_persen", 0))
            fee = amount * (fee_persen / 100.0)
        except Exception:
            fee = hitung_fee_gateway(amount)

    data_untuk_bank = {
        "user_id": req.user_id,
        "amount": amount,
        "fee_gateway": fee,
        "metadata": req.parameter
    }
    hasil_bank = await teruskan_ke_smartbank(data_untuk_bank)

    elapsed_ms = int((time.time() - start_time) * 1000)
    source_app = req.parameter.get("source_app", "unknown") if req.parameter else "unknown"
    smartbank_status = hasil_bank.get("status", "gagal") if isinstance(hasil_bank, dict) else "gagal"
    final_status = "sukses" if str(smartbank_status).lower() in ("sukses", "success", "ok") else "gagal"

    await catat_request_log(
        user_id=req.user_id,
        source_app=source_app,
        endpoint="/integrator/routing_api",
        amount=amount,
        fee=fee,
        jwt_valid=True,
        smartbank_status=smartbank_status,
        response_time_ms=elapsed_ms
    )

    if api_key and smartbank_status == "sukses":
        try:
            await kurangi_quota(api_key)
            await tambah_fee_app(api_key, fee)
        except Exception as e:
            print(f"[WARN] Gagal update quota/fee untuk {api_key}: {e}")

    return ResponseFormat(status=final_status, data={
        "integrator_note": "Request divalidasi & log tersimpan di MySQL",
        "fee_diambil": fee,
        "respons_dari_smartbank": hasil_bank
    })


@router.post("/integrator/validasi_request", response_model=ResponseFormat)
async def validasi_request(req: RequestFormat):
    token = req.parameter.get("token", "") if req.parameter else ""
    auth = verifikasi_token(token)
    if auth["valid"]:
        return ResponseFormat(status="sukses", data={"valid": True, "user_id": auth["user_id"], "pesan": auth["pesan"]})
    else:
        return ResponseFormat(status="gagal", data={"valid": False, "pesan": auth["pesan"]})


@router.post("/integrator/logging", response_model=ResponseFormat)
async def logging_request(req: RequestFormat):
    endpoint_target = req.parameter.get("endpoint", "/unknown") if req.parameter else "/unknown"
    await catat_legacy_log(req.user_id, endpoint_target, req.parameter)
    return ResponseFormat(status="sukses", data={
        "pesan": f"Log untuk user '{req.user_id}' berhasil dicatat di MySQL",
        "endpoint_dicatat": endpoint_target
    })


@router.post("/integrator/biaya_layanan_integrasi", response_model=ResponseFormat)
async def biaya_layanan(req: RequestFormat):
    amount = req.parameter.get("amount", 0) if req.parameter else 0
    fee = hitung_fee_gateway(amount)
    net_amount = amount - fee
    return ResponseFormat(status="sukses", data={
        "jumlah_transaksi": amount,
        "fee_gateway_persen": "0.5%",
        "fee_gateway_nominal": fee,
        "jumlah_diteruskan": net_amount,
        "keterangan": "Fee dipotong otomatis dari setiap transaksi via API Gateway"
    })


# ==========================================
# MONITOR
# ==========================================

@router.get("/monitor/request-stats")
async def request_stats():
    data = await get_request_stats()
    return {"status": "sukses", "data": data}


@router.get("/monitor/health-stats")
async def health_stats():
    data = await get_health_stats()
    return {"status": "sukses", "data": data}


@router.post("/monitor/health-check")
async def health_check_endpoint(req: RequestFormat):
    app_name = req.parameter.get("app_name", "unknown") if req.parameter else "unknown"
    endpoint = req.parameter.get("endpoint", "/") if req.parameter else "/"
    status = req.parameter.get("status", "offline") if req.parameter else "offline"
    response_time = req.parameter.get("response_time_ms", 0) if req.parameter else 0
    status_code = req.parameter.get("status_code", None) if req.parameter else None
    error_msg = req.parameter.get("error_message", None) if req.parameter else None

    await catat_health_log(
        app_name=app_name,
        endpoint=endpoint,
        status=status,
        response_time_ms=response_time,
        status_code=status_code,
        error_message=error_msg
    )
    return ResponseFormat(status="sukses", data={"pesan": f"Health log {app_name} berhasil dicatat"})


# ==========================================
# PRICING ENDPOINTS — Sistem Paket Harga
# ==========================================

@router.get("/paket/list")
async def list_paket():
    """Lihat semua paket harga yang tersedia"""
    data = await get_all_paket()
    return {"status": "sukses", "data": data}


@router.post("/apps/register", response_model=ResponseFormat)
async def register_app(req: RequestFormat):
    """Daftarkan app ke gateway & pilih paket langganan"""
    app_name = req.parameter.get("app_name", "") if req.parameter else ""
    nama_paket = req.parameter.get("nama_paket", "Starter") if req.parameter else "Starter"

    if not app_name:
        return ResponseFormat(status="gagal", data={"pesan": "'app_name' wajib diisi"})

    hasil = await registrasi_app(app_name, nama_paket)
    status = "sukses" if hasil.get("sukses") else "gagal"
    return ResponseFormat(status=status, data=hasil)


@router.get("/apps/status")
async def status_app(api_key: str):
    """Cek status langganan & quota tersisa sebuah app"""
    data = await get_status_app(api_key)
    if not data:
        return {"status": "gagal", "data": {"pesan": "API Key tidak ditemukan"}}
    return {"status": "sukses", "data": data}


@router.post("/apps/upgrade", response_model=ResponseFormat)
async def upgrade_app(req: RequestFormat):
    """Upgrade paket langganan app"""
    api_key = req.parameter.get("api_key", "") if req.parameter else ""
    paket_baru = req.parameter.get("paket_baru", "") if req.parameter else ""

    if not api_key or not paket_baru:
        return ResponseFormat(status="gagal", data={"pesan": "'api_key' dan 'paket_baru' wajib diisi"})

    hasil = await upgrade_paket_app(api_key, paket_baru)
    status = "sukses" if hasil.get("sukses") else "gagal"
    return ResponseFormat(status=status, data=hasil)


# ==========================================
# BACKTRACKING ROUTING — Algoritma Backtracking
# ==========================================

@router.post("/integrator/routing_backtracking", response_model=ResponseFormat)
async def routing_backtracking(req: RequestFormat):
    """
    Forward request ke app target menggunakan ALGORITMA BACKTRACKING.
    Jika route utama gagal, otomatis backtrack ke route alternatif.
    Wajib isi 'target_app' di parameter.
    """
    start_time = time.time()

    rl_ok, rl_pesan = check_rate_limit(req.user_id)
    if not rl_ok:
        return ResponseFormat(status="gagal", data={"pesan": rl_pesan, "kode": "RATE_LIMIT"})

    token = req.parameter.get("token", "") if req.parameter else ""
    auth = verifikasi_token(token)
    if not auth["valid"]:
        return ResponseFormat(status="gagal", data={"pesan": auth["pesan"]})

    target_app = req.parameter.get("target_app", "") if req.parameter else ""
    target_endpoint = req.parameter.get("target_endpoint", None) if req.parameter else None

    if not target_app:
        return ResponseFormat(status="gagal", data={
            "pesan": "'target_app' wajib diisi di dalam parameter.",
            "pilihan_app": ["smartbank", "marketplace", "pos", "supplierhub", "logistikita", "umkminsight"],
            "contoh": {
                "user_id": "user123",
                "parameter": {
                    "token": "...",
                    "target_app": "marketplace",
                    "amount": 50000
                }
            }
        })

    raw_amount = req.parameter.get("amount", 0) if req.parameter else 0
    amount_valid, amount_pesan, amount = validasi_amount(raw_amount)
    if not amount_valid:
        return ResponseFormat(status="gagal", data={"pesan": amount_pesan, "kode": "INVALID_AMOUNT"})

    fee = hitung_fee_gateway(amount)

    await catat_legacy_log(req.user_id, f"/integrator/routing_backtracking → {target_app}", req.parameter)

    data_forward = {
        "user_id": req.user_id,
        "amount": amount,
        "fee_gateway": fee,
        "source": "API_Gateway_Backtracking",
        "metadata": req.parameter
    }
    hasil = await route_dengan_backtracking(target_app, data_forward, target_endpoint)

    elapsed_ms = int((time.time() - start_time) * 1000)

    await catat_backtracking_log(
        user_id=req.user_id,
        target_app=target_app,
        total_candidates=hasil.get("total_candidates", len(hasil.get("trace", []))),
        total_attempts=hasil.get("total_attempts", 0),
        route_used=hasil.get("route_used", "none"),
        final_status=hasil.get("status", "gagal"),
        trace=hasil.get("trace", []),
        response_time_ms=elapsed_ms
    )

    return ResponseFormat(status=hasil.get("status", "gagal"), data={
        "algoritma": "backtracking",
        "target_app": target_app,
        "route_used": hasil.get("route_used", "none"),
        "route_url": hasil.get("route_url", "-"),
        "total_attempts": hasil.get("total_attempts", 0),
        "total_candidates": hasil.get("total_candidates", 0),
        "fee_gateway": fee,
        "response_time_ms": elapsed_ms,
        "trace": hasil.get("trace", []),
        "respons_dari_app": hasil.get("data", hasil.get("pesan", "No response"))
    })


@router.get("/monitor/backtracking-stats")
async def backtracking_stats_endpoint():
    """Lihat statistik dan riwayat backtracking routing"""
    data = await get_backtracking_stats()
    return {"status": "sukses", "data": data}


@router.get("/integrator/route-candidates")
async def route_candidates_endpoint():
    """Lihat routing table backtracking — semua candidates per app"""
    data = await get_route_candidates_info_with_plugins()
    return {"status": "sukses", "data": data}


# ==========================================
# ROUTING UNIVERSAL — Kirim ke App Manapun
# ==========================================

@router.get("/integrator/plugins")
async def list_plugins(include_inactive: bool = True):
    data = await list_api_plugins(include_inactive=include_inactive)
    return api_success(data={"total": len(data), "plugins": data}, message="Daftar API plugin berhasil diambil.")


@router.post("/integrator/plugins")
async def create_plugin(payload: ApiPluginPayload):
    try:
        plugin = await save_api_plugin(payload.model_dump())
        return api_success(data=plugin, message="API plugin berhasil disimpan.")
    except ValueError as exc:
        return api_error(str(exc), code="VALIDATION_ERROR", status_code=422)
    except Exception as exc:
        return api_error("Gagal menyimpan API plugin.", code="PLUGIN_SAVE_FAILED", status_code=500, details={"error": str(exc)})


@router.put("/integrator/plugins/{plugin_id}")
async def edit_plugin(plugin_id: int, payload: ApiPluginPayload):
    try:
        plugin = await update_api_plugin(plugin_id, payload.model_dump())
        if not plugin:
            return api_error("API plugin tidak ditemukan.", code="PLUGIN_NOT_FOUND", status_code=404)
        return api_success(data=plugin, message="API plugin berhasil diperbarui.")
    except ValueError as exc:
        return api_error(str(exc), code="VALIDATION_ERROR", status_code=422)
    except Exception as exc:
        return api_error("Gagal memperbarui API plugin.", code="PLUGIN_UPDATE_FAILED", status_code=500, details={"error": str(exc)})


@router.delete("/integrator/plugins/{plugin_id}")
async def remove_plugin(plugin_id: int):
    deleted = await delete_api_plugin(plugin_id)
    if not deleted:
        return api_error("API plugin tidak ditemukan.", code="PLUGIN_NOT_FOUND", status_code=404)
    return api_success(data={"deleted": True, "id": plugin_id}, message="API plugin berhasil dihapus.")


@router.post("/integrator/plugins/{plugin_id}/test")
async def test_plugin_connection(plugin_id: int):
    plugin = await get_api_plugin(plugin_id, include_secret=True)
    if not plugin:
        return api_error("API plugin tidak ditemukan.", code="PLUGIN_NOT_FOUND", status_code=404)
    result = await test_api_plugin(plugin)
    return api_success(data=result, message="Test koneksi API plugin selesai.")


@router.post("/integrator/routing_universal", response_model=ResponseFormat)
async def routing_universal(req: RequestFormat):
    """
    Forward request ke app UMKM manapun via Gateway.
    Wajib isi 'target_app' di parameter (smartbank/marketplace/pos/supplierhub/logistikita/umkminsight).
    Opsional: 'target_endpoint' untuk override endpoint default.
    """
    start_time = time.time()

    rl_ok, rl_pesan = check_rate_limit(req.user_id)
    if not rl_ok:
        return ResponseFormat(status="gagal", data={"pesan": rl_pesan, "kode": "RATE_LIMIT"})

    token = req.parameter.get("token", "") if req.parameter else ""
    if not token or token in ("SYSTEM_AUTO_SYNC", "system", "auto"):
        auth = {"valid": True, "pesan": "System Auto Sync"}
    else:
        auth = verifikasi_token(token)
    if not auth["valid"]:
        return ResponseFormat(status="gagal", data={"pesan": auth["pesan"]})

    target_app = req.parameter.get("target_app", "") if req.parameter else ""
    target_endpoint = req.parameter.get("target_endpoint", None) if req.parameter else None

    if not target_app:
        return ResponseFormat(status="gagal", data={
            "pesan": "'target_app' wajib diisi di dalam parameter.",
            "pilihan_app": ["smartbank", "marketplace", "pos", "supplierhub", "logistikita", "umkminsight"],
            "contoh": {"user_id": "user123", "parameter": {"token": "...", "target_app": "marketplace", "amount": 50000}}
        })

    raw_amount = req.parameter.get("amount", 0) if req.parameter else 0
    amount_valid, amount_pesan, amount = validasi_amount(raw_amount)
    if not amount_valid:
        return ResponseFormat(status="gagal", data={"pesan": amount_pesan, "kode": "INVALID_AMOUNT"})

    fee = hitung_fee_gateway(amount)

    await catat_legacy_log(req.user_id, f"/integrator/routing_universal → {target_app}", req.parameter)

    data_forward = {
        "user_id": req.user_id,
        "amount": amount,
        "fee_gateway": fee,
        "source": "API_Gateway",
        "metadata": req.parameter
    }
    hasil = await teruskan_ke_app(target_app, data_forward, target_endpoint)

    elapsed_ms = int((time.time() - start_time) * 1000)
    target_status = hasil.get("status", "gagal") if isinstance(hasil, dict) else "gagal"
    final_status = "sukses" if str(target_status).lower() in ("sukses", "success", "ok") else "gagal"
    source_app    = req.parameter.get("source_app", "unknown") if req.parameter else "unknown"

    await catat_request_log(
        user_id=req.user_id,
        source_app=source_app,
        endpoint=f"/integrator/routing_universal → {target_app}",
        amount=amount,
        fee=fee,
        jwt_valid=True,
        smartbank_status=target_status,
        response_time_ms=elapsed_ms
    )

    return ResponseFormat(status=final_status, data={
        "target_app": target_app,
        "target_endpoint": target_endpoint or f"(default {target_app})",
        "fee_gateway": fee,
        "fee_persen": "0.5%",
        "response_time_ms": elapsed_ms,
        "respons_dari_app": hasil
    })


@router.post("/integrator/proxy")
async def proxy_request(req: ProxyRequest, request: Request):
    """
    Endpoint JSON standar untuk meneruskan request ke aplikasi lain.
    Dipakai untuk integrasi antar-aplikasi tanpa bergantung pada halaman frontend.
    """
    start_time = time.time()
    method = req.method.upper()

    if method != "POST":
        return api_error(
            "Saat ini proxy integrasi hanya menerima method POST.",
            code="METHOD_NOT_ALLOWED",
            status_code=405,
            details={"allowed_methods": ["POST"]},
        )

    if not req.target_app:
        return api_error(
            "Field target_app wajib diisi.",
            code="VALIDATION_ERROR",
            status_code=422,
        )

    payload = req.payload or {}
    if req.user_id and "user_id" not in payload:
        payload["user_id"] = req.user_id

    hasil = await teruskan_ke_app(req.target_app, payload, req.target_endpoint)
    elapsed_ms = int((time.time() - start_time) * 1000)
    upstream_status = "success" if isinstance(hasil, dict) and hasil.get("status") in ("success", "sukses") else "error"
    http_status = 200 if upstream_status == "success" else int(hasil.get("http_status", 502)) if isinstance(hasil, dict) else 502

    await catat_request_log(
        user_id=req.user_id or payload.get("user_id", "external"),
        source_app=request.headers.get("x-source-app", "api-integrator"),
        endpoint=f"/integrator/proxy -> {req.target_app}",
        amount=float(payload.get("amount", 0) or 0),
        fee=0,
        jwt_valid=True,
        smartbank_status="sukses" if upstream_status == "success" else "gagal",
        response_time_ms=elapsed_ms,
    )

    if upstream_status == "success":
        return api_success(
            data={
                "target_app": req.target_app,
                "target_endpoint": req.target_endpoint,
                "upstream_response": hasil,
            },
            message="Request berhasil diteruskan.",
            meta={"service": "api-integrator", "response_time_ms": elapsed_ms},
        )

    return api_error(
        "Request gagal diteruskan ke aplikasi tujuan.",
        code="UPSTREAM_ERROR",
        status_code=http_status if 400 <= http_status <= 599 else 502,
        details={
            "target_app": req.target_app,
            "target_endpoint": req.target_endpoint,
            "upstream_response": hasil,
            "response_time_ms": elapsed_ms,
        },
    )


@router.get("/integrator/daftar_route")
async def daftar_route():
    """
    Tampilkan routing table — daftar semua app yang terdaftar di Gateway.
    Berguna untuk dokumentasi integrasi dan debug koneksi antar service.
    """
    apps = await get_daftar_app_with_plugins()
    return {
        "status": "sukses",
        "data": {
            "total_app": len(apps),
            "daftar_app": apps,
            "catatan": "Ganti base_url sesuai IP/port teman kelompok saat integrasi.",
            "endpoint_universal": "POST /integrator/routing_universal"
        }
    }


# ==========================================
# FINANCIAL LEDGER — Pembukuan & Neraca
# ==========================================

@router.get("/api/financial/summary")
async def financial_summary_endpoint(
    current_user: dict = Depends(cek_role(["admin", "operator"]))
):
    """Ambil ringkasan keuangan: Laba Rugi & Neraca"""
    data = await get_financial_summary()
    if "error" in data:
        return {"status": "gagal", "data": data}
    return {"status": "sukses", "data": data}


@router.get("/api/financial/ledger")
async def financial_ledger_endpoint(
    current_user: dict = Depends(cek_role(["admin", "operator"]))
):
    """Ambil daftar semua transaksi buku besar"""
    data = await get_financial_ledger()
    return {"status": "sukses", "data": data}


@router.post("/api/financial/entry")
async def financial_add_entry(
    entry: FinancialEntrySchema,
    current_user: dict = Depends(cek_role(["admin", "operator"]))
):
    """Tambah transaksi keuangan baru (Admin & Operator)"""
    valid_tipe = ["pemasukan", "pengeluaran", "aset", "kewajiban"]
    if entry.tipe not in valid_tipe:
        return {"status": "gagal", "data": {"pesan": f"Tipe harus salah satu dari: {', '.join(valid_tipe)}"}}
    if entry.jumlah <= 0:
        return {"status": "gagal", "data": {"pesan": "Jumlah harus lebih dari 0"}}

    hasil = await add_financial_entry(
        tipe=entry.tipe,
        kategori=entry.kategori,
        deskripsi=entry.deskripsi,
        jumlah=entry.jumlah,
        user_id=entry.user_id,
        tanggal=entry.tanggal
    )
    status = "sukses" if hasil.get("sukses") else "gagal"
    return {"status": status, "data": hasil}


@router.delete("/api/financial/entry/{entry_id}")
async def financial_delete_entry(
    entry_id: int,
    current_user: dict = Depends(cek_role(["admin"]))
):
    """Hapus transaksi keuangan (Admin only)"""
    hasil = await delete_financial_entry(entry_id)
    status = "sukses" if hasil.get("sukses") else "gagal"
    return {"status": status, "data": hasil}


# ==========================================
# UMKM INSIGHT INTEGRATION ENDPOINTS
# Endpoint khusus untuk menerima koneksi dari UMKM Insight (PHP Legacy)
# PHP memanggil via: integrator_proxy.php?action=umkm-status / send-transaksi / push-data
# ==========================================

@router.get("/integrator/umkm-insight/status")
async def umkm_insight_status():
    """
    Cek status koneksi dari UMKM Insight.
    Dipanggil oleh PHP legacy UMKM Insight via integrator_proxy.php?action=umkm-status
    """
    import datetime
    return {
        "status": "sukses",
        "data": {
            "service": "api-integrator",
            "connected_from": "umkm-insight-php",
            "integration_mode": "php-legacy-to-python-fastapi",
            "available_endpoints": [
                {
                    "method": "GET",
                    "path": "/integrator/umkm-insight/status",
                    "description": "Cek status koneksi dari UMKM Insight"
                },
                {
                    "method": "POST",
                    "path": "/integrator/umkm-insight/push-transaksi",
                    "description": "Terima data transaksi/insight dari UMKM Insight"
                },
                {
                    "method": "GET",
                    "path": "/integrator/daftar_route",
                    "description": "Lihat routing table semua app ekosistem"
                },
                {
                    "method": "POST",
                    "path": "/integrator/routing_api",
                    "description": "Kirim transaksi via Gateway ke SmartBank"
                },
            ],
            "timestamp": datetime.datetime.utcnow().isoformat() + "Z",
            "version": "1.0",
        },
        "message": "API Integrator siap menerima koneksi dari UMKM Insight"
    }


class UmkmInsightPushPayload(BaseModel):
    user_id: Optional[str] = None
    amount: Optional[float] = 0.0
    token: Optional[str] = ""
    source: Optional[str] = "umkm-insight"
    metadata: Optional[dict] = {}
    insight_data: Optional[list] = []


@router.post("/integrator/umkm-insight/push-transaksi")
async def umkm_insight_push_transaksi(payload: UmkmInsightPushPayload, request: Request):
    """
    Terima data transaksi / insight data dari UMKM Insight (PHP Legacy).
    Kemudian teruskan ke SmartBank via Gateway jika ada amount > 0 dan token valid.

    Dipanggil oleh PHP:
      POST /api/integrator_proxy.php?action=send-transaksi
      POST /api/integrator_proxy.php?action=push-data
    """
    import time as _time
    start_time = _time.time()

    user_id      = payload.user_id or "umkm-insight-user"
    amount       = float(payload.amount or 0)
    token        = payload.token or ""
    source       = payload.source or "umkm-insight"
    metadata     = payload.metadata or {}
    insight_data = payload.insight_data or []

    # Catat log bahwa request masuk dari UMKM Insight
    await catat_legacy_log(
        user_id,
        "/integrator/umkm-insight/push-transaksi",
        {
            "source": source,
            "amount": amount,
            "metadata": metadata,
            "insight_data_count": len(insight_data),
        }
    )

    result = {
        "diterima_dari": "umkm-insight-php",
        "user_id": user_id,
        "source": source,
        "insight_data_count": len(insight_data),
        "smartbank_response": None,
        "fee_gateway": None,
        "note": "",
    }

    # Jika ada amount dan token, teruskan ke SmartBank via gateway
    if amount > 0 and token:
        auth = verifikasi_token(token)
        if not auth.get("valid"):
            elapsed_ms = int((_time.time() - start_time) * 1000)
            return {
                "status": "gagal",
                "data": {
                    **result,
                    "note": f"Token tidak valid: {auth.get('pesan', 'Unknown error')}",
                    "response_time_ms": elapsed_ms,
                }
            }

        fee = hitung_fee_gateway(amount)
        data_untuk_bank = {
            "user_id": user_id,
            "amount": amount,
            "fee_gateway": fee,
            "source": "umkm-insight-via-gateway",
            "metadata": {
                **metadata,
                "original_source": source,
                "insight_data_count": len(insight_data),
            }
        }
        hasil_bank = await teruskan_ke_smartbank(data_untuk_bank)
        elapsed_ms = int((_time.time() - start_time) * 1000)

        smartbank_status = hasil_bank.get("status", "gagal") if isinstance(hasil_bank, dict) else "gagal"
        await catat_request_log(
            user_id=user_id,
            source_app=source,
            endpoint="/integrator/umkm-insight/push-transaksi",
            amount=amount,
            fee=fee,
            jwt_valid=True,
            smartbank_status=smartbank_status,
            response_time_ms=elapsed_ms
        )

        result["smartbank_response"] = hasil_bank
        result["fee_gateway"] = fee
        result["note"] = "Transaksi diteruskan ke SmartBank via API Gateway"

        return {
            "status": "sukses",
            "data": {
                **result,
                "response_time_ms": elapsed_ms,
            },
            "message": "Data dari UMKM Insight berhasil diterima dan diteruskan ke SmartBank"
        }

    # Jika hanya insight data (tanpa transaksi ke SmartBank)
    elapsed_ms = int((_time.time() - start_time) * 1000)
    result["note"] = (
        "Data insight diterima dan di-log. Tidak ada transaksi ke SmartBank "
        "(amount = 0 atau token kosong)."
    )

    return {
        "status": "sukses",
        "data": {
            **result,
            "response_time_ms": elapsed_ms,
        },
        "message": "Data insight dari UMKM Insight berhasil diterima oleh API Integrator"
    }
