"use client";

import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { AnimatePresence, motion } from "framer-motion";
import {
  AlertTriangle,
  ArrowDownUp,
  ArrowLeft,
  ArrowRight,
  Banknote,
  BookOpen,
  Building2,
  CheckCircle2,
  ChevronDown,
  CircleDollarSign,
  Clock,
  CreditCard,
  FileCheck2,
  Fingerprint,
  Flame,
  Hash,
  KeyRound,
  Landmark,
  Layers,
  Lightbulb,
  Lock,
  Mail,
  Phone,
  PiggyBank,
  QrCode,
  ScanSearch,
  ScrollText,
  ShieldCheck,
  Sparkles,
  Sprout,
  Users,
  Wallet,
  Zap,
} from "lucide-react";

/* ============================================================
   KONSTANTA KONTEN
   ============================================================ */

const ONBOARDING_STEPS = [
  {
    n: "01",
    title: "Registrasi atau gunakan akun resmi",
    body: "Untuk akun retail baru, buka halaman Register dan isi nama, email, telepon (+62), password, dan PIN 6-digit. Akun staf disediakan administrator.",
    icon: Mail,
  },
  {
    n: "02",
    title: "Lengkapi KYC ringan",
    body: "Menu Verifikasi KYC di sidebar. Status UNVERIFIED cukup untuk transfer & top-up. Untuk pinjam, status harus naik ke VERIFIED lewat Teller (verifikasi dokumen ringan).",
    icon: FileCheck2,
  },
  {
    n: "03",
    title: "Top-up saldo via Teller",
    body: "Saldo retail baru = 0. Datangi cabang (login sebagai Teller) → menu Operasi Teller → pilih nasabah via pencarian → lakukan top-up dari kas cabang ke wallet-nya.",
    icon: Wallet,
  },
  {
    n: "04",
    title: "Login & mulai transaksi",
    body: "Semua fitur ada di dashboard sesuai peran. Status KYC & role terlihat di pojok kanan atas header. PIN 6-digit diminta terpisah dari password untuk setiap transaksi finansial.",
    icon: KeyRound,
  },
];

const P2P_STEPS = [
  { n: "01", label: "Buka menu Transfer", icon: ArrowDownUp },
  { n: "02", label: "Pilih tujuan (no telepon / 10 digit account number)", icon: Hash },
  { n: "03", label: "Input nominal + catatan", icon: Banknote },
  { n: "04", label: "Review fee (auto dari fee engine)", icon: CircleDollarSign },
  { n: "05", label: "Konfirmasi PIN 6-digit", icon: KeyRound },
  { n: "06", label: "Settled — cek di menu Aktivitas", icon: ScrollText },
];

const LOAN_FLOW = [
  { status: "APPLY", desc: "Nasabah apply via menu Pinjaman. Sistem cek KYC=VERIFIED & outstanding + nominal ≤ 100.000.", icon: Sprout, tone: "primary" },
  { status: "PENDING", desc: "Masuk antrian. Teller screening (≤ 50.000) atau langsung Manager (> 50.000).", icon: Clock, tone: "amber" },
  { status: "RECOMMENDED", desc: "Teller menyertakan catatan. Tab Manager punya filter ?recommended=true untuk yang sudah di-screening.", icon: CheckCircle2, tone: "blue" },
  { status: "DISBURSED", desc: "Manager approve → LOAN_POOL_ACCOUNT di-debit, wallet borrower di-kredit. Saldo naik instan.", icon: Flame, tone: "emerald" },
  { status: "PARTIAL_PAID", desc: "Nasabah cicil via menu Pinjaman → Repay. Setiap cicilan menambah paid_amount.", icon: CircleDollarSign, tone: "primary" },
  { status: "PAID", desc: "paid_amount ≥ total_due. Status final, dana kembali ke LOAN_POOL_ACCOUNT.", icon: ShieldCheck, tone: "emerald" },
];

