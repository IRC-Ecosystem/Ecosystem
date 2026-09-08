# Arsitektur API Integrator

Runtime aktif memakai FastAPI, MySQL, static HTML/CSS/JS, dan Docker.

## Alur Modul

```text
Browser/API client -> FastAPI router -> service -> MySQL / service eksternal
```

- `main.py`: bootstrap FastAPI, CORS, static files, router, dan startup DB.
- `app/routers/pages.py`: route halaman UI legacy.
- `app/routers/api.py`: endpoint API gateway.
- `app/core`: helper konfigurasi, response JSON, validasi, dan rate limit.
- `services`: JWT, routing, logging, health check, pricing, dan akses database.
- `models`: schema Pydantic untuk kontrak request/response.
- `frontend`: tampilan HTML/CSS/JS legacy yang aktif digunakan.

## Prinsip

- UI legacy disajikan langsung oleh FastAPI agar tidak perlu service frontend tambahan.
- API dan UI tetap bisa diakses lewat port berbeda sesuai `Setting PORT.txt`.
- Logic integrasi berada di service Python, bukan di file HTML.
- Endpoint JSON memakai helper response agar bentuk response lebih konsisten.
