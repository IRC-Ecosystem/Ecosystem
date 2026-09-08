from pydantic import BaseModel
from typing import Optional, Dict, Any

# Ini aturan baku buat data yang MASUK dari aplikasi lain
class RequestFormat(BaseModel):
    user_id: str
    parameter: Optional[Dict[str, Any]] = None

# Ini aturan baku buat data yang KELUAR dari Gateway lu
class ResponseFormat(BaseModel):
    status: str
    success: Optional[bool] = None
    message: Optional[str] = None
    data: Optional[Dict[str, Any]] = None
    error: Optional[Dict[str, Any]] = None
    meta: Optional[Dict[str, Any]] = None


class ProxyRequest(BaseModel):
    target_app: str
    target_endpoint: Optional[str] = None
    method: str = "POST"
    payload: Optional[Dict[str, Any]] = None
    user_id: Optional[str] = None


class ApiPluginPayload(BaseModel):
    app_id: str
    app_name: str
    base_url: str
    default_endpoint: str = "/"
    method: str = "POST"
    health_endpoint: Optional[str] = "/health"
    auth_header_name: Optional[str] = None
    auth_header_value: Optional[str] = None
    timeout_seconds: int = 10
    is_active: bool = True
    description: Optional[str] = None
