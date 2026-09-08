<?php
/** @var array $data */
$store = $data['store'] ?? ['name' => 'Toko'];
$chats = $data['chats'] ?? [];
$contacts = $data['contacts'] ?? [];
$activeReceiverId = (int) ($data['activeReceiverId'] ?? 3);
?>

<style>
    .chat-bubble-left { border-radius: 4px 16px 16px 16px; }
    .chat-bubble-right { border-radius: 16px 4px 16px 16px; }
    .seller-chat-panel { height: calc(100vh - 190px); min-height: 620px; }
</style>

<section class="space-y-5">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <h1 class="text-3xl font-extrabold text-[#0b1c30]">Pesan & Chat Pembeli</h1>
            <p class="mt-2 text-sm leading-6 text-[#3d4947]">Jawab pertanyaan pelanggan mengenai produk <?= e($store['name']) ?>.</p>
        </div>
    </div>

    <div class="seller-chat-panel grid overflow-hidden rounded-xl border border-[#bcc9c6] bg-white shadow-sm lg:grid-cols-[300px_1fr]">
        <aside class="flex flex-col border-b border-[#bcc9c6] bg-[#ffffff] lg:border-b-0 lg:border-r">
            <div class="p-4 border-b border-[#bcc9c6]">
                <h2 class="text-xs font-extrabold text-[#0b1c30] uppercase tracking-wide">Pilih Kontak Pelanggan</h2>
            </div>
            <div class="p-3 space-y-2 overflow-y-auto">
                <?php foreach ($contacts as $contact): ?>
                    <?php $isActive = (int)$contact['id'] === $activeReceiverId; ?>
                    <a href="<?= BASEURL ?>toko/chat?with=<?= $contact['id'] ?>" class="block rounded-xl border p-3 transition <?= $isActive ? 'border-[#00685f] bg-[#008378]/10' : 'border-[#bcc9c6] bg-white hover:bg-[#eff4ff]' ?>">
                        <p class="font-extrabold text-sm <?= $isActive ? 'text-[#0b1c30]' : 'text-slate-700' ?>">
                            <?= e($contact['name']) ?>
                        </p>
                        <p class="text-xs text-[#00685f] font-bold mt-1 uppercase tracking-wide"><?= e($contact['role']) ?></p>
                    </a>
                <?php endforeach; ?>
                <?php if (!$contacts): ?>
                    <p class="p-4 text-xs text-slate-500 text-center">Belum ada pelanggan menghubungi toko.</p>
                <?php endif; ?>
            </div>
        </aside>

        <main class="flex flex-col bg-[#f8f9ff]">
            <header class="flex h-16 items-center justify-between border-b border-[#bcc9c6] bg-white px-5">
                <h2 class="font-extrabold text-[#0b1c30]">Obrolan Toko</h2>
            </header>

            <div class="flex-1 space-y-4 overflow-y-auto bg-slate-50 p-5">
                <?php if (!$chats): ?>
                    <div class="flex h-full flex-col items-center justify-center text-center">
                        <p class="text-sm text-slate-500">Belum ada pesan dengan pembeli ini.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($chats as $msg): ?>
                    <?php $isMe = (int)($msg['sender_id'] ?? 0) === (int)current_user()['id']; ?>
                    <div class="flex flex-col <?= $isMe ? 'items-end' : 'items-start' ?>">
                        <div class="max-w-[75%] p-4 text-sm <?= $isMe ? 'chat-bubble-right bg-[#00685f] text-white' : 'chat-bubble-left border border-[#bcc9c6] bg-white text-[#0b1c30]' ?>">
                            <p class="text-xs font-bold mb-1 opacity-80"><?= e($isMe ? 'Toko Saya' : ($msg['sender_name'] ?? 'Pembeli')) ?></p>
                            <p><?= e($msg['message']) ?></p>
                            <span class="mt-2 block text-right text-[10px] opacity-70"><?= e($msg['created_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" action="<?= BASEURL ?>toko/sendChat" class="border-t border-[#bcc9c6] bg-white p-4">
                <?= csrf_field() ?>
                <input type="hidden" name="receiver_id" value="<?= $activeReceiverId ?>">
                <div class="flex items-center gap-3">
                    <input name="message" required class="flex-1 rounded-xl border border-[#bcc9c6] px-4 py-3 text-sm focus:border-[#00685f]" placeholder="Ketik balasan untuk pembeli...">
                    <button class="rounded-xl bg-[#00685f] px-6 py-3 text-sm font-extrabold text-white hover:bg-[#005049]">Kirim Balasan</button>
                </div>
            </form>
        </main>
    </div>
</section>
