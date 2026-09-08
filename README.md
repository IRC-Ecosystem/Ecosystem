# Eco Docker

Jalankan inti ekosistem Eco pada laptop RAM 4 GB:

```powershell
Copy-Item .env.example .env
# Isi seluruh nilai change_me_* di .env. Jangan commit file ini.
docker compose --parallel 1 up -d --build --wait
```

Stack default: MySQL, Central Bank, UMKM Insight, dan Connector. Build dibuat serial agar penggunaan RAM tidak melonjak.

Tambahkan satu domain saat diperlukan:

```powershell
docker compose --parallel 1 --profile ui up -d --build --wait
docker compose --parallel 1 --profile pos up -d --build --wait
docker compose --parallel 1 --profile marketplace up -d --build --wait
docker compose --parallel 1 --profile supplier up -d --build --wait
docker compose --parallel 1 --profile integrator up -d --build --wait
```

`ui`: Wallet, Gateway, SmartBank UI, Central Bank UI. `pos`: POS dan Logistika. `marketplace`: Marketplace. `supplier`: SupplierHub dan Logistika. `integrator`: API Integrator, SupplierHub, dan Logistika.

Endpoints inti: Central Bank `http://localhost:3000`, Connector `http://localhost:5000`, UMKM Insight `http://localhost:3006`. Endpoint domain tersedia setelah profilnya dijalankan.

Data MySQL tersimpan di volume `mysql_prod_data`. Hentikan layanan dengan `docker compose down`. `docker compose down -v` menghapus seluruh data lokal. Detail lifecycle, troubleshooting, dan monitoring RAM: [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md).
