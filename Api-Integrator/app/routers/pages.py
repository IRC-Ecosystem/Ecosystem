import os

from fastapi import APIRouter
from fastapi.responses import FileResponse

router = APIRouter()

FRONTEND_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "..", "frontend"))


def page(filename: str):
    return FileResponse(os.path.join(FRONTEND_DIR, filename))


@router.get("/")
def serve_landing():
    return page("landing.html")


@router.get("/login")
def serve_login():
    return page("login.html")


@router.get("/dashboard")
def serve_dashboard():
    return page("index.html")


@router.get("/monitor")
def serve_monitor():
    return page("api_monitor.html")


@router.get("/apps/manage")
def serve_apps_manage():
    return page("apps.html")


@router.get("/integrator/manage/routing")
def serve_routing_manage():
    return page("routing.html")


@router.get("/integrator/plugins/manage")
def serve_plugins_manage():
    return page("plugins.html")


@router.get("/pricing/manage")
def serve_pricing_manage():
    return page("pricing.html")


@router.get("/token/manage")
def serve_token_manage():
    return page("token.html")


@router.get("/integrator/fee/manage")
def serve_fee_manage():
    return page("fee.html")


@router.get("/integrator/logging/manage")
def serve_logging_manage():
    return page("logging.html")


@router.get("/monitor/health-check/manage")
def serve_health_check_manage():
    return page("health_check.html")


@router.get("/integrator/backtracking/manage")
def serve_backtracking_manage():
    return page("backtracking.html")


@router.get("/integrator/pembukuan")
def serve_pembukuan():
    return page("pembukuan.html")
