<section class="mx-auto max-w-lg rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-bold">Register User</h1>
    <form method="post" class="mt-6 grid gap-4">
        <?= csrf_field() ?>
        <label class="grid gap-1 text-sm font-medium">Nama
            <input name="name" required class="rounded-md border border-slate-300 px-3 py-2">
        </label>
        <label class="grid gap-1 text-sm font-medium">Email
            <input name="email" type="email" required class="rounded-md border border-slate-300 px-3 py-2">
        </label>
        <label class="grid gap-1 text-sm font-medium">No. HP
            <input name="phone" class="rounded-md border border-slate-300 px-3 py-2">
        </label>
        <label class="grid gap-1 text-sm font-medium">Alamat
            <textarea name="address" class="rounded-md border border-slate-300 px-3 py-2"></textarea>
        </label>
        <label class="grid gap-1 text-sm font-medium">Password
            <input name="password" type="password" required minlength="6" class="rounded-md border border-slate-300 px-3 py-2">
        </label>
        <button class="rounded-md bg-emerald-700 px-4 py-2 font-semibold text-white">Buat akun</button>
    </form>
</section>
