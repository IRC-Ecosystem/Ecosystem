<?php
/** @var array $data */
$store = $data['store'];
$summary = $data['summary'] ?? [];
$money = fn ($value) => 'Rp' . number_format((float) $value, 0, ',', '.');
$statusBadge = function (string $status): string {
    return match ($status) {
        'completed', 'active', 'paid', 'aktif' => 'bg-emerald-50 text-emerald-800',
        'shipped' => 'bg-blue-50 text-blue-800',
        'cancelled', 'inactive' => 'bg-red-50 text-red-800',
        'draft' => 'bg-slate-100 text-slate-700',
        default => 'bg-amber-50 text-amber-800',
    };
};
?>
<?php if (!$store): ?>
<section class="mx-auto max-w-2xl rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Area Calon Seller</p>
    <h1 class="mt-1 text-2xl font-bold">Buka Toko</h1>
    <p class="mt-2 text-slate-600">Setelah toko dibuat, role akun berubah menjadi seller dan bisa mengelola produk.</p>
    <form method="post" action="<?= BASEURL ?>toko/create" class="mt-5 grid gap-4">
        <?= csrf_field() ?>
        <input name="name" required placeholder="Nama toko" class="rounded-md border border-slate-300 px-3 py-2">
        <textarea name="description" placeholder="Deskripsi toko" class="rounded-md border border-slate-300 px-3 py-2"></textarea>
        <textarea name="address" placeholder="Alamat toko" class="rounded-md border border-slate-300 px-3 py-2"></textarea>
        <button class="rounded-md bg-emerald-700 px-4 py-2 font-semibold text-white">Buat toko</button>
    </form>
</section>
<?php else: ?>
<section class="mb-6">
    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Area Seller</p>
    <h1 class="mt-1 text-3xl font-bold">Dashboard Seller</h1>
    <p class="mt-2 text-slate-600">Kelola performa toko, produk, pesanan, promosi, komunikasi pembeli, keuangan, dan restock dari satu tempat.</p>
</section>

<section id="dashboard" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Omzet hari ini</p>
        <b class="mt-2 block text-2xl text-emerald-700"><?= $money($summary['omzet_hari_ini'] ?? 0) ?></b>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Omzet bulan ini</p>
        <b class="mt-2 block text-2xl"><?= $money($summary['omzet_bulan_ini'] ?? 0) ?></b>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Pesanan masuk</p>
        <b class="mt-2 block text-2xl"><?= (int) ($summary['pesanan_baru'] ?? 0) ?></b>
    </div>
    <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">Stok hampir habis</p>
        <b class="mt-2 block text-2xl text-amber-700"><?= count($data['lowStock']) ?></b>
    </div>
</section>

