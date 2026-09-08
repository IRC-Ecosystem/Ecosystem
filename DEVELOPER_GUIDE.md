# Eco Developer Guide

## Prerequisites

- Docker Desktop menggunakan Linux containers dan WSL2.
- Laptop 4 GB RAM: Docker Desktop terdeteksi dengan limit sekitar 3.57 GiB. Tutup aplikasi berat sebelum build atau menjalankan profile tambahan.
- File `.env` lokal dibuat dari `.env.example`; setiap `change_me_*` harus diganti secret acak. Jangan commit `.env`.

## Configure Secrets

```powershell
Copy-Item .env.example .env
# Isi semua change_me_* sebelum menjalankan Compose.
docker compose config --quiet
```

## Default Core Stack

Stack default menjalankan MySQL, Central Bank, UMKM Insight, dan Connector. Build/start serial menurunkan lonjakan RAM.

```powershell
docker compose --parallel 1 up -d --build --wait
docker compose ps
```

Core endpoint:

```powershell
curl.exe -fsS http://localhost:3000/api/v1/health
curl.exe -fsS http://localhost:5000/ready
curl.exe -fsS http://localhost:3006/api/health.php
```

## Domain Profiles

Aktifkan satu profile sesuai pekerjaan. Compose otomatis juga memulai dependensi yang diperlukan.

| Profile | Services | Start command |
|---|---|---|
| `ui` | Wallet, Gateway, SmartBank UI, Central Bank UI | `docker compose --parallel 1 --profile ui up -d --build --wait` |
| `pos` | POS, Logistika | `docker compose --parallel 1 --profile pos up -d --build --wait` |
| `marketplace` | Marketplace | `docker compose --parallel 1 --profile marketplace up -d --build --wait` |
| `supplier` | SupplierHub, Logistika | `docker compose --parallel 1 --profile supplier up -d --build --wait` |
| `integrator` | API Integrator, SupplierHub, Logistika | `docker compose --parallel 1 --profile integrator up -d --build --wait` |

Untuk seluruh stack:

```powershell
docker compose --parallel 1 --profile ui --profile pos --profile marketplace --profile supplier --profile integrator up -d --build --wait
```

Seluruh service memiliki limit CPU/RAM Compose. Jumlah ceiling maksimum `3200 MiB`; limit bukan jaminan aplikasi tidak OOM saat migrasi atau beban tinggi.

## Verify Services

```powershell
docker compose ps
docker stats --no-stream
curl.exe -fsS http://localhost:3000/api/v1/health
curl.exe -fsS http://localhost:5000/ready
```

Profile `ui`:

```powershell
curl.exe -fsS http://localhost:6969/health
curl.exe -fsS http://localhost:4000/health
curl.exe -fsS http://localhost:3001/
curl.exe -fsS http://localhost:5173/
```

Profile lain memakai endpoint pada [dokumentasi.md](dokumentasi.md#arsitektur-integrasi).

## Measure Memory

```powershell
docker stats --no-stream
docker inspect --format '{{.Name}} memory={{.HostConfig.Memory}} cpu={{.HostConfig.NanoCpus}}' eco-mysql-1 eco-central-bank-1 eco-umkm-insight-1 eco-connector-1
```

Jika sebuah service dihentikan OOM, periksa `docker inspect <container>` untuk `OOMKilled`, naikkan hanya `mem_limit` service tersebut, lalu ulangi profile terkait.

## Troubleshoot Startup Failures

Urutan startup wajib mengikuti healthcheck. Bila Central Bank, Connector, POS, atau API Integrator gagal setelah MySQL restart:

```powershell
docker compose ps
docker compose logs --tail=100 mysql central-bank connector pos api-integrator
docker compose up -d --wait central-bank connector
```

Jangan menyembunyikan error dengan menghapus healthcheck. Periksa terlebih dahulu koneksi MySQL (`mysql:3306`), lalu ulangi start serial. POS perlu profile `pos`; API Integrator perlu profile `integrator`.

## Stop and Reset

Hentikan stack tanpa menghapus data:

```powershell
docker compose down
```

Reset data MySQL lokal secara eksplisit:

```powershell
docker compose down -v
```

`down -v` menghapus `mysql_prod_data` dan tidak dapat dibatalkan.

## Disk Cleanup

Periksa penggunaan disk:

```powershell
docker system df
```

`docker builder prune` dan `docker system prune` dapat menghapus cache atau resource Docker yang masih diperlukan proyek lain. Tinjau output dan konfirmasi scope sebelum menjalankannya.