const ROLE_BENTO = [
  {
    n: "01",
    name: "Nasabah",
    sub: "Retail / Merchant",
    icon: Users,
    size: "lg:col-span-7 lg:row-span-2",
    tone: "primary",
    bullets: [
      "Cek saldo & mutasi di dashboard",
      "Transfer P2P via telepon atau 10 digit account number",
      "Bayar QR / invoice merchant",
      "Top-up & tarik tunai via Teller",
      "Ajukan & cicil pinjaman UMKM (cap 100.000)",
    ],
    restrict: "Tidak bisa lihat data nasabah lain atau ubah status akun sendiri.",
    cta: { href: "/register", label: "Daftar retail" },
  },
  {
    n: "02",
    name: "Teller",
    sub: "Layanan Cabang",
    icon: Building2,
    size: "lg:col-span-5",
    tone: "blue",
    bullets: [
      "Cari nasabah via menu Pencarian",
      "Top-up / withdraw kas cabang ↔ wallet",
      "Verifikasi KYC (BASIC → VERIFIED)",
      "Screening pinjaman ≤ 50.000 + catatan",
    ],
    restrict: "Tidak boleh approve pinjaman atau freeze akun.",
    cta: null,
  },
  {
    n: "03",
    name: "Manager",
    sub: "Pimpinan Cabang",
    icon: ShieldCheck,
    size: "lg:col-span-5",
    tone: "amber",
    bullets: [
      "Seluruh hak akses Teller",
      "Approval final pinjaman (semua nominal)",
      "Suspend akun sementara (anti-fraud)",
      "Set limit harian merchant",
    ],
    restrict: "Tidak boleh mint/burn uang atau ubah tarif pajak global.",
    cta: null,
  },
  {
    n: "04",
    name: "Admin Central Bank",
    sub: "Otoritas Moneter",
    icon: Landmark,
    size: "lg:col-span-7",
    tone: "rose",
    bullets: [
      "Issuance & burn CBDC (Tier-1)",
      "Audit global ledger (append-only)",
      "Reversal transaksi ACID dengan reason code",
      "Atur fee_rules & tax_rules",
      "Monitoring pool saldo & money supply",
    ],
    restrict: "Akses tertinggi, hanya untuk admin infrastruktur bank sentral.",
    cta: null,
  },
];

const SECURITY_TIPS = [
  {
    icon: KeyRound,
    title: "Password ≠ PIN",
    body: "Password untuk login. PIN 6-digit hanya diminta saat transaksi finansial (transfer, top-up, repay). Keduanya di-hash bcrypt, tidak pernah plain.",
  },
  {
    icon: Fingerprint,
    title: "Idempotency-Key otomatis",
    body: "Setiap POST finansial dapat UUID acak dari client. Submit dua kali = satu transaksi. Aman dari double-tap atau retry jaringan.",
  },
  {
    icon: ScanSearch,
    title: "Audit append-only",
    body: "Setiap aksi admin (freeze, reversal, fee-rules) tercatat dengan reason, actor_id, request_id. Tidak ada baris ledger yang bisa diedit diam-diam.",
  },
  {
    icon: Lock,
    title: "Privacy masking",
    body: "Nama penerima di-masking sebelum transfer. Data KYC tidak pernah sampai ke merchant. Audit regulator hanya untuk Admin.",
  },
];

const FAQS = [
  {
    q: "Saya lupa password,怎么办?",
    a: "Hubungi Admin Bank Sentral untuk reset manual. Sistem akademis ini belum punya self-service reset. PIN 6-digit juga di-reset via Teller cabang dengan verifikasi identitas.",
  },
  {
    q: "Transfer saya statusnya PENDING lama, kenapa?",
    a: "Umumnya karena Gateway timeout atau Core sedang down. Cek koneksi semua service (Central-Bank :3000, Wallet :4000, Gateway :6969). Transaksi PENDING > 30 detik otomatis di-mark FAILED; dana tidak ter-debit (atomic settlement).",
  },
  {
    q: "Kenapa pengajuan pinjaman saya ditolak (REJECTED)?",
    a: "Tiga penyebab umum: (1) KYC belum VERIFIED — lengkapi dulu via Teller. (2) outstanding + nominal > 100.000 — lunasi sebagian. (3) ada tanggungan aktif di pool — selesaikan dulu sebelum apply baru.",
  },
  {
    q: "Berapa lama pencairan pinjaman setelah Manager approve?",
    a: "Instan (< 3 detik). Settlement adalah debit LOAN_POOL_ACCOUNT + kredit borrower wallet dalam satu DB transaction. Status loan berubah ke DISBURSED dan saldo wallet naik real-time.",
  },
  {
    q: "Apa bedanya account number 10 digit dengan UUID wallet?",
    a: "UUID adalah identifier internal sistem. Account number 10 digit (format 1234-5678-90) adalah identifier publik untuk transfer — pakai Luhn checksum saat generate, lenient saat lookup agar akun lama (backfill) tetap valid.",
  },
  {
    q: "Bisakah saya pakai satu akun untuk dua peran sekaligus?",
    a: "Tidak. Role enum tunggal per user (WALLET_USER / TELLER / MANAGER / ADMIN). Gunakan akun resmi yang sesuai dengan tugas operasional.",
  },
  {
    q: "Apakah data saya benar-benar tersimpan?",
    a: "Ya, di MySQL via Prisma (CentralBank Core). Tapi ini prototipe akademis RPL 2 — DB bisa di-reset kapan saja untuk tugas demo. Jangan gunakan untuk finansial produksi.",
  },
];

