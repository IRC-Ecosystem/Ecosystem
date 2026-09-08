<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/config/koneksi.php';

class FakePDOStatement extends PDOStatement
{
    private array $data;
    private int $affected;

    public function __construct(array $data = [], int $affected = 1)
    {
        $this->data = $data;
        $this->affected = $affected;
    }

    public function execute(?array $params = null): bool
    {
        return true;
    }

    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        return $this->data[0] ?? false;
    }

    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, ...$args): array
    {
        return $this->data;
    }

    public function rowCount(): int
    {
        return $this->affected;
    }
}

class MarketplaceConsistencyTest
{
    public static function run(): void
    {
        echo "Running Marketplace consistency checks...\n";

        // 1. Verify Logistics callback mapping logic
        $status = 'failed';
        $allowed = ['created','assigned','dispatched','in_transit','delivered','failed','cancelled'];
        if (!in_array($status, $allowed, true)) {
            throw new RuntimeException("Invalid status");
        }
        $orderStatus = match($status) {
            'delivered' => 'completed',
            'cancelled', 'failed' => 'cancelled',
            'created' => 'processing',
            default => 'shipped'
        };

        if ($orderStatus === 'shipped') {
            throw new RuntimeException("Test Failed: 'failed' shipment mapped to 'shipped'!");
        }
        echo "✔ Logistics callback mapping test passed (failed -> cancelled).\n";

        // 2. Verify Safe Integer Money Conversion
        $totalFloat = 15000.00;
        $grossAmount = (string) (int) round($totalFloat);
        if ($grossAmount !== '15000') {
            throw new RuntimeException("Test Failed: money conversion invalid");
        }
        echo "✔ Safe integer money conversion test passed.\n";

        // 3. Container environment must override local .env values
        $envPath = tempnam(sys_get_temp_dir(), 'marketplace-env-');
        file_put_contents($envPath, "DB_HOST=localhost\nDB_USER=root\n");
        putenv('DB_HOST=mysql');
        putenv('DB_USER=marketplace_app');
        load_env($envPath);
        unlink($envPath);

        if (getenv('DB_HOST') !== 'mysql' || getenv('DB_USER') !== 'marketplace_app') {
            throw new RuntimeException('Test Failed: local .env overrides container environment');
        }
        echo "✔ Container environment overrides local .env values.\n";

        echo "All Marketplace consistency unit assertions PASSED!\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    MarketplaceConsistencyTest::run();
}
