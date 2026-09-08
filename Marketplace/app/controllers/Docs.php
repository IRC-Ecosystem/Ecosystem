<?php

class Docs extends Controllers
{
    public function index()
    {
        $data['title'] = 'API Docs';
        $this->view('docs/index', $data);
    }

    public function openapi()
    {
        header('Content-Type: application/json');

        $chartResponse = [
            'description' => 'Data grafik berhasil diambil dari database.',
            'content' => [
                'application/json' => [
                    'schema' => ['$ref' => '#/components/schemas/ChartResponse'],
                ],
            ],
        ];
        $htmlRedirectResponse = [
            'description' => 'Session tidak sesuai role. Aplikasi akan mengarahkan ke halaman login atau halaman sesuai role.',
        ];
        $formBody = function (array $properties, array $required = []): array {
            return [
                'required' => true,
                'content' => [
                    'application/x-www-form-urlencoded' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => $required,
                            'properties' => $properties,
                        ],
                    ],
                ],
            ];
        };
        $redirectResponse = [
            'description' => 'Action berhasil/gagal lalu aplikasi redirect ke halaman terkait dengan flash message.',
        ];

        $paths = [
            '/docs/openapi' => [
                'get' => [
                    'tags' => ['Documentation'],
                    'summary' => 'OpenAPI JSON',
                    'description' => 'Mengambil spesifikasi OpenAPI PasarKita dalam format JSON.',
                    'responses' => ['200' => ['description' => 'Dokumen OpenAPI JSON.']],
                ],
            ],
            '/marketplace/browse_produk' => [
                'get' => [
                    'tags' => ['Marketplace'],
                    'summary' => 'Browse produk',
                    'description' => 'Mengambil daftar produk aktif. Bisa memakai query q untuk pencarian nama produk atau kategori.',
                    'parameters' => [
                        [
                            'name' => 'q',
                            'in' => 'query',
                            'required' => false,
                            'schema' => ['type' => 'string'],
                            'description' => 'Kata kunci pencarian produk atau kategori.',
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Daftar produk berhasil diambil.',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'status' => ['type' => 'boolean', 'example' => true],
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Product'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/marketplace/checkout' => [
                'get' => [
                    'tags' => ['Marketplace'],
                    'summary' => 'Kontrak checkout',
                    'description' => 'Menjelaskan kontrak checkout Marketplace. Checkout UI penuh berada di /user/checkout.',
                    'responses' => [
                        '200' => [
                            'description' => 'Informasi fee dan kontrak checkout.',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/CheckoutContractResponse'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/marketplace/status_order' => [
                'get' => [
                    'tags' => ['Marketplace'],
                    'summary' => 'Status order user login',
                    'description' => 'Mengambil status order untuk session user yang sedang login. Jika belum login, data dikembalikan sebagai array kosong.',
                    'security' => [['cookieAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Daftar order user login.',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'status' => ['type' => 'boolean', 'example' => true],
                                            'data' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Order'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/auth/login' => [
                'post' => [
                    'tags' => ['Auth Actions'],
                    'summary' => 'Login user',
                    'description' => 'Memproses login dan membuat session PHP. Response berupa redirect ke dashboard sesuai role.',
                    'requestBody' => $formBody([
                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'admin@pasarkita.test'],
                        'password' => ['type' => 'string', 'format' => 'password', 'example' => 'admin123'],
                    ], ['email', 'password']),
                    'responses' => [
                        '302' => $redirectResponse,
                        '200' => ['description' => 'Jika gagal validasi, halaman login ditampilkan kembali.'],
                    ],
                ],
            ],
            '/auth/register' => [
                'post' => [
                    'tags' => ['Auth Actions'],
                    'summary' => 'Register pembeli',
                    'description' => 'Membuat akun role user, membuat wallet awal, lalu redirect ke login.',
                    'requestBody' => $formBody([
                        'name' => ['type' => 'string', 'example' => 'Budi Pembeli'],
                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'budi@example.com'],
                        'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 6, 'example' => 'rahasia123'],
                        'address' => ['type' => 'string', 'example' => 'Jl. Merdeka No. 10'],
                        'phone' => ['type' => 'string', 'example' => '081234567890'],
                    ], ['name', 'email', 'password']),
                    'responses' => [
                        '302' => $redirectResponse,
                        '200' => ['description' => 'Jika gagal validasi, halaman register ditampilkan kembali.'],
                    ],
                ],
            ],
            '/auth/logout' => [
                'get' => [
                    'tags' => ['Auth Actions'],
                    'summary' => 'Logout',
                    'description' => 'Menghapus session login lalu redirect ke landing page.',
                    'security' => [['cookieAuth' => []]],
                    'responses' => ['302' => $redirectResponse],
                ],
            ],
            '/user/addCart' => [
                'post' => [
                    'tags' => ['User Actions'],
                    'summary' => 'Tambah produk ke keranjang',
                    'description' => 'Menambahkan produk ke session cart user login.',
                    'security' => [['cookieAuth' => []]],
                    'requestBody' => $formBody([
                        'product_id' => ['type' => 'integer', 'example' => 1],
                        'qty' => ['type' => 'integer', 'minimum' => 1, 'example' => 2],
                    ], ['product_id']),
                    'responses' => [
                        '302' => $redirectResponse,
                    ],
                ],
            ],
            '/user/removeCart/{productId}' => [
                'delete' => [
                    'tags' => ['User Actions'],
                    'summary' => 'Hapus produk dari keranjang',
                    'description' => 'Menghapus item dari session cart. Route MVC juga bisa dipanggil via link GET lama, tapi method API yang didokumentasikan adalah DELETE.',
                    'security' => [['cookieAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'productId',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer'],
                            'example' => 1,
                        ],
                    ],
                    'responses' => ['302' => $redirectResponse],
                ],
            ],
            '/user/checkout' => [
                'post' => [
                    'tags' => ['User Actions'],
                    'summary' => 'Checkout keranjang',
                    'description' => 'Membuat order dari session cart, mengurangi saldo wallet, membuat payment request simulasi SmartBank, mencatat ledger, dan mengurangi stok.',
                    'security' => [['cookieAuth' => []]],
                    'requestBody' => $formBody([
                        'shipping_address' => ['type' => 'string', 'example' => 'Jl. Merdeka No. 10, Bandung'],
                    ], ['shipping_address']),
                    'responses' => [
                        '302' => $redirectResponse,
                        '200' => ['description' => 'Jika gagal validasi, halaman checkout ditampilkan kembali.'],
                    ],
                ],
            ],
            '/toko/create' => [
                'post' => [
                    'tags' => ['Seller Actions'],
                    'summary' => 'Buka toko',
                    'description' => 'User membuat toko baru. Jika berhasil, role session berubah menjadi seller.',
                    'security' => [['cookieAuth' => []]],
                    'requestBody' => $formBody([
                        'name' => ['type' => 'string', 'example' => 'Toko Sari Makmur'],
                        'description' => ['type' => 'string', 'example' => 'Produk UMKM harian'],
                        'address' => ['type' => 'string', 'example' => 'Jl. Pasar No. 7'],
                    ], ['name']),
                    'responses' => ['302' => $redirectResponse],
                ],
            ],
            '/toko/product' => [
                'post' => [
                    'tags' => ['Seller Actions'],
                    'summary' => 'Tambah produk seller',
                    'description' => 'Menambahkan produk baru ke toko milik seller login.',
                    'security' => [['cookieAuth' => []]],
                    'requestBody' => $formBody([
                        'name' => ['type' => 'string', 'example' => 'Keripik Singkong Pedas'],
                        'category' => ['type' => 'string', 'example' => 'Makanan'],
                        'description' => ['type' => 'string', 'example' => 'Keripik renyah produksi UMKM lokal'],
                        'price' => ['type' => 'number', 'example' => 12000],
                        'stock' => ['type' => 'integer', 'example' => 40],
                        'image_url' => ['type' => 'string', 'example' => 'https://example.com/produk.jpg'],
                    ], ['name', 'category', 'price', 'stock']),
                    'responses' => ['302' => $redirectResponse],
                ],
            ],
            '/toko/updateProduct/{id}' => [
                'post' => [
                    'tags' => ['Seller Actions'],
                    'summary' => 'Update produk seller',
                    'description' => 'Mengubah data produk milik toko seller login.',
                    'security' => [['cookieAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer'],
                            'example' => 1,
                        ],
                    ],
                    'requestBody' => $formBody([
                        'name' => ['type' => 'string', 'example' => 'Keripik Singkong Pedas'],
                        'category' => ['type' => 'string', 'example' => 'Makanan'],
                        'description' => ['type' => 'string', 'example' => 'Keripik renyah produksi UMKM lokal'],
                        'price' => ['type' => 'number', 'example' => 13000],
                        'stock' => ['type' => 'integer', 'example' => 35],
                        'image_url' => ['type' => 'string', 'example' => 'https://example.com/produk.jpg'],
                        'status' => ['type' => 'string', 'enum' => ['active', 'inactive'], 'example' => 'active'],
                    ], ['name', 'category', 'price', 'stock']),
                    'responses' => ['302' => $redirectResponse],
                ],
            ],
            '/toko/deleteProduct/{id}' => [
                'delete' => [
                    'tags' => ['Seller Actions'],
                    'summary' => 'Hapus produk seller',
                    'description' => 'Menghapus produk milik toko seller login. Route MVC juga bisa dipanggil via link GET lama, tapi method API yang didokumentasikan adalah DELETE.',
                    'security' => [['cookieAuth' => []]],
                    'parameters' => [
                        [
                            'name' => 'id',
                            'in' => 'path',
                            'required' => true,
                            'schema' => ['type' => 'integer'],
                            'example' => 1,
                        ],
                    ],
                    'responses' => ['302' => $redirectResponse],
                ],
            ],
            '/toko/orderStatus' => [
                'post' => [
                    'tags' => ['Seller Actions'],
                    'summary' => 'Update status pesanan',
                    'description' => 'Seller mengubah status pesanan yang berisi item dari tokonya.',
                    'security' => [['cookieAuth' => []]],
                    'requestBody' => $formBody([
                        'order_id' => ['type' => 'integer', 'example' => 1],
                        'status' => ['type' => 'string', 'enum' => ['processing', 'shipped', 'completed', 'cancelled'], 'example' => 'shipped'],
                    ], ['order_id', 'status']),
                    'responses' => ['302' => $redirectResponse],
                ],
            ],
        ];

        foreach ([
            '/chart/adminSummary' => ['Admin Charts', 'Grafik ringkasan platform', 'Order, produk, toko, dan stok menipis.'],
            '/chart/adminRoles' => ['Admin Charts', 'Grafik distribusi role', 'Jumlah user berdasarkan role.'],
            '/chart/adminStores' => ['Admin Charts', 'Grafik status toko', 'Jumlah toko berdasarkan status.'],
            '/chart/adminOrders' => ['Admin Charts', 'Grafik status order', 'Jumlah order berdasarkan status.'],
            '/chart/sellerDashboard' => ['Seller Charts', 'Grafik dashboard seller', 'Produk terlaris seller login.'],
            '/chart/sellerProducts' => ['Seller Charts', 'Grafik stok produk seller', 'Stok produk seller login.'],
            '/chart/sellerOrders' => ['Seller Charts', 'Grafik status pesanan seller', 'Pesanan seller login berdasarkan status.'],
            '/chart/sellerFinance' => ['Seller Charts', 'Grafik keuangan seller', 'Pendapatan kotor, fee marketplace, dan estimasi bersih.'],
            '/chart/sellerRestock' => ['Seller Charts', 'Grafik prioritas restock', 'Prioritas restock berdasarkan stok menipis.'],
            '/chart/sellerPerformance' => ['Seller Charts', 'Grafik performa seller', 'Produk terlaris untuk halaman performa toko.'],
        ] as $path => $meta) {
            [$tag, $summary, $description] = $meta;
            $paths[$path] = [
                'get' => [
                    'tags' => [$tag],
                    'summary' => $summary,
                    'description' => $description . ' Endpoint ini dipakai kotak grafik yang auto-refresh di dashboard.',
                    'security' => [['cookieAuth' => []]],
                    'responses' => [
                        '200' => $chartResponse,
                        '302' => $htmlRedirectResponse,
                    ],
                ],
            ];
        }

        echo json_encode([
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'PasarKita API',
                'description' => 'Dokumentasi semua endpoint JSON PasarKita yang aktif saat ini: Marketplace, grafik admin, grafik seller, dan OpenAPI spec. Route dashboard, produk, pesanan, login, register, dan toko masih berupa halaman/form HTML sehingga tidak dimasukkan sebagai API JSON.',
                'version' => '1.0.0',
            ],
            'servers' => [
                ['url' => rtrim(BASEURL, '/')],
            ],
            'tags' => [
                ['name' => 'Documentation', 'description' => 'Dokumentasi OpenAPI'],
                ['name' => 'Marketplace', 'description' => 'Endpoint kontrak Marketplace PasarKita'],
                ['name' => 'Auth Actions', 'description' => 'Action login, register, dan logout berbasis form/session'],
                ['name' => 'User Actions', 'description' => 'Action pembeli berbasis form/session'],
                ['name' => 'Seller Actions', 'description' => 'Action seller berbasis form/session'],
                ['name' => 'Admin Charts', 'description' => 'Endpoint data grafik role admin'],
                ['name' => 'Seller Charts', 'description' => 'Endpoint data grafik role seller'],
            ],
            'paths' => $paths,
            'components' => [
                'securitySchemes' => [
                    'cookieAuth' => [
                        'type' => 'apiKey',
                        'in' => 'cookie',
                        'name' => 'PHPSESSID',
                        'description' => 'Session cookie dari login aplikasi. Swagger UI akan mengirim cookie browser secara otomatis.',
                    ],
                ],
                'schemas' => [
                    'ChartItem' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string', 'example' => 'Produk'],
                            'value' => ['type' => 'number', 'example' => 4],
                            'formatted' => ['type' => 'string', 'example' => 'Rp50.000'],
                            'color' => ['type' => 'string', 'example' => 'emerald'],
                        ],
                    ],
                    'ChartResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string', 'example' => 'Kesehatan Platform'],
                            'updated_at' => ['type' => 'string', 'example' => '2026-07-10 10:00:00'],
                            'items' => [
                                'type' => 'array',
                                'items' => ['$ref' => '#/components/schemas/ChartItem'],
                            ],
                        ],
                    ],
                    'Product' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'store_id' => ['type' => 'integer', 'example' => 1],
                            'name' => ['type' => 'string', 'example' => 'Keripik Singkong Pedas'],
                            'category' => ['type' => 'string', 'example' => 'Makanan'],
                            'description' => ['type' => 'string'],
                            'price' => ['type' => 'string', 'example' => '12000.00'],
                            'stock' => ['type' => 'integer', 'example' => 40],
                            'image_url' => ['type' => 'string'],
                            'status' => ['type' => 'string', 'example' => 'active'],
                            'store_name' => ['type' => 'string', 'example' => 'Dapur Sari'],
                        ],
                    ],
                    'Order' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer', 'example' => 1],
                            'order_code' => ['type' => 'string', 'example' => 'PK-20260709120000-123'],
                            'total' => ['type' => 'string', 'example' => '25000.00'],
                            'payment_status' => ['type' => 'string', 'example' => 'paid'],
                            'order_status' => ['type' => 'string', 'example' => 'processing'],
                            'created_at' => ['type' => 'string', 'example' => '2026-07-10 12:00:00'],
                        ],
                    ],
                    'CheckoutContractResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'boolean', 'example' => true],
                            'message' => ['type' => 'string'],
                            'fees' => [
                                'type' => 'object',
                                'properties' => [
                                    'marketplace' => ['type' => 'string', 'example' => '2%'],
                                    'gateway' => ['type' => 'string', 'example' => '0.5%'],
                                    'bank' => ['type' => 'string', 'example' => '1%'],
                                    'tax' => ['type' => 'string', 'example' => '2%'],
                                    'logistics' => ['type' => 'string', 'example' => '5% atau flat 5000'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
