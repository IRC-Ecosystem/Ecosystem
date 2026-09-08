<?php
class Logistics_model {
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public function dispatchPending(int $limit = 20): array {
        $rows = $this->db->query('SELECT * FROM integration_outbox WHERE status IN ("pending","failed") AND available_at<=NOW() ORDER BY id LIMIT '.min(100,max(1,$limit)))->fetchAll();
        $result = ['published'=>0,'failed'=>0];
        foreach ($rows as $row) {
            try {
                $payload = json_decode($row['payload'], true, 512, JSON_THROW_ON_ERROR);
                $eventType = $row['event_type'] ?? '';

                if ($eventType === 'MARKETPLACE_ORDER_PAID') {
                    $response = $this->postLogistika('/api/v1/shipments', [
                        'external_id'=>'marketplace-order-'.$row['aggregate_id'], 'source'=>'marketplace', 'order_id'=>(int)$row['aggregate_id'],
                        'origin'=>$payload['origin'], 'destination'=>$payload['destination'],
                    ]);
                    $this->db->prepare('UPDATE integration_outbox SET status="published",published_at=NOW(),last_error=NULL WHERE id=?')->execute([$row['id']]);
                    $this->db->prepare('INSERT INTO shipments (order_id,tracking_reference,status) VALUES (?, ?, "created") ON DUPLICATE KEY UPDATE tracking_reference=VALUES(tracking_reference),status="created"')->execute([$row['aggregate_id'],$response['data']['tracking_reference']]);
                    $result['published']++;
                } elseif ($eventType === 'UMKM_INSIGHT_PAYMENT_SETTLED') {
                    if (UMKM_INSIGHT_EVENTS_URL !== '') {
                        $this->postInsight('/api/events.php', [
                            'source' => 'marketplace',
                            'event_id' => $row['event_id'],
                            'event_type' => 'payment.settled',
                            'occurred_at' => $payload['occurred_at'] ?? date('c'),
                            'data' => [
                                'order_id' => (int) $row['aggregate_id'],
                                'order_code' => $payload['order_code'] ?? '',
                                'amount' => (int) round((float) ($payload['amount'] ?? 0)),
                            ],
                        ]);
                    }
                    $this->db->prepare('UPDATE integration_outbox SET status="published",published_at=NOW(),last_error=NULL WHERE id=?')->execute([$row['id']]);
                    $result['published']++;
                } else {
                    // Unknown event type
                    $this->db->prepare('UPDATE integration_outbox SET status="published",published_at=NOW(),last_error=NULL WHERE id=?')->execute([$row['id']]);
                    $result['published']++;
                }
            } catch (Throwable $error) {
                $this->db->prepare('UPDATE integration_outbox SET status=IF(attempts+1>=max_attempts,"dead_letter","failed"),attempts=attempts+1,last_error=?,available_at=DATE_ADD(NOW(),INTERVAL LEAST(3600,30*POW(2,attempts)) SECOND) WHERE id=?')->execute([substr($error->getMessage(),0,500),$row['id']]);
                $result['failed']++;
            }
        }
        return $result;
    }

    public function receiveCallback(string $raw, array $headers): array {
        $eventId = trim($headers['X-B2BLink-Event-Id'] ?? '');
        $provided = preg_replace('/^sha256=/', '', trim($headers['X-B2BLink-Signature'] ?? ''));
        if ($eventId==='' || strlen(INTEGRATION_WEBHOOK_SECRET)<32 || !hash_equals(hash_hmac('sha256',$raw,INTEGRATION_WEBHOOK_SECRET),$provided)) throw new RuntimeException('Signature callback tidak valid.');
        $payload = json_decode($raw,true,512,JSON_THROW_ON_ERROR);
        $occurred=strtotime((string)($payload['occurred_at']??''));
        if($occurred===false||abs(time()-$occurred)>300)throw new RuntimeException('Callback kedaluwarsa.');

        $this->db->beginTransaction();
        try {
            try {
                $this->db->prepare('INSERT INTO integration_inbox(event_id,event_type,payload) VALUES (?, ?, ?)')->execute([$eventId,$payload['event']??'', $raw]);
            } catch (PDOException $error) {
                if ($error->getCode()==='23000') {
                    $this->db->rollBack();
                    return ['idempotent_replay'=>true];
                }
                throw $error;
            }
            $status = $payload['shipment_status'] ?? 'created';
            $allowed = ['created','assigned','dispatched','in_transit','delivered','failed','cancelled'];
            if (!in_array($status,$allowed,true)) {
                $this->db->rollBack();
                throw new RuntimeException('Status shipment tidak valid.');
            }
            $orderStatus = match($status) {
                'delivered' => 'completed',
                'cancelled', 'failed' => 'cancelled',
                'created' => 'processing',
                default => 'shipped'
            };
            $this->db->prepare('UPDATE shipments SET status=?,updated_at=NOW() WHERE order_id=?')->execute([$status,(int)$payload['order_id']]);
            $this->db->prepare('UPDATE orders SET order_status=? WHERE id=? AND payment_status="paid"')->execute([$orderStatus,(int)$payload['order_id']]);

            $this->db->commit();
            return ['idempotent_replay'=>false,'status'=>$status];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function postLogistika(string $path,array $body): array {
        if(LOGISTIKA_URL===''||strlen(LOGISTIKA_API_KEY)<32)throw new RuntimeException('Logistika belum dikonfigurasi.');
        $curl=curl_init(LOGISTIKA_URL.$path);curl_setopt_array($curl,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($body,JSON_THROW_ON_ERROR),CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-API-Key: '.LOGISTIKA_API_KEY]]);$raw=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_error($curl);curl_close($curl);if($raw===false||$error!==''||$status<200||$status>=300)throw new RuntimeException($error?:"Logistika HTTP {$status}");return json_decode($raw,true,512,JSON_THROW_ON_ERROR);
    }

    private function postInsight(string $path, array $body): array {
        $headers = ['Content-Type: application/json'];
        if (UMKM_INSIGHT_EVENTS_API_KEY !== '') {
            $headers[] = 'X-API-Key: ' . UMKM_INSIGHT_EVENTS_API_KEY;
        }
        $curl = curl_init(UMKM_INSIGHT_EVENTS_URL . $path);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($raw === false || $error !== '' || $status < 200 || $status >= 300) {
            throw new RuntimeException($error ?: "Insight HTTP {$status}");
        }
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }
}