<section class="mt-6 grid gap-6 xl:grid-cols-[360px_1fr]">
    <aside class="grid gap-6">
        <div class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-bold"><?= htmlspecialchars($store['name']) ?></h2>
            <p class="mt-2 text-sm text-slate-600"><?= htmlspecialchars($store['description']) ?></p>
            <span class="mt-3 inline-block rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800"><?= htmlspecialchars($store['status']) ?></span>
        </div>

        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="font-bold">Tambah Produk Baru</h2>
            <form method="post" action="<?= BASEURL ?>toko/product" class="mt-4 grid gap-3">
                <?= csrf_field() ?>
                <input name="name" required placeholder="Nama produk" class="rounded-md border border-slate-300 px-3 py-2">
                <input name="category" required placeholder="Kategori" class="rounded-md border border-slate-300 px-3 py-2">
                <input name="price" required type="number" min="0" placeholder="Harga" class="rounded-md border border-slate-300 px-3 py-2">
                <input name="stock" required type="number" min="0" placeholder="Stok" class="rounded-md border border-slate-300 px-3 py-2">
                <input name="image_url" placeholder="URL foto produk" class="rounded-md border border-slate-300 px-3 py-2">
                <textarea name="description" placeholder="Deskripsi" class="rounded-md border border-slate-300 px-3 py-2"></textarea>
                <button class="rounded-md bg-emerald-700 px-4 py-2 font-semibold text-white">Simpan produk</button>
            </form>
        </section>

        <section id="restock" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-bold">Stok & Restock</h2>
            <p class="mt-1 text-sm text-slate-500">Integrasi SupplierHub ditampilkan sebagai alur restock.</p>
            <?php if (!$data['lowStock']): ?>
                <div class="mt-4 rounded-md border border-dashed border-slate-300 p-4 text-sm text-slate-500">Tidak ada stok menipis.</div>
            <?php endif; ?>
            <?php foreach ($data['lowStock'] as $product): ?>
                <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <div><b><?= htmlspecialchars($product['name']) ?></b><p class="text-sm text-amber-800">Sisa stok <?= (int) $product['stock'] ?></p></div>
                        <a href="#" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white">Restock ke SupplierHub</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    </aside>

    <div class="grid gap-6">
        <section id="products" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold">Manajemen Produk</h2>
                    <p class="text-sm text-slate-500">Tambah, edit, hapus/nonaktifkan, stok, kategori, dan foto produk.</p>
                </div>
                <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600"><?= count($data['products']) ?> produk</span>
            </div>
            <div class="mb-4 flex flex-wrap gap-2">
                <?php foreach ($data['categories'] as $category): ?>
                    <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800"><?= htmlspecialchars($category['category']) ?> (<?= (int) $category['total'] ?>)</span>
                <?php endforeach; ?>
            </div>
            <?php if (!$data['products']): ?>
                <div class="rounded-md border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Belum ada produk.</div>
            <?php else: ?>
                <div class="overflow-hidden rounded-md border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-100 text-slate-600"><tr><th class="p-3 text-left">Produk</th><th class="p-3">Kategori</th><th class="p-3 text-right">Harga</th><th class="p-3">Stok</th><th class="p-3">Status</th><th class="p-3">Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($data['products'] as $product): ?>
                            <tr class="border-t align-top">
                                <form method="post" action="<?= BASEURL ?>toko/updateProduct/<?= $product['id'] ?>">
                                    <?= csrf_field() ?>
                                    <td class="p-3"><input name="name" value="<?= htmlspecialchars($product['name']) ?>" class="w-full rounded border border-slate-300 px-2 py-2"><input type="hidden" name="image_url" value="<?= htmlspecialchars($product['image_url']) ?>"><input type="hidden" name="description" value="<?= htmlspecialchars($product['description']) ?>"></td>
                                    <td class="p-3"><input name="category" value="<?= htmlspecialchars($product['category']) ?>" class="w-32 rounded border border-slate-300 px-2 py-2"></td>
                                    <td class="p-3"><input name="price" type="number" value="<?= (int) $product['price'] ?>" class="w-28 rounded border border-slate-300 px-2 py-2 text-right"></td>
                                    <td class="p-3"><input name="stock" type="number" value="<?= (int) $product['stock'] ?>" class="w-20 rounded border border-slate-300 px-2 py-2"></td>
                                    <td class="p-3"><select name="status" class="rounded border border-slate-300 px-2 py-2"><option <?= $product['status'] === 'active' ? 'selected' : '' ?>>active</option><option <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>inactive</option></select></td>
                                    <td class="p-3"><div class="flex gap-2"><button class="rounded bg-slate-900 px-3 py-2 text-xs font-semibold text-white">Update</button><a class="rounded bg-red-600 px-3 py-2 text-xs font-semibold text-white" href="<?= BASEURL ?>toko/deleteProduct/<?= $product['id'] ?>">Hapus</a></div></td>
                                </form>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section id="orders" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold">Manajemen Pesanan</h2>
                    <p class="text-sm text-slate-500">Pesanan baru, detail, status pembayaran, proses, selesai, dan pembatalan.</p>
                </div>
                <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600"><?= count($data['orders']) ?> order</span>
            </div>
            <?php if (!$data['orders']): ?>
                <div class="rounded-md border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Belum ada order untuk toko ini.</div>
            <?php endif; ?>
            <?php foreach ($data['orders'] as $order): ?>
                <form method="post" action="<?= BASEURL ?>toko/orderStatus" class="mb-3 rounded-md border border-slate-200 p-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <b><?= htmlspecialchars($order['order_code']) ?></b>
                            <p class="text-sm text-slate-600"><?= htmlspecialchars($order['customer_name'] ?? 'Pembeli') ?> - <?= htmlspecialchars($order['shipping_address']) ?></p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span class="rounded-md px-2 py-1 text-xs font-semibold <?= $statusBadge($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span>
                            <span class="rounded-md px-2 py-1 text-xs font-semibold <?= $statusBadge($order['order_status']) ?>"><?= htmlspecialchars($order['order_status']) ?></span>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-[1fr_auto_auto] md:items-center">
                        <p class="text-sm text-slate-600">Subtotal order: <b><?= $money($order['subtotal']) ?></b> | Total dibayar: <b><?= $money($order['total']) ?></b></p>
                        <select name="status" class="rounded border border-slate-300 px-3 py-2">
                            <?php foreach (['processing', 'shipped', 'completed', 'cancelled'] as $status): ?>
                                <option value="<?= $status ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>><?= $status ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="rounded bg-emerald-700 px-3 py-2 font-semibold text-white">Proses pesanan</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </section>

        <section id="promotions" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-bold">Promosi Toko</h2>
            <p class="mt-1 text-sm text-slate-500">Voucher toko, diskon produk, dan produk unggulan.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <?php foreach ($data['promotions'] as $promo): ?>
                    <div class="rounded-md border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div><b><?= htmlspecialchars($promo['name']) ?></b><p class="text-sm text-slate-600"><?= htmlspecialchars($promo['type']) ?> - <?= htmlspecialchars($promo['value']) ?></p></div>
                            <span class="rounded-md px-2 py-1 text-xs font-semibold <?= $statusBadge($promo['status']) ?>"><?= htmlspecialchars($promo['status']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="chat" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-bold">Chat / Komunikasi Pembeli</h2>
            <p class="mt-1 text-sm text-slate-500">Notifikasi pesan masuk dan riwayat chat pembeli-seller.</p>
            <div class="mt-4 grid gap-3">
                <?php foreach ($data['messages'] as $message): ?>
                    <div class="flex items-start justify-between gap-3 rounded-md border border-slate-200 p-4">
                        <div><b><?= htmlspecialchars($message['buyer']) ?></b><p class="text-sm text-slate-600"><?= htmlspecialchars($message['message']) ?></p></div>
                        <div class="text-right"><p class="text-xs text-slate-500"><?= htmlspecialchars($message['time']) ?></p><?php if ($message['unread']): ?><span class="mt-2 inline-block rounded-md bg-red-50 px-2 py-1 text-xs font-semibold text-red-700">baru</span><?php endif; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="finance" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-bold">Keuangan Seller</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-4">
                <div class="rounded-md bg-slate-100 p-4"><p class="text-sm text-slate-500">Pendapatan</p><b><?= $money($summary['total_pendapatan'] ?? 0) ?></b></div>
                <div class="rounded-md bg-slate-100 p-4"><p class="text-sm text-slate-500">Status pencairan</p><b>Menunggu SmartBank</b></div>
                <div class="rounded-md bg-slate-100 p-4"><p class="text-sm text-slate-500">Fee marketplace</p><b><?= $money($summary['total_fee_marketplace'] ?? 0) ?></b></div>
                <div class="rounded-md bg-slate-100 p-4"><p class="text-sm text-slate-500">Saldo penuh</p><b>SmartBank</b></div>
            </div>
            <div class="mt-4 overflow-hidden rounded-md border border-slate-200">
                <table class="w-full text-sm"><thead class="bg-slate-100 text-slate-600"><tr><th class="p-3 text-left">Order</th><th class="p-3">Produk</th><th class="p-3 text-right">Qty</th><th class="p-3 text-right">Subtotal</th></tr></thead><tbody>
                <?php foreach (array_slice($data['orderItems'], 0, 8) as $item): ?><tr class="border-t"><td class="p-3 font-semibold"><?= htmlspecialchars($item['order_code']) ?></td><td class="p-3"><?= htmlspecialchars($item['product_name']) ?></td><td class="p-3 text-right"><?= (int) $item['qty'] ?></td><td class="p-3 text-right font-semibold"><?= $money($item['subtotal']) ?></td></tr><?php endforeach; ?>
                <?php if (!$data['orderItems']): ?><tr><td colspan="4" class="p-6 text-center text-slate-500">Belum ada transaksi order.</td></tr><?php endif; ?>
                </tbody></table>
            </div>
        </section>

        <section id="performance" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-xl font-bold">Performa Toko Sederhana</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-5">
                <div class="rounded-md bg-slate-100 p-4"><p class="text-sm text-slate-500">Omzet</p><b><?= $money($summary['total_pendapatan'] ?? 0) ?></b></div>
                <div class="rounded-md bg-slate-100 p-4"><p class="text-sm text-slate-500">Pesanan</p><b><?= count($data['orders']) ?></b></div>
                <div class="rounded-md bg-slate-100 p-4"><p class="text-sm text-slate-500">Terlaris</p><b><?= htmlspecialchars($data['bestSellers'][0]['product_name'] ?? '-') ?></b></div>
                <div class="rounded-md bg-slate-100 p-4"><p class="text-sm text-slate-500">Pengunjung</p><b><?= max(24, count($data['products']) * 17) ?></b></div>
                <div class="rounded-md bg-slate-100 p-4"><p class="text-sm text-slate-500">Rating</p><b>4.8/5</b></div>
            </div>
            <div class="mt-4 rounded-md border border-slate-200 p-4">
                <h3 class="font-semibold">Produk terlaris</h3>
                <?php if (!$data['bestSellers']): ?><p class="mt-2 text-sm text-slate-500">Belum ada produk terjual.</p><?php endif; ?>
                <?php foreach ($data['bestSellers'] as $product): ?>
                    <div class="mt-3 flex justify-between gap-3 text-sm"><span><?= htmlspecialchars($product['product_name']) ?></span><b><?= (int) $product['qty_sold'] ?> terjual</b></div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
<?php endif; ?>
