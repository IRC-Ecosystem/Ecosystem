import os

from dotenv import load_dotenv
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles

from app.core.responses import api_success
from app.core.settings import settings
from app.routers.api import router as api_router
from app.routers.pages import router as pages_router
from services.log_service import close_pool, get_conn, init_db

load_dotenv()

app = FastAPI(title=settings.app_name)

app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.cors_origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

frontend_dir = os.path.join(os.path.dirname(__file__), "frontend")
app.mount("/static", StaticFiles(directory=frontend_dir), name="static")
app.include_router(pages_router)
app.include_router(api_router)


@app.on_event("startup")
async def startup():
    await init_db()

@app.on_event("shutdown")
async def shutdown():
    await close_pool()


@app.get("/health")
async def health():
    return api_success(data={"service": "api-integrator", "status": "alive"}, message="API Integrator is alive")

@app.get("/ready")
async def ready():
    conn = await get_conn()
    try:
        async with conn.cursor() as cursor:
            await cursor.execute("SELECT 1")
    finally:
        conn.close()
    return api_success(data={"service": "api-integrator", "database": "connected"}, message="API Integrator is ready")
