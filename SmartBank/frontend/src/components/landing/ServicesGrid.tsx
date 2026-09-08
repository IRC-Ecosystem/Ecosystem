"use client";

import { motion } from "framer-motion";
import {
  ArrowRightLeft,
  Banknote,
  HandCoins,
  History,
  QrCode,
  Wallet,
} from "lucide-react";

const services = [
  {
    icon: ArrowRightLeft,
    title: "Transfer P2P",
    body: "Kirim dana antar wallet dengan fee transparan. Validasi saldo via Core sebelum settlement.",
    gradient: "from-blue-500 to-cyan-500",
    tone: "primary" as const,
  },
  {
    icon: QrCode,
    title: "Bayar QR / Invoice",
    body: "Scan QR merchant atau input invoice ID. Konfirmasi fee & pajak sebelum konfirmasi PIN.",
    gradient: "from-emerald-500 to-teal-500",
    tone: "emerald" as const,
  },
  {
    icon: Wallet,
    title: "Top Up Saldo",
    body: "Simulasi distribusi dari Reserve atau Provider Settlement sesuai aturan tugas.",
    gradient: "from-sky-500 to-blue-600",
    tone: "blue" as const,
  },
  {
    icon: Banknote,
    title: "Tarik Tunai",
    body: "Simulasi cash-out ke account system. Tercatat sebagai withdrawal di ledger Core.",
    gradient: "from-indigo-500 to-purple-500",
    tone: "indigo" as const,
  },
  {
    icon: HandCoins,
    title: "Pinjaman UMKM",
    body: "Limit 100.000 / user, bunga 10%. Teller screening ≤50k, Manager final-approve.",
    gradient: "from-amber-500 to-orange-500",
    tone: "amber" as const,
  },
  {
    icon: History,
    title: "Mutasi Rekening",
    body: "Riwayat transaksi settled, pending, failed, reversed, refunded. Filter tanggal & status.",
    gradient: "from-rose-500 to-pink-500",
    tone: "rose" as const,
  },
];

export default function ServicesGrid() {
  return (
    <section className="relative px-6 py-24 overflow-hidden">
      <div className="max-w-7xl mx-auto">
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-80px" }}
          transition={{ duration: 0.6 }}
          className="max-w-2xl mb-14 space-y-3"
        >
          <p className="text-xs font-mono text-primary uppercase tracking-widest">
            Layanan Nasabah
          </p>
          <h2 className="text-3xl md:text-5xl font-display font-semibold text-foreground leading-[1.1] tracking-tight">
            Fitur lengkap dalam{" "}
            <span className="bg-gradient-to-br from-blue-500 to-cyan-500 bg-clip-text text-transparent">
              satu wallet
            </span>
          </h2>
          <p className="text-sm text-muted-foreground max-w-[55ch] leading-relaxed">
            Enam fitur utama yang bisa langsung dipakai nasabah retail setelah registrasi dan KYC.
          </p>
        </motion.div>

        {/* Asymmetric Bento grid — 3 + 3 split, but with varying sizes */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 [perspective:2000px]">
          {services.map((s, i) => (
            <motion.div
              key={s.title}
              initial={{ opacity: 0, y: 16 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, margin: "-60px" }}
              transition={{
                delay: i * 0.06,
                duration: 0.6,
                ease: [0.16, 1, 0.3, 1],
              }}
              className="group relative"
            >
              <div className="relative h-full rounded-2xl border border-slate-200 dark:border-border/60 bg-white dark:bg-card/60 backdrop-blur-xl p-6 shadow-[0_10px_30px_-15px_rgba(2,6,23,0.1),inset_0_1px_0_rgba(255,255,255,0.6)] transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-[0_20px_50px_-15px_rgba(37,99,235,0.3)] overflow-hidden">
                {/* Inner refraction */}
                <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 dark:via-white/10 to-transparent" />

                {/* Hover glow */}
                <div
                  className={`absolute -top-20 -right-20 w-40 h-40 rounded-full bg-gradient-to-br ${s.gradient} opacity-0 group-hover:opacity-20 blur-3xl transition-opacity duration-500`}
                />

                <div className="relative z-10">
                  <div className="flex items-start justify-between mb-5">
                    <div
                      className={`relative p-3 rounded-xl bg-gradient-to-br ${s.gradient} text-white shadow-lg`}
                    >
                      <s.icon className="w-5 h-5" />
                      <div className="absolute inset-0 rounded-xl bg-gradient-to-t from-transparent to-white/20 pointer-events-none" />
                    </div>
                    <span className="text-[10px] font-mono text-muted-foreground tabular-nums">
                      {String(i + 1).padStart(2, "0")} / 06
                    </span>
                  </div>

                  <h3 className="text-lg font-display font-semibold text-foreground mb-2 tracking-tight">
                    {s.title}
                  </h3>
                  <p className="text-sm text-muted-foreground leading-relaxed">{s.body}</p>

                  {/* Bottom progress line */}
                  <div className="mt-5 h-px bg-border/40 overflow-hidden">
                    <motion.div
                      initial={{ scaleX: 0 }}
                      whileInView={{ scaleX: 1 }}
                      viewport={{ once: true }}
                      transition={{ delay: i * 0.06 + 0.3, duration: 0.8 }}
                      className={`h-full bg-gradient-to-r ${s.gradient} origin-left`}
                    />
                  </div>
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      </div>
    </section>
  );
}
