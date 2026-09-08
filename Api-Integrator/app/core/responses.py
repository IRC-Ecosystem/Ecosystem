from datetime import datetime, timezone

from fastapi.responses import JSONResponse


def api_success(data=None, message="OK", status_code=200, meta=None):
    response_meta = {
        "service": "api-integrator",
        "timestamp": datetime.now(timezone.utc).isoformat(),
    }
    if meta:
        response_meta.update(meta)
    return JSONResponse(
        status_code=status_code,
        content={
            "success": True,
            "status": "success",
            "message": message,
            "data": data or {},
            "error": None,
            "meta": response_meta,
        },
    )


def api_error(message, code="BAD_REQUEST", status_code=400, details=None):
    return JSONResponse(
        status_code=status_code,
        content={
            "success": False,
            "status": "error",
            "message": message,
            "data": None,
            "error": {"code": code, "details": details or {}},
            "meta": {
                "service": "api-integrator",
                "timestamp": datetime.now(timezone.utc).isoformat(),
            },
        },
    )