/* ============================================================
   SUB-COMPONENTS
   ============================================================ */

function GuideHero() {
  return (
    <section className="relative pt-28 pb-16 px-6 overflow-hidden">
      <div className="absolute top-1/4 left-1/3 -translate-y-1/2 w-[520px] h-[520px] bg-primary/5 blur-[120px] rounded-full pointer-events-none" />
      <div className="absolute bottom-0 right-0 w-[420px] h-[420px] bg-emerald-500/5 blur-[120px] rounded-full pointer-events-none" />

      <div className="max-w-6xl mx-auto grid lg:grid-cols-12 gap-10 items-center relative z-10">
        {/* Kiri: teks (7 kolom) */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, ease: "easeOut" }}
          className="lg:col-span-7 space-y-6"
        >
          <Link
            href="/"
            className="inline-flex items-center gap-2 text-xs text-muted-foreground hover:text-foreground transition-colors font-mono"
          >
            <ArrowLeft className="w-3 h-3" />
            KEMBALI KE BERANDA
          </Link>

          <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full border border-primary/20 bg-primary/5 text-primary text-xs font-mono">
            <BookOpen className="w-3.5 h-3.5" />
            DOKUMENTASI PENGGUNA · v1.0.0
          </div>

          <h1 className="text-4xl md:text-6xl font-display font-semibold tracking-tight text-foreground leading-[1.05]">
            Cara pakai{" "}
            <span className="text-primary">SmartBank</span>
            <br />
            tanpa trial & error.
          </h1>

          <p className="text-base text-muted-foreground max-w-xl leading-relaxed">
            Panduan lengkap 4 peran end-to-end: dari registrasi, alur transfer P2P, lifecycle
            pinjaman UMKM, sampai kontrol bank sentral. Ikuti sesuai peran Anda, atau baca
            semuanya untuk memahami arsitektur two-tier.
          </p>

          <div className="flex flex-wrap items-center gap-3 pt-2">
            <Link href="#memulai">
              <button className="group inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-primary text-primary-foreground font-semibold hover:bg-primary/90 transition-all hover:scale-[1.02] active:scale-[0.98] shadow-lg shadow-primary/20 text-sm">
                Mulai dari sini
                <ArrowRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
              </button>
            </Link>
            <Link href="#faq">
              <button className="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-secondary text-secondary-foreground font-semibold hover:bg-secondary/80 border border-border transition-all text-sm">
                <Lightbulb className="w-4 h-4" />
                Loncat ke FAQ
              </button>
            </Link>
          </div>

          <div className="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-muted-foreground pt-3 font-mono">
            <span className="inline-flex items-center gap-1.5">
              <Clock className="w-3 h-3 text-primary" />
              baca ± 8 menit
            </span>
            <span className="inline-flex items-center gap-1.5">
              <Layers className="w-3 h-3 text-primary" />
              4 peran · 8 alur
            </span>
            <span className="inline-flex items-center gap-1.5">
              <Zap className="w-3 h-3 text-primary" />
              idempotent by default
            </span>
          </div>
        </motion.div>

        {/* Kanan: visual stat panel (5 kolom) */}
        <motion.div
          initial={{ opacity: 0, x: 24 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.5, delay: 0.1, ease: "easeOut" }}
          className="lg:col-span-5"
        >
          <div className="grid grid-cols-2 gap-3">
            <StatTile icon={Users} label="Peran" value="4" sub="Nasabah / Teller / Manager / Admin" />
            <StatTile icon={CircleDollarSign} label="Cap Pinjaman" value="100k" sub="Outstanding per user" tone="emerald" />
            <StatTile icon={PiggyBank} label="Bunga" value="10%" sub="Flat rate, simple interest" tone="amber" />
            <StatTile icon={Zap} label="Settlement" value="<3s" sub="Atomic DB transaction" tone="blue" />
            <StatTile icon={ShieldCheck} label="Audit" value="100%" sub="Append-only ledger" tone="emerald" className="col-span-2" />
          </div>
        </motion.div>
      </div>
    </section>
  );
}

