<?php
/** @var array $data */
$chats = $data['chats'] ?? [];
$contacts = $data['contacts'] ?? [];
$activeReceiverId = (int) ($data['activeReceiverId'] ?? 2);
?>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    .chat-page { font-family: 'Plus Jakarta Sans', sans-serif; }
    .chat-scroll::-webkit-scrollbar { width: 4px; }
    .chat-scroll::-webkit-scrollbar-thumb { background: #bcc9c6; border-radius: 9999px; }
    .chat-bubble-inbound { border-radius: 4px 16px 16px 16px; }
    .chat-bubble-outbound { border-radius: 16px 16px 4px 16px; }
</style>

<div class="chat-page overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <section class="border-b border-slate-200 bg-white p-5">
        <h1 class="text-3xl font-extrabold text-slate-950">Chat Pelanggan & Support</h1>
        <p class="mt-2 text-sm text-slate-600">Komunikasi langsung dengan Penjual UMKM & Admin PasarKita.</p>
    </section>

    <div class="grid h-[620px] grid-cols-1 lg:grid-cols-[300px_1fr]">
        <aside class="flex flex-col border-b border-slate-200 bg-slate-50 lg:border-b-0 lg:border-r">
            <div class="p-4 border-b border-slate-200">
                <h2 class="text-sm font-extrabold text-slate-950 uppercase tracking-wide">Pilih Kontak Chat</h2>
            </div>
            <div class="p-3 space-y-2 overflow-y-auto">
                <?php foreach ($contacts as $contact): ?>
                    <?php $isActive = (int)$contact['id'] === $activeReceiverId; ?>
                    <a href="<?= BASEURL ?>user/chat?with=<?= $contact['id'] ?>" class="block rounded-xl border p-3 transition <?= $isActive ? 'border-emerald-700 bg-emerald-50' : 'border-slate-200 bg-white hover:bg-slate-100' ?>">
                        <p class="font-extrabold text-sm <?= $isActive ? 'text-emerald-950' : 'text-slate-900' ?>">
                            <?= e($contact['name']) ?> 
                            <?php if (!empty($contact['store_name'])): ?>
                                <span class="text-xs font-bold text-emerald-700">(<?= e($contact['store_name']) ?>)</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-xs text-slate-500 uppercase tracking-wide font-bold mt-1"><?= e($contact['role']) ?></p>
                    </a>
                <?php endforeach; ?>
                <?php if (!$contacts): ?>
                    <p class="p-4 text-xs text-slate-500 text-center">Belum ada kontak tersedia.</p>
                <?php endif; ?>
            </div>
        </aside>

        <section class="flex flex-col bg-white">
            <div class="chat-scroll flex-1 space-y-4 overflow-y-auto bg-slate-50/50 p-5">
                <?php if (!$chats): ?>
                    <div class="flex h-full flex-col items-center justify-center text-center">
                        <p class="text-sm text-slate-500">Belum ada percakapan dengan kontak ini.</p>
                        <p class="text-xs text-slate-400 mt-1">Kirim pesan pertama Anda menggunakan kolom di bawah.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($chats as $chat): ?>
                    <?php $isMe = (int)$chat['sender_id'] === (int)current_user()['id']; ?>
                    <div class="flex flex-col <?= $isMe ? 'items-end' : 'items-start' ?>">
                        <div class="max-w-[80%] p-4 text-sm <?= $isMe ? 'chat-bubble-outbound bg-emerald-700 text-white' : 'chat-bubble-inbound bg-slate-200 text-slate-900' ?>">
                            <p class="text-xs font-bold mb-1 opacity-75"><?= e($isMe ? 'Saya' : ($chat['sender_name'] ?? 'Penjual')) ?></p>
                            <p><?= e($chat['message']) ?></p>
                            <span class="mt-2 block text-right text-[10px] opacity-60"><?= e($chat['created_at']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <form method="post" action="<?= BASEURL ?>user/sendChat" class="border-t border-slate-200 bg-white p-4">
                <?= csrf_field() ?>
                <input type="hidden" name="receiver_id" value="<?= $activeReceiverId ?>">
                <div class="flex items-center gap-3">
                    <input name="message" required class="flex-1 rounded-xl border border-slate-300 px-4 py-3 text-sm focus:border-emerald-700 focus:ring-emerald-100" placeholder="Ketik pesan balasan...">
                    <button class="rounded-xl bg-emerald-700 px-6 py-3 text-sm font-extrabold text-white hover:bg-emerald-800">Kirim</button>
                </div>
            </form>
        </section>
    </div>
</div>
