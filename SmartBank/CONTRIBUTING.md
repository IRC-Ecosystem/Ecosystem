# Contributing to SmartBank

Terima kasih sudah tertarik untuk berkontribusi pada **SmartBank CBDC Integration**! 🎉
Proyek ini adalah simulasi two-tier Central Bank Digital Currency (CBDC) untuk
tujuan akademis. Semua kontribusi—mulai dari bug report, dokumentasi, hingga
fitur baru—sangat dihargai.

## 📜 Code of Conduct

Proyek ini mengikuti [Contributor Covenant Code of Conduct](./CODE_OF_CONDUCT.md).
Dengan berpartisipasi, Anda setuju untuk menjunjung tinggi standar perilaku
yang tercantum di sana.

## 🚀 Cara Berkontribusi

### 1. Lapor Bug / Request Fitur

- Buka [GitHub Issues](https://github.com/RPL2-Becode/SmartBank/issues)
- Gunakan template yang tersedia
- Sertakan langkah reproduksi, expected behavior, dan actual behavior
- Untuk fitur baru, jelaskan use case dan benefit-nya

### 2. Pull Request

1. **Fork** repositori ini
2. **Buat branch** dari `main`:
   ```bash
   git checkout -b feat/nama-fitur-anda
   ```
3. **Commit** perubahan dengan pesan yang jelas:
   ```bash
   git commit -m "feat(admin): tambah export audit log ke CSV"
   ```
4. **Push** ke fork Anda:
   ```bash
   git push origin feat/nama-fitur-anda
   ```
5. **Buka Pull Request** ke branch `main` di repositori ini
6. Isi deskripsi PR lengkap—apa yang diubah, kenapa, dan cara test-nya

### 3. Dokumentasi

- Perbaiki typo, tata bahasa, atau format di README
- Tambah contoh API call
- Terjemahkan dokumentasi ke bahasa lain

## 🏗️ Setup Development

### Prasyarat

| Tool   | Versi minimum |
|--------|---------------|
| Node.js| 20.x atau lebih baru |
| npm    | 10.x atau lebih baru |
| pnpm   | 9.x (direkomendasikan) |
| Docker | 24.x (untuk full stack) |
| MySQL  | 8.x (untuk local dev tanpa Docker) |

### Instalasi Lokal (Tanpa Docker)

Lihat [`dokumentasi-lokal.md`](./dokumentasi-lokal.md) untuk panduan lengkap
menjalankan Laragon + MySQL.

### Instalasi dengan Docker

```powershell
docker compose up -d --build --wait
docker compose ps
```

### Menjalankan Test

```bash
# Backend (Central-Bank)
cd Central-Bank
pnpm test
pnpm test:watch          # untuk development

# Linting
pnpm lint

# Frontend
cd ../frontend
pnpm lint
pnpm build
```

## 📏 Konvensi Kode

### Branch Naming

| Awalan      | Contoh                              | Digunakan untuk |
|-------------|-------------------------------------|-----------------|
| `feat/`     | `feat/fee-configuration`            | Fitur baru |
| `fix/`      | `fix/transfer-deadlock`             | Bug fix |
| `docs/`     | `docs/api-rate-limit`               | Dokumentasi |
| `refactor/` | `refactor/settlement-service`       | Refactor tanpa perubahan behavior |
| `test/`     | `test/integration-audit`            | Test saja |
| `chore/`    | `chore/upgrade-prisma`              | Maintenance (deps, config) |

### Commit Messages

Ikuti [Conventional Commits](https://www.conventionalcommits.org/):

```text
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

Contoh:

```text
feat(admin): tambah halaman audit log dengan filter service

- Tambah GET /central-bank/audit-logs
- Filter by search dan serviceName
- Expand row untuk lihat metadata JSON

Closes #42
```

### Code Style

- **TypeScript** — strict mode aktif, jalankan `pnpm lint` sebelum commit
- **File naming** — PascalCase untuk component/class, camelCase untuk function/variable, kebab-case untuk route file
- **Imports** — urutkan: external → internal → types
- **Comments** — bahasa Indonesia untuk logika bisnis, English untuk dokumentasi API
- **Indentation** — 2 spasi (frontend & NestJS default)
- **Max line length** — 100 karakter (soft limit)

### Backend (NestJS) Pattern

- Module → Controller → Service → Prisma
- Pakai DTO + `class-validator` untuk validasi input
- Pakai `AppError` untuk error handling (jangan lempar raw exception)
- Selalu sertakan `Idempotency-Key` di endpoint mutasi
- Audit log WAJIB untuk aksi admin

### Frontend (Next.js + React) Pattern

- Client component: `"use client"` di paling atas
- Pakai `fetchApi` dari `@/lib/api` (auto-generate Idempotency-Key)
- Form pattern konsisten: notice → form card → submit button
- Ikuti design tokens dari shadcn/ui (Tailwind classes)
- Tulis type untuk response API, jangan `any`

## 🔒 Keamanan

- **Jangan commit** kredensial, JWT secret, atau `.env` apapun
- Selalu sanitize input user
- Untuk laporan celah keamanan, hubungi maintainer langsung via email
  (jangan buka public issue)

## 📋 Pull Request Checklist

Sebelum submit PR, pastikan:

- [ ] Branch mengikuti konvensi penamaan
- [ ] Commit message mengikuti Conventional Commits
- [ ] Test baru ditambahkan untuk fitur baru
- [ ] `pnpm lint` lulus di semua workspace
- [ ] `pnpm build` sukses
- [ ] Migrasi database ditambahkan jika ada perubahan schema
- [ ] Dokumentasi (README, JSDoc, komentar) di-update
- [ ] PR deskripsi menjelaskan apa & kenapa
- [ ] Tidak ada konflik dengan `main`

## 📞 Kontak

- **Issues:** [GitHub Issues](https://github.com/RPL2-Becode/SmartBank/issues)
- **Discussions:** [GitHub Discussions](https://github.com/RPL2-Becode/SmartBank/discussions)
- **Maintainer:** RPL2-Becode

## 📄 Lisensi

Dengan berkontribusi, Anda setuju bahwa kontribusi Anda akan dilisensikan
di bawah [MIT License](./LICENSE).
