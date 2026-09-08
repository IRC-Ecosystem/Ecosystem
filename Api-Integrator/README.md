# 🔌 API Integrator — Central Ecosystem Gateway

API Integrator adalah **Central API Gateway & Router Orchestrator** dalam Ekosistem Ekonomi Digital UMKM RPL II. Gateway ini bertindak sebagai jembatan otomatis di balik layar untuk menghubungkan seluruh aplikasi (SmartBank, Marketplace, WarungPOS, SupplierHub, LogistiKita, dan UMKM Insight).

---

## ⚡ Fitur Utama Gateway

- **Otomatisasi Ekosistem (Background Router)**: Meneruskan transaksi secara backend-to-backend dari aplikasi asal (misal SmartBank) langsung ke UMKM Insight tanpa perlu interaksi manual user.
- **Dynamic API Plugins Manager**: Pendaftaran & manajemen API aplikasi baru (termasuk aplikasi ke-7 dan seterusnya) dapat dilakukan secara visual melalui UI tanpa perlu mengubah satu baris pun kode program backend Python!
- **Universal Routing**: Router universal yang mendukung validasi JWT, rate-limiting, audit logging ke MySQL, dan pemotongan biaya layanan gateway secara otomatis (fee 0.5%).
- **Token System & Testing Playground**: Menyediakan generator token JWT dan UI Sandbox/Playground di `http://localhost:3007` untuk pengujian developer.

---

## 🌐 URL Akses (Docker)

Dari root directory `Ecosystem RPL II`:

```powershell
docker compose up --build -d
```

- **Portal Gateway UI / Sandbox**: `http://localhost:3007`
- **Manajemen API Plugins Dinamis**: `http://localhost:3007/integrator/plugins/manage`
- **Status Ekosistem & Monitor**: `http://localhost:3007/monitor`
- **API Backend FastAPI**: `http://localhost:4001`
- **Database MySQL**: `localhost:3311` (Database: `TugasGateaway`)

---

## 🧩 Manajemen API Plugin Dinamis (Tanpa Coding)

Aplikasi baru dapat didaftarkan langsung via UI Web atau REST API:

- **UI Web**: `http://localhost:3007/integrator/plugins/manage`
- **List All Plugins**: `GET /integrator/plugins`
- **Tambah Plugin Baru**: `POST /integrator/plugins`
- **Edit Plugin**: `PUT /integrator/plugins/{plugin_id}`
- **Hapus Plugin**: `DELETE /integrator/plugins/{plugin_id}`
- **Uji Koneksi Plugin**: `POST /integrator/plugins/{plugin_id}/test`

Setiap plugin baru yang disimpan ke tabel `api_plugins` akan langsung aktif secara *real-time* di fungsi Universal Routing tanpa perlu merestart server.

---

## 🔌 Core API Endpoints

| Method | Endpoint | Fungsi |
|---|---|---|
| `GET` | `/health` | Health check server gateway |
| `GET` | `/generate_token_tester/{user_id}` | Penerbitan token JWT untuk testing |
| `POST` | `/integrator/routing_api` | Forward transaksi pembayaran ke SmartBank Gateway |
| `POST` | `/integrator/routing_universal` | **Universal Forwarding**: Forward data transaksi ke aplikasi target (`umkminsight`, `marketplace`, `pos`, dll) |
| `GET` | `/integrator/daftar_route` | Menampilkan routing table aktif |

---

## 🛠️ Setup Manual (Non-Docker)

```powershell
Set-Location Api-Integrator
python -m venv venv
.\venv\Scripts\Activate.ps1
pip install -r requirements.txt
uvicorn main:app --reload --host 0.0.0.0 --port 4001
```

---

## 📁 Struktur Folder Project

```text
Api-Integrator/
├── app/              # Core logic FastAPI, router, validator, dan middleware rate-limit
├── frontend/         # UI static HTML/CSS/JS (landing page, dashboard, plugin manager)
├── models/           # Schema validation Pydantic (requests & responses)
├── services/         # Dynamic plugin service, routing engine, JWT service, & MySQL logger
├── main.py           # Bootstrap FastAPI application
├── requirements.txt  # Python dependencies
├── Dockerfile
└── Context/          # Dokumen requirement & spesifikasi ekosistem
```