function StatTile({
  icon: Icon,
  label,
  value,
  sub,
  tone = "primary",
  className = "",
}: {
  icon: typeof Users;
  label: string;
  value: string;
  sub: string;
  tone?: "primary" | "emerald" | "amber" | "blue";
  className?: string;
}) {
  const toneClass = {
    primary: "bg-primary/10 text-primary border-primary/20",
    emerald: "bg-emerald-500/10 text-emerald-500 border-emerald-500/20",
    amber: "bg-amber-500/10 text-amber-500 border-amber-500/20",
    blue: "bg-blue-500/10 text-blue-500 border-blue-500/20",
  }[tone];

  return (
    <div
      className={`relative bg-card/60 backdrop-blur-md border border-border rounded-2xl p-4 overflow-hidden ${className}`}
    >
      <div className="absolute -top-8 -right-8 size-24 bg-primary/5 rounded-full blur-2xl pointer-events-none" />
      <div className="relative z-10 space-y-2">
        <div className={`inline-flex p-1.5 rounded-lg border ${toneClass}`}>
          <Icon className="w-4 h-4" />
        </div>
        <p className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground">
          {label}
        </p>
        <p className="text-2xl font-display font-semibold text-foreground">{value}</p>
        <p className="text-[11px] text-muted-foreground leading-relaxed">{sub}</p>
      </div>
    </div>
  );
}

function GuideToc() {
  const sections = [
    { id: "memulai", label: "Memulai", num: "01", icon: Sparkles },
    { id: "alur-p2p", label: "Alur Transfer P2P", num: "02", icon: ArrowDownUp },
    { id: "pinjaman", label: "Pinjaman UMKM", num: "03", icon: PiggyBank },
    { id: "peran", label: "Panduan per Peran", num: "04", icon: Users },
    { id: "keamanan", label: "Tips Keamanan", num: "05", icon: ShieldCheck },
    { id: "faq", label: "FAQ", num: "06", icon: Lightbulb },
  ];

  const [active, setActive] = useState("memulai");

  useEffect(() => {
    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries
          .filter((e) => e.isIntersecting)
          .sort((a, b) => b.intersectionRatio - a.intersectionRatio);
        if (visible[0]) setActive(visible[0].target.id);
      },
      { rootMargin: "-30% 0px -55% 0px", threshold: [0, 0.25, 0.5, 0.75, 1] },
    );

    sections.forEach((s) => {
      const el = document.getElementById(s.id);
      if (el) observer.observe(el);
    });

    return () => observer.disconnect();
  }, []);

  return (
    <aside className="hidden lg:block sticky top-20 self-start">
      <p className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground mb-4 px-2">
        Daftar Isi
      </p>
      <nav className="space-y-0.5 border-l border-border" aria-label="Table of contents">
        {sections.map((s) => {
          const isActive = active === s.id;
          return (
            <a
              key={s.id}
              href={`#${s.id}`}
              className={`group flex items-center gap-3 pl-4 pr-2 py-2 border-l-2 -ml-px text-sm transition-all ${
                isActive
                  ? "border-primary text-foreground font-semibold"
                  : "border-transparent text-muted-foreground hover:text-foreground"
              }`}
            >
              <span
                className={`text-[10px] font-mono tabular-nums ${
                  isActive ? "text-primary" : "text-muted-foreground/60"
                }`}
              >
                {s.num}
              </span>
              <s.icon className={`w-3.5 h-3.5 ${isActive ? "text-primary" : ""}`} />
              <span>{s.label}</span>
            </a>
          );
        })}
      </nav>

      <div className="mt-8 mx-2 p-4 rounded-xl bg-card/50 border border-border">
        <p className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground mb-2">
          Butuh cepat?
        </p>
        <div className="space-y-1.5 text-xs">
          <Link href="/login" className="flex items-center gap-2 text-foreground hover:text-primary transition-colors">
            <KeyRound className="w-3 h-3" /> Login
          </Link>
          <Link href="/register" className="flex items-center gap-2 text-foreground hover:text-primary transition-colors">
            <Sparkles className="w-3 h-3" /> Register retail
          </Link>
        </div>
      </div>
    </aside>
  );
}

// HandCoinsIcon dihapus — PiggyBank dipakai langsung di TOC.

/* --- Memulai --- */

