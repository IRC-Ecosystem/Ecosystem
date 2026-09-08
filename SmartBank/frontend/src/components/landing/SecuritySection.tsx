"use client";

import { motion } from "framer-motion";
import { Fingerprint, KeyRound, Lock, ScanSearch, ShieldCheck, Users } from "lucide-react";

const items = [
  {
    icon: KeyRound,
    title: "Password & PIN terpisah",
    body: "Password untuk login. PIN 6-digit untuk transaksi finansial. Keduanya di-hash (bcrypt) dan tidak pernah disimpan plain.",
  },
  {
    icon: Fingerprint,
    title: "Idempotency-Key",
    body: "Setiap POST/PUT finansial wajib menyertakan Idempotency-Key. Submit dua kali = satu transaksi, tidak mungkin double-debit.",
  },
  {
    icon: Lock,
    title: "JWT + Service Token",
    body: "Autentikasi user via JWT. Service-to-service via service token signed. Rate limit per IP & per user.",
  },
  {
    icon: ScanSearch,
    title: "Audit Log Append-Only",
    body: "Setiap aksi admin freeze, reversal, atau adjustment tercatat dengan reason code, actor_id, request_id.",
  },
  {
    icon: Users,
    title: "Role-Based Access Control",
    body: "4 peran: User, Teller, Manager, Central Bank Admin. Masing-masing punya scope endpoint sesuai matriks otorisasi.",
  },
  {
    icon: ShieldCheck,
    title: "Privacy Masking",
    body: "Nama penerima di-masking sebelum transfer. Data KYC tidak ditampilkan ke merchant. Audit regulator hanya untuk Admin.",
  },
];

export default function SecuritySection() {
  return (
    <section className="relative px-6 py-24 overflow-hidden">
      {/* Background shield silhouette */}
      <div className="absolute inset-0 pointer-events-none opacity-[0.04] dark:opacity-[0.06]">
        <svg className="absolute -right-32 top-1/4 w-[600px] h-[600px]" viewBox="0 0 200 200" fill="currentColor">
          <path d="M100 5 L185 40 V100 C185 145 145 180 100 195 C55 180 15 145 15 100 V40 Z" />
        </svg>
      </div>

      <div className="max-w-7xl mx-auto">
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-80px" }}
          transition={{ duration: 0.6 }}
          className="max-w-2xl mb-14 space-y-3"
        >
          <p className="text-xs font-mono text-primary uppercase tracking-widest">
            Keamanan
          </p>
          <h2 className="text-3xl md:text-5xl font-display font-semibold text-foreground leading-[1.1] tracking-tight">
            Standar keamanan finansial,{" "}
            <span className="text-muted-foreground">bukan cuma kosmetik</span>
          </h2>
          <p className="text-sm text-muted-foreground max-w-[55ch] leading-relaxed">
            Enam lapisan proteksi yang wajib diimplementasi untuk uang digital.
          </p>
        </motion.div>

        {/* Hexagonal-ish staggered layout */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-5">
          {items.map((it, i) => (
            <motion.div
              key={it.title}
              initial={{ opacity: 0, y: 24, scale: 0.95 }}
              whileInView={{ opacity: 1, y: 0, scale: 1 }}
              viewport={{ once: true, margin: "-60px" }}
              transition={{ delay: i * 0.06, duration: 0.5, ease: [0.16, 1, 0.3, 1] }}
              className="group relative"
            >
              <div className="relative h-full rounded-2xl border border-slate-200 dark:border-border/60 bg-white dark:bg-card/60 backdrop-blur-xl p-5 shadow-[0_10px_30px_-15px_rgba(2,6,23,0.1),inset_0_1px_0_rgba(255,255,255,0.6)] transition-all duration-500 hover:border-primary/40 hover:shadow-[0_20px_50px_-15px_rgba(37,99,235,0.3)] hover:-translate-y-1 overflow-hidden">
                {/* Inner refraction */}
                <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 dark:via-white/10 to-transparent" />

                {/* Scanning line on hover (CSS transition, no infinite keyframe) */}
                <div className="absolute inset-y-0 -left-1 w-1 bg-gradient-to-b from-transparent via-primary to-transparent opacity-0 group-hover:opacity-100 group-hover:left-full transition-all duration-700 ease-in-out pointer-events-none" />

                <div className="flex items-start gap-4 relative z-10">
                  <div className="relative shrink-0">
                    <div className="absolute inset-0 rounded-lg bg-primary/20 blur-md opacity-0 group-hover:opacity-100 transition-opacity" />
                    <div className="relative p-2 rounded-lg bg-gradient-to-br from-blue-500/10 to-primary/10 border border-primary/20 text-primary">
                      <it.icon className="w-5 h-5" />
                    </div>
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between mb-1.5 gap-2">
                      <h3 className="text-base font-display font-semibold text-foreground leading-tight">
                        {it.title}
                      </h3>
                      <span className="text-[10px] font-mono text-muted-foreground tabular-nums shrink-0">
                        L-{String(i + 1).padStart(2, "0")}
                      </span>
                    </div>
                    <p className="text-sm text-muted-foreground leading-relaxed">{it.body}</p>
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
