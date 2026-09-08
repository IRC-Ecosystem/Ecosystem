"use client";

import { motion } from "framer-motion";
import { Building2, Landmark, ShieldCheck, Wallet, ArrowRight } from "lucide-react";

const Tier1 = [
  "Issuance & Burn",
  "Reserve & Pool",
  "Settlement Atomic",
  "Double-entry Ledger",
  "Fee & Tax Engine",
  "Audit Append-only",
];

const Tier2 = [
  "Registrasi & Login",
  "PIN Transaksi",
  "KYC Ringan",
  "Transfer P2P",
  "QR & Invoice",
  "Top Up & Withdraw",
];

export default function ArchitectureDiagram() {
  return (
    <section className="relative px-6 py-24 overflow-hidden">
      <div className="max-w-7xl mx-auto">
        {/* Asymmetric header */}
        <div className="grid lg:grid-cols-[1.2fr_0.8fr] gap-8 items-end mb-16">
          <motion.div
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-80px" }}
            transition={{ duration: 0.6 }}
            className="space-y-3"
          >
            <p className="text-xs font-mono text-primary uppercase tracking-widest">
              Arsitektur Sistem
            </p>
            <h2 className="text-3xl md:text-5xl font-display font-semibold text-foreground leading-[1.1] tracking-tight">
              Two-Tier CBDC:
              <br />
              <span className="text-muted-foreground">Tier-1 & Tier-2</span>
            </h2>
          </motion.div>
          <motion.p
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-80px" }}
            transition={{ duration: 0.6, delay: 0.1 }}
            className="text-sm text-muted-foreground max-w-md leading-relaxed"
          >
            Pemisahan tegas antara otoritas moneter (Central Bank Core) sebagai single source of
            truth dengan provider/PJP (Wallet) sebagai antarmuka pengguna.
          </motion.p>
        </div>

        <div className="grid md:grid-cols-[1fr_auto_1fr] gap-6 md:gap-4 items-stretch [perspective:2000px]">
          {/* Tier 1 — card */}
          <motion.div
            initial={{ opacity: 0, x: -32 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-80px" }}
            transition={{ duration: 0.7, ease: [0.16, 1, 0.3, 1] }}
            className="group relative"
          >
            <div className="relative rounded-3xl border border-white/10 bg-gradient-to-br from-zinc-950 via-neutral-950 to-black backdrop-blur-xl p-7 shadow-[0_30px_80px_-15px_rgba(0,0,0,0.85),inset_0_1px_0_rgba(255,255,255,0.14),inset_0_-1px_0_rgba(255,255,255,0.04)] transition-all duration-500 hover:shadow-[0_40px_100px_-15px_rgba(0,0,0,0.95),inset_0_1px_0_rgba(255,255,255,0.2)] hover:[transform:translateY(-4px)] overflow-hidden">
              {/* Gloss highlight — top half lighter for shine */}
              <div className="absolute inset-x-0 top-0 h-1/2 bg-gradient-to-b from-white/[0.06] to-transparent rounded-t-3xl pointer-events-none" />

              {/* Specular edge — sharp white hairline on top */}
              <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent rounded-t-3xl" />

              {/* Inner refraction (subtle blue accent on top edge) */}
              <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/30 to-transparent rounded-t-3xl" />

              {/* Subtle bottom rim */}
              <div className="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent pointer-events-none" />

              {/* Corner glow (subtle white) */}
              <div className="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-white/5 blur-3xl pointer-events-none" />

              <div className="relative z-10">
                <div className="flex items-center gap-3 mb-5">
                  <div className="p-2.5 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-lg shadow-blue-500/30">
                    <Landmark className="w-5 h-5" />
                  </div>
                  <div>
                    <p className="text-[10px] font-mono uppercase tracking-widest text-primary">
                      Tier 1 · Otoritas
                    </p>
                    <h3 className="text-xl font-display font-semibold text-foreground">
                      CentralBank Core
                    </h3>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-5 leading-relaxed">
                  Core moneter: satu-satunya yang boleh mint, burn, dan menulis ledger final.
                </p>
                <ul className="space-y-2.5">
                  {Tier1.map((item) => (
                    <li
                      key={item}
                      className="flex items-center gap-2.5 text-sm text-foreground/90 group/item"
                    >
                      <span className="size-1.5 rounded-full bg-primary group-hover/item:scale-150 transition-transform" />
                      {item}
                    </li>
                  ))}
                </ul>

                <div className="mt-6 pt-5 border-t border-border/40 flex items-center gap-2 text-[10px] font-mono uppercase tracking-widest text-muted-foreground">
                  <span className="size-1.5 rounded-full bg-emerald-500 animate-pulse" />
                  Single source of truth
                </div>
              </div>
            </div>
          </motion.div>

          {/* Arrow / Bridge — animated flow */}
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            whileInView={{ opacity: 1, scale: 1 }}
            viewport={{ once: true, margin: "-80px" }}
            transition={{ duration: 0.6, delay: 0.2 }}
            className="hidden md:flex flex-col items-center justify-center gap-3 px-2"
          >
            <div className="relative">
              <div className="absolute inset-0 rounded-full bg-emerald-500/20 blur-xl animate-pulse" />
              <div className="relative p-4 rounded-full bg-gradient-to-br from-emerald-500/20 to-cyan-500/20 border border-emerald-500/40 text-emerald-600 dark:text-emerald-400 backdrop-blur-xl">
                <ShieldCheck className="w-6 h-6" />
              </div>
            </div>
            <p className="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 uppercase tracking-widest font-semibold">
              API Gateway
            </p>
            <p className="text-[10px] text-muted-foreground max-w-[160px] leading-relaxed text-center">
              Settlement via payment request tervalidasi & idempotent
            </p>

            {/* Animated data flow */}
            <div className="flex flex-col gap-2 my-2 w-full">
              <div className="flex items-center justify-between gap-2 text-[9px] font-mono text-muted-foreground">
                <span>payment_req</span>
                <ArrowRight className="w-3 h-3 text-primary" />
              </div>
              <div className="relative h-px w-full bg-border overflow-hidden">
                <motion.div
                  animate={{ x: ["-100%", "100%"] }}
                  transition={{ duration: 1.8, repeat: Infinity, ease: "linear" }}
                  className="absolute inset-y-0 left-0 w-1/2 bg-gradient-to-r from-transparent via-primary to-transparent"
                />
              </div>
              <div className="flex items-center justify-between gap-2 text-[9px] font-mono text-muted-foreground">
                <ArrowRight className="w-3 h-3 text-emerald-500 rotate-180" />
                <span>settle ack</span>
              </div>
            </div>
          </motion.div>

          {/* Tier 2 — card */}
          <motion.div
            initial={{ opacity: 0, x: 32 }}
            whileInView={{ opacity: 1, x: 0 }}
            viewport={{ once: true, margin: "-80px" }}
            transition={{ duration: 0.7, ease: [0.16, 1, 0.3, 1], delay: 0.05 }}
            className="group relative"
          >
            <div className="relative rounded-3xl border border-white/10 bg-gradient-to-br from-zinc-950 via-neutral-950 to-black backdrop-blur-xl p-7 shadow-[0_30px_80px_-15px_rgba(0,0,0,0.85),inset_0_1px_0_rgba(255,255,255,0.14),inset_0_-1px_0_rgba(255,255,255,0.04)] transition-all duration-500 hover:shadow-[0_40px_100px_-15px_rgba(0,0,0,0.95),inset_0_1px_0_rgba(255,255,255,0.2)] hover:[transform:translateY(-4px)] overflow-hidden">
              {/* Gloss highlight — top half lighter for shine */}
              <div className="absolute inset-x-0 top-0 h-1/2 bg-gradient-to-b from-white/[0.06] to-transparent rounded-t-3xl pointer-events-none" />

              {/* Specular edge — sharp white hairline on top */}
              <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent rounded-t-3xl" />

              {/* Inner refraction (subtle cyan accent on top edge) */}
              <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-cyan-400/40 to-transparent rounded-t-3xl" />

              {/* Subtle bottom rim */}
              <div className="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent pointer-events-none" />

              {/* Corner glow (subtle white) */}
              <div className="absolute -top-10 -left-10 w-40 h-40 rounded-full bg-white/5 blur-3xl pointer-events-none" />

              <div className="relative z-10">
                <div className="flex items-center gap-3 mb-5">
                  <div className="p-2.5 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 text-white shadow-lg shadow-blue-500/30">
                    <Wallet className="w-5 h-5" />
                  </div>
                  <div>
                    <p className="text-[10px] font-mono uppercase tracking-widest text-blue-500">
                      Tier 2 · Provider
                    </p>
                    <h3 className="text-xl font-display font-semibold text-foreground">
                      SmartBank Wallet
                    </h3>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mb-5 leading-relaxed">
                  Antarmuka pengguna: registrasi, UX, payment request ke Core. Tidak boleh mint/burn.
                </p>
                <ul className="space-y-2.5">
                  {Tier2.map((item) => (
                    <li
                      key={item}
                      className="flex items-center gap-2.5 text-sm text-foreground/90 group/item"
                    >
                      <span className="size-1.5 rounded-full bg-blue-500 group-hover/item:scale-150 transition-transform" />
                      {item}
                    </li>
                  ))}
                </ul>

                <div className="mt-6 pt-5 border-t border-border/40 flex items-center gap-2 text-[10px] font-mono uppercase tracking-widest text-muted-foreground">
                  <span className="size-1.5 rounded-full bg-blue-500 animate-pulse" />
                  Read-only dari Core
                </div>
              </div>
            </div>
          </motion.div>
        </div>

        <motion.p
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6, delay: 0.4 }}
          className="text-center text-xs text-muted-foreground mt-10 flex items-center justify-center gap-2 font-mono uppercase tracking-widest"
        >
          <Building2 className="w-3 h-3" />
          Singleness of money · Programmable payments · Tanpa programmable money retail
        </motion.p>
      </div>
    </section>
  );
}