function OnboardingSection() {
  return (
    <section id="memulai" className="px-6 py-16 scroll-mt-24">
      <SectionHeader
        num="01"
        label="Memulai"
        title="Empat langkah untuk onboard"
        subtitle="Dari registrasi sampai saldo aktif melalui alur operasional resmi."
      />

      {/* Steps zig-zag */}
      <div className="space-y-4 mt-6">
        {ONBOARDING_STEPS.map((s, i) => (
          <motion.div
            key={s.n}
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-50px" }}
            transition={{ duration: 0.4, delay: i * 0.05 }}
            className={`grid md:grid-cols-12 gap-4 md:gap-8 items-start border-t border-border pt-6 ${
              i % 2 === 1 ? "md:[&>*:first-child]:order-2" : ""
            }`}
          >
            <div className="md:col-span-3 flex md:flex-col items-start gap-3">
              <span className="font-mono text-xs text-primary tabular-nums">{s.n}</span>
              <div className="p-2.5 rounded-xl bg-primary/10 text-primary border border-primary/20 shrink-0">
                <s.icon className="w-5 h-5" />
              </div>
            </div>
            <div className="md:col-span-9 space-y-2">
              <h3 className="text-lg font-display font-semibold text-foreground">{s.title}</h3>
              <p className="text-sm text-muted-foreground leading-relaxed max-w-[65ch]">{s.body}</p>
            </div>
          </motion.div>
        ))}
      </div>
    </section>
  );
}

/* --- Alur Transfer P2P --- */

function P2PFlowSection() {
  return (
    <section id="alur-p2p" className="px-6 py-16 scroll-mt-24">
      <SectionHeader
        num="02"
        label="Alur P2P"
        title="Transfer antar wallet dalam 6 langkah"
        subtitle="Setiap langkah punya tombol konfirmasi sendiri. PIN hanya diminta di langkah 5 — sebelum itu Anda masih bisa cancel tanpa efek."
      />

      {/* Flow horizontal */}
      <div className="mt-10 mb-8 overflow-x-auto pb-2">
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 min-w-[640px]">
          {P2P_STEPS.map((step, i) => (
            <motion.div
              key={step.n}
              initial={{ opacity: 0, y: 16 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.3, delay: i * 0.06 }}
              className="relative bg-card/60 backdrop-blur-md border border-border rounded-2xl p-4"
            >
              <div className="flex items-center justify-between mb-3">
                <span className="font-mono text-[10px] text-primary tabular-nums">{step.n}</span>
                <div className="p-1.5 rounded-lg bg-primary/10 text-primary">
                  <step.icon className="w-3.5 h-3.5" />
                </div>
              </div>
              <p className="text-xs font-medium text-foreground leading-snug">{step.label}</p>
              {i < P2P_STEPS.length - 1 && (
                <ArrowRight className="hidden lg:block absolute -right-2 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground/50 z-10" />
              )}
            </motion.div>
          ))}
        </div>
      </div>

      {/* Detail callouts: 2 kolom zig-zag */}
      <div className="grid md:grid-cols-2 gap-4 md:gap-6 mt-8">
        <Callout
          icon={Hash}
          tone="primary"
          title="Identifier penerima"
          body="Bisa pakai nomor telepon (+62...) atau 10 digit account number (format 1234-5678-90). Lookup lenient — akun lama hasil backfill tetap valid walau checksum-nya non-Luhn."
        />
        <Callout
          icon={CircleDollarSign}
          tone="emerald"
          title="Fee engine otomatis"
          body="Total fee dihitung dari fee_rules aktif via GET /fees/quote. Ditampilkan sebelum PIN. Wallet hanya pass-through — Core yang menghitung. Sumber: Bank, Gateway, Marketplace, POS, Tax, dll."
        />
        <Callout
          icon={Fingerprint}
          tone="blue"
          title="Idempotency-Key"
          body="Setiap POST finansial dapat UUID acak di header. Submit dua kali (retry, double-tap, jaringan timeout) = satu transaksi. Tidak mungkin double-debit."
        />
        <Callout
          icon={AlertTriangle}
          tone="amber"
          title="Status PENDING"
          body="Jika Gateway timeout, transaksi tetap PENDING sampai 30 detik lalu otomatis FAILED. Saldo tidak ter-debit (atomic settlement — debit + kredit dalam satu DB transaction)."
        />
      </div>
    </section>
  );
}

/* --- Pinjaman UMKM --- */

