<section class="mx-auto max-w-md rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-2xl font-bold">Login</h1>
    <form method="post" class="mt-6 grid gap-4">
        <?= csrf_field() ?>
        <label class="grid gap-1 text-sm font-medium">Email
            <input name="email" type="email" required class="rounded-md border border-slate-300 px-3 py-2">
        </label>
        <label class="grid gap-1 text-sm font-medium">Password
            <input name="password" type="password" required class="rounded-md border border-slate-300 px-3 py-2">
        </label>
        <button class="rounded-md bg-emerald-700 px-4 py-2 font-semibold text-white">Masuk</button>
    </form>
    <p class="mt-4 text-sm text-slate-600">Belum punya akun? <a class="font-semibold text-emerald-700" href="<?= BASEURL ?>auth/register">Register</a></p>
</section>
