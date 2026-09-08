<?php
class Integration extends Controllers {
    public function logistics() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); return; }
        header('Content-Type: application/json');
        try {
            $raw=file_get_contents('php://input');
            $headers=['X-B2BLink-Event-Id'=>$_SERVER['HTTP_X_B2BLINK_EVENT_ID']??'','X-B2BLink-Signature'=>$_SERVER['HTTP_X_B2BLINK_SIGNATURE']??''];
            echo json_encode(['success'=>true,'data'=>$this->model('Logistics_model')->receiveCallback($raw,$headers)]);
        } catch(Throwable $error) { http_response_code(401); echo json_encode(['success'=>false,'error'=>['code'=>'CALLBACK_REJECTED','message'=>'Callback ditolak']]); }
    }
}