function LoanFlowSection() {
  return (
    <section id="pinjaman" className="px-6 py-16 scroll-mt-24">
      <SectionHeader
        num="03"
        label="Pinjaman UMKM"
        title="Lifecycle pinjaman dari apply sampai lunas"
        subtitle="Pool saldo: Rp 9.990.000 dari seed 10.000.000. Cap per user: 100.000. Bunga flat 10%."
      />

      {/* Loan stats */}
      <motion.div
        initial={{ opacity: 0, y: 16 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        className="mt-10 mb-8 grid grid-cols-2 md:grid-cols-4 gap-3"
      >
        <StatTile icon={PiggyBank} label="Pool Saldo" value="9,99M" sub="Tersisa untuk dicairkan" tone="emerald" />
        <StatTile icon={CircleDollarSign} label="Cap per User" value="100k" sub="Outstanding + apply ≤ 100k" tone="amber" />
        <StatTile icon={Banknote} label="Bunga" value="10%" sub="Flat, simple interest" tone="primary" />
        <StatTile icon={Clock} label="Tenor" value="30 hari" sub="Default due_at" tone="blue" />
      </motion.div>

      {/* Vertical flow */}
      <div className="mt-10 space-y-0">
        {LOAN_FLOW.map((step, i) => (
          <motion.div
            key={step.status}
            initial={{ opacity: 0, x: -16 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-50px" }}
            transition={{ duration: 0.4, delay: i * 0.05 }}
            className="relative grid grid-cols-[auto_1fr] md:grid-cols-[120px_1fr] gap-4 md:gap-8 pb-8 last:pb-0"
          >
            {/* Garis vertikal antar step */}
            {i < LOAN_FLOW.length - 1 && (
              <div className="absolute left-[19px] md:left-[59px] top-12 bottom-0 w-px bg-gradient-to-b from-border to-transparent" />
            )}

            <div className="flex flex-col items-start gap-2">
              <div className={`p-2.5 rounded-xl border ${toneBox(step.tone)}`}>
                <step.icon className={`w-5 h-5 ${toneText(step.tone)}`} />
              </div>
              <span className={`text-[10px] font-mono uppercase tracking-widest ${toneText(step.tone)}`}>
                {step.status}
              </span>
            </div>

            <div className="pt-1">
              <p className="text-sm text-muted-foreground leading-relaxed max-w-[65ch]">
                {step.desc}
              </p>
            </div>
          </motion.div>
        ))}
      </div>
    </section>
  );
}

/* --- Panduan per Peran (Bento) --- */

function RolesBentoSection() {
  return (
    <section id="peran" className="px-6 py-16 scroll-mt-24">
      <SectionHeader
        num="04"
        label="Panduan per Peran"
        title="Setiap peran, matriks akses sendiri"
        subtitle="Role enum tunggal per user. Beralih peran memerlukan akun resmi dengan kewenangan terkait."
      />

      <div className="mt-10 grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6">
        {ROLE_BENTO.map((r, i) => (
          <motion.div
            key={r.name}
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-50px" }}
            transition={{ duration: 0.4, delay: i * 0.06 }}
            className={`relative bg-card/60 backdrop-blur-md border-2 ${toneRing(r.tone)} rounded-2xl p-5 md:p-6 overflow-hidden flex flex-col ${r.size}`}
          >
            <div className={`absolute -top-12 -right-12 size-32 ${toneBlob(r.tone)} rounded-full blur-3xl pointer-events-none`} />

            <div className="relative z-10 flex-1 space-y-4">
              <div className="flex items-start justify-between gap-3">
                <div className={`p-2.5 rounded-xl ${toneIconBox(r.tone)}`}>
                  <r.icon className="w-5 h-5" />
                </div>
                <span className={`text-[10px] font-mono tabular-nums ${toneText(r.tone)}`}>
                  {r.n}
                </span>
              </div>

              <div>
                <p className={`text-[10px] font-mono uppercase tracking-widest ${toneText(r.tone)}`}>
                  {r.sub}
                </p>
                <h3 className="text-xl font-display font-semibold text-foreground mt-0.5">
                  {r.name}
                </h3>
              </div>

              <ul className="space-y-1.5">
                {r.bullets.map((b) => (
                  <li
                    key={b}
                    className="flex items-start gap-2 text-xs text-foreground/80 leading-relaxed"
                  >
                    <CheckCircle2 className={`w-3.5 h-3.5 ${toneText(r.tone)} shrink-0 mt-0.5`} />
                    {b}
                  </li>
                ))}
              </ul>

              <p className={`text-[10px] italic text-muted-foreground border-t border-border/50 pt-3 leading-relaxed`}>
                <span className={`font-semibold ${toneText(r.tone)} not-italic`}>Batasan: </span>
                {r.restrict}
              </p>
            </div>

            {r.cta && (
              <div className="relative z-10 pt-2">
                <Link
                  href={r.cta.href}
                  className="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:gap-2 transition-all"
                >
                  {r.cta.label}
                  <ArrowRight className="w-3 h-3" />
                </Link>
              </div>
            )}
          </motion.div>
        ))}
      </div>
    </section>
  );
}

/* --- Tips Keamanan --- */

function SecuritySection() {
  return (
    <section id="keamanan" className="px-6 py-16 scroll-mt-24">
      <SectionHeader
        num="05"
        label="Keamanan"
        title="Standar finansial, bukan kosmetik"
        subtitle="Empat lapisan proteksi yang wajib dipahami sebelum transaksi pertama."
      />

      <div className="mt-10 grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
        {SECURITY_TIPS.map((tip, i) => (
          <motion.div
            key={tip.title}
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-50px" }}
            transition={{ duration: 0.4, delay: i * 0.05 }}
            className="bg-card/60 backdrop-blur-md border border-border rounded-2xl p-5 hover:border-primary/30 transition-colors"
          >
            <div className="flex items-start gap-3">
              <div className="p-2 rounded-lg bg-primary/10 text-primary shrink-0">
                <tip.icon className="w-4 h-4" />
              </div>
              <div className="flex-1 min-w-0">
                <h3 className="text-sm font-display font-semibold text-foreground mb-1">
                  {tip.title}
                </h3>
                <p className="text-xs text-muted-foreground leading-relaxed">{tip.body}</p>
              </div>
            </div>
          </motion.div>
        ))}
      </div>
    </section>
  );
}

/* --- FAQ --- */

function FAQSection() {
  const [openIdx, setOpenIdx] = useState<number | null>(0);

  return (
    <section id="faq" className="px-6 py-16 scroll-mt-24">
      <SectionHeader
        num="06"
        label="FAQ"
        title="Pertanyaan yang sering muncul"
        subtitle="Kalau belum ada di sini, hubungi tim lewat link Source di footer beranda."
      />

      <div className="mt-10 max-w-3xl mx-auto space-y-2">
        {FAQS.map((f, i) => {
          const isOpen = openIdx === i;
          return (
            <motion.div
              key={f.q}
              initial={{ opacity: 0, y: 8 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true }}
              transition={{ duration: 0.3, delay: i * 0.03 }}
              className="bg-card/60 backdrop-blur-md border border-border rounded-xl overflow-hidden"
            >
              <button
                onClick={() => setOpenIdx(isOpen ? null : i)}
                className="w-full flex items-center justify-between gap-4 px-5 py-4 text-left hover:bg-secondary/30 transition-colors"
                aria-expanded={isOpen}
              >
                <div className="flex items-center gap-3">
                  <span className="font-mono text-[10px] text-primary tabular-nums shrink-0">
                    {String(i + 1).padStart(2, "0")}
                  </span>
                  <span className="text-sm font-medium text-foreground">{f.q}</span>
                </div>
                <ChevronDown
                  className={`w-4 h-4 text-muted-foreground shrink-0 transition-transform ${
                    isOpen ? "rotate-180" : ""
                  }`}
                />
              </button>
              <AnimatePresence initial={false}>
                {isOpen && (
                  <motion.div
                    key="content"
                    initial={{ height: 0, opacity: 0 }}
                    animate={{ height: "auto", opacity: 1 }}
                    exit={{ height: 0, opacity: 0 }}
                    transition={{ duration: 0.25, ease: "easeOut" }}
                    className="overflow-hidden"
                  >
                    <div className="px-5 pb-4 pt-1 text-sm text-muted-foreground leading-relaxed border-t border-border/50 pl-12">
                      {f.a}
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>
            </motion.div>
          );
        })}
      </div>
    </section>
  );
}

/* --- CTA --- */

function GuideCTA() {
  return (
    <section className="px-6 py-16">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        whileInView={{ opacity: 1, y: 0 }}
        viewport={{ once: true }}
        className="max-w-5xl mx-auto bg-gradient-to-br from-primary/10 via-card to-emerald-500/5 border border-primary/20 rounded-3xl p-8 md:p-12 text-center space-y-5 relative overflow-hidden"
      >
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[400px] h-[200px] bg-primary/10 blur-[100px] rounded-full pointer-events-none" />
        <div className="relative z-10">
          <Sparkles className="w-9 h-9 text-primary mx-auto mb-3" />
          <h2 className="text-2xl md:text-3xl font-display font-semibold text-foreground">
            Sudah siap praktik langsung?
          </h2>
          <p className="text-sm text-muted-foreground max-w-xl mx-auto">
            Daftar akun retail baru atau masuk menggunakan akun yang diberikan administrator.
          </p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-4">
            <Link href="/register">
              <button className="group inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-primary text-primary-foreground font-semibold hover:bg-primary/90 transition-all shadow-lg shadow-primary/20">
                Daftar Nasabah Baru
                <ArrowRight className="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
              </button>
            </Link>
            <Link href="/login">
              <button className="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-card border border-border text-foreground font-semibold hover:bg-secondary/50 transition-all">
                <KeyRound className="w-4 h-4" />
                Login
              </button>
            </Link>
          </div>
        </div>
      </motion.div>
    </section>
  );
}

/* ============================================================
   HELPER: tone variants
   ============================================================ */

function toneBox(tone: string) {
  return {
    primary: "border-primary/20 bg-primary/5",
    emerald: "border-emerald-500/20 bg-emerald-500/5",
    amber: "border-amber-500/20 bg-amber-500/5",
    rose: "border-rose-500/20 bg-rose-500/5",
    blue: "border-blue-500/20 bg-blue-500/5",
  }[tone] ?? "border-border bg-card/40";
}

function toneText(tone: string) {
  return {
    primary: "text-primary",
    emerald: "text-emerald-500",
    amber: "text-amber-500",
    rose: "text-rose-500",
    blue: "text-blue-500",
  }[tone] ?? "text-muted-foreground";
}

function toneIconBox(tone: string) {
  return {
    primary: "bg-primary/10 text-primary border border-primary/20",
    emerald: "bg-emerald-500/10 text-emerald-500 border border-emerald-500/20",
    amber: "bg-amber-500/10 text-amber-500 border border-amber-500/20",
    rose: "bg-rose-500/10 text-rose-500 border border-rose-500/20",
    blue: "bg-blue-500/10 text-blue-500 border border-blue-500/20",
  }[tone] ?? "bg-secondary text-foreground";
}

function toneRing(tone: string) {
  return {
    primary: "border-primary/20",
    emerald: "border-emerald-500/20",
    amber: "border-amber-500/20",
    rose: "border-rose-500/20",
    blue: "border-blue-500/20",
  }[tone] ?? "border-border";
}

function toneBlob(tone: string) {
  return {
    primary: "bg-primary/10",
    emerald: "bg-emerald-500/10",
    amber: "bg-amber-500/10",
    rose: "bg-rose-500/10",
    blue: "bg-blue-500/10",
  }[tone] ?? "bg-muted";
}

function Callout({
  icon: Icon,
  tone,
  title,
  body,
}: {
  icon: typeof Hash;
  tone: "primary" | "emerald" | "amber" | "blue";
  title: string;
  body: string;
}) {
  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      className="bg-card/60 backdrop-blur-md border border-border rounded-2xl p-5 hover:border-primary/30 transition-colors"
    >
      <div className="flex items-start gap-3">
        <div className={`p-2 rounded-lg ${toneIconBox(tone)} shrink-0`}>
          <Icon className="w-4 h-4" />
        </div>
        <div className="flex-1 min-w-0">
          <h4 className="text-sm font-display font-semibold text-foreground mb-1">{title}</h4>
          <p className="text-xs text-muted-foreground leading-relaxed">{body}</p>
        </div>
      </div>
    </motion.div>
  );
}

function SectionHeader({
  num,
  label,
  title,
  subtitle,
}: {
  num: string;
  label: string;
  title: string;
  subtitle: string;
}) {
  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      whileInView={{ opacity: 1, y: 0 }}
      viewport={{ once: true }}
      className="max-w-3xl mx-auto space-y-3 mb-2"
    >
      <div className="flex items-center gap-3">
        <span className="text-[10px] font-mono text-primary tabular-nums">{num}</span>
        <span className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground">
          {label}
        </span>
      </div>
      <h2 className="text-3xl md:text-4xl font-display font-semibold text-foreground leading-tight">
        {title}
      </h2>
      <p className="text-sm text-muted-foreground max-w-[60ch]">{subtitle}</p>
    </motion.div>
  );
}

/* ============================================================
   PAGE ROOT
   ============================================================ */

export default function GuidePage() {
  return (
    <main className="flex-1 bg-background text-foreground min-h-dvh">
      <GuideHero />

      {/* Layout: sticky TOC + content */}
      <div className="max-w-6xl mx-auto grid lg:grid-cols-12 gap-10 px-6">
        <div className="lg:col-span-3 pt-4">
          <GuideToc />
        </div>

        <div className="lg:col-span-9 min-w-0">
          <OnboardingSection />
          <P2PFlowSection />
          <LoanFlowSection />
          <RolesBentoSection />
          <SecuritySection />
          <FAQSection />
          <GuideCTA />
        </div>
      </div>
    </main>
  );
}
