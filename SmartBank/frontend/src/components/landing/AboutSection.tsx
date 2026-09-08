"use client";

import { motion } from "framer-motion";
import { BookOpen, Layers, Lock } from "lucide-react";

const pillars = [
  {
    icon: Layers,
    title: "Single Source of Truth",
    body:
      "CentralBank Core adalah satu-satunya pihak yang boleh menulis ledger final. Wallet hanya mengirim payment request, Core yang memvalidasi dan settle.",
  },
  {
    icon: Lock,
    title: "Idempotent & Atomic",
    body:
      "Setiap transaksi finansial membawa Idempotency-Key unik. Debit & kredit terjadi dalam satu database transaction, tidak mungkin double-submit atau saldo tidak seimbang.",
  },
  {
    icon: BookOpen,
    title: "Audit Append-Only",
    body:
      "Ledger tidak dapat dihapus atau diedit. Koreksi hanya melalui reversal atau refund transaction yang tercatat di audit log dengan reason code.",
  },
];

export default function AboutSection() {
  return (
    <section className="relative px-6 py-24 overflow-hidden">
      {/* Subtle background accent */}
      <div className="absolute top-1/2 left-0 w-[400px] h-[400px] rounded-full bg-blue-500/5 dark:bg-blue-500/10 blur-3xl pointer-events-none" />

      <div className="max-w-7xl mx-auto grid lg:grid-cols-[0.9fr_1.1fr] gap-16 items-center relative">
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          whileInView={{ opacity: 1, x: 0 }}
          viewport={{ once: true, margin: "-80px" }}
          transition={{ duration: 0.6 }}
          className="space-y-6"
        >
          <div className="space-y-3">
            <p className="text-xs font-mono text-primary uppercase tracking-widest">
              Tentang SmartBank
            </p>
            <h2 className="text-3xl md:text-5xl font-display font-semibold text-foreground leading-[1.1] tracking-tight">
              Prototipe akademis CBDC two-tier dengan{" "}
              <span className="bg-gradient-to-br from-blue-500 to-cyan-500 bg-clip-text text-transparent">
                prinsip bank sentral modern.
              </span>
            </h2>
          </div>
          <p className="text-sm text-muted-foreground leading-relaxed max-w-[55ch]">
            SmartBank adalah platform Central Bank Digital Currency terintegrasi.
            Mengadopsi arsitektur two-tier: Tier-1 CentralBank Core mengelola suplai uang,
            reserve, dan ledger. Tier-2 SmartBank Wallet menjadi antarmuka pengguna untuk
            transfer, QR payment, top-up, withdrawal, dan pinjaman UMKM.
          </p>
          <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-destructive/5 border border-destructive/20 text-xs">
            <span className="size-1.5 rounded-full bg-destructive animate-pulse" />
            <span className="text-muted-foreground">
              Bukan sistem finansial produksi. Tidak terhubung dengan BI, BCA, atau bank nyata.
            </span>
          </div>
        </motion.div>

        <div className="space-y-4 [perspective:1500px]">
          {pillars.map((p, i) => (
            <motion.div
              key={p.title}
              initial={{ opacity: 0, y: 16 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, margin: "-80px" }}
              transition={{ delay: i * 0.1, duration: 0.6, ease: [0.16, 1, 0.3, 1] }}
              className="group relative"
            >
              <div className="relative rounded-2xl border border-slate-200 dark:border-border/60 bg-white dark:bg-card/60 backdrop-blur-xl p-5 shadow-[0_10px_30px_-15px_rgba(2,6,23,0.1),inset_0_1px_0_rgba(255,255,255,0.6)] transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-[0_20px_50px_-15px_rgba(37,99,235,0.3)]">
                {/* Inner refraction line */}
                <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 dark:via-white/10 to-transparent rounded-t-2xl" />

                <div className="flex items-start gap-4">
                  <div className="relative shrink-0">
                    <div className="absolute inset-0 rounded-xl bg-primary/20 blur-md group-hover:bg-primary/40 transition-colors" />
                    <div className="relative p-2.5 rounded-xl bg-gradient-to-br from-blue-500/10 to-cyan-500/10 border border-primary/20 text-primary">
                      <p.icon className="w-5 h-5" />
                    </div>
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1.5">
                      <h3 className="text-base font-display font-semibold text-foreground">
                        {p.title}
                      </h3>
                      <span className="text-[10px] font-mono text-muted-foreground">
                        {String(i + 1).padStart(2, "0")}
                      </span>
                    </div>
                    <p className="text-sm text-muted-foreground leading-relaxed">{p.body}</p>
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
