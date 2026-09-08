"use client";

import { motion } from "framer-motion";
import {
  AlertTriangle,
  ArrowRight,
  Code2,
  ExternalLink,
  Mail,
  ShieldCheck,
} from "lucide-react";
import Link from "next/link";

export default function CTAFooter() {
  return (
    <footer className="relative px-6 py-16 border-t border-border/40 mt-10 overflow-hidden">
      {/* Subtle background */}
      <div className="absolute inset-0 pointer-events-none opacity-30">
        <div className="absolute top-0 left-1/4 w-[500px] h-[500px] rounded-full bg-blue-500/5 blur-3xl" />
        <div className="absolute bottom-0 right-1/4 w-[500px] h-[500px] rounded-full bg-cyan-500/5 blur-3xl" />
      </div>

      <div className="max-w-7xl mx-auto space-y-10 relative z-10">
        {/* CTA hero card */}
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-80px" }}
          transition={{ duration: 0.7, ease: [0.16, 1, 0.3, 1] }}
          className="relative"
        >
          <div className="relative rounded-3xl border border-primary/30 bg-gradient-to-br from-blue-50/60 via-white to-cyan-50/60 dark:from-blue-500/10 dark:via-primary/5 dark:to-cyan-500/10 backdrop-blur-xl p-10 md:p-14 text-center space-y-6 overflow-hidden shadow-[0_25px_60px_-20px_rgba(37,99,235,0.2),inset_0_1px_0_rgba(255,255,255,0.6)]">
            {/* Inner refraction */}
            <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/50 to-transparent" />

            {/* Animated mesh background */}
            <div className="absolute inset-0 pointer-events-none">
              <motion.div
                animate={{ scale: [1, 1.2, 1], opacity: [0.3, 0.5, 0.3] }}
                transition={{ duration: 6, repeat: Infinity, ease: "easeInOut" }}
                className="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-primary/20 rounded-full blur-3xl"
              />
            </div>

            <div className="relative z-10 space-y-6">
              <motion.div
                animate={{ y: [0, -6, 0] }}
                transition={{ duration: 3, repeat: Infinity, ease: "easeInOut" }}
                className="inline-flex size-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 items-center justify-center text-white shadow-lg shadow-blue-500/40 mx-auto"
              >
                <ShieldCheck className="w-8 h-8" />
                <div className="absolute inset-0 rounded-2xl bg-gradient-to-t from-transparent to-white/20 pointer-events-none" />
              </motion.div>

              <div className="space-y-2">
                <h2 className="text-3xl md:text-4xl font-display font-semibold text-foreground tracking-tight">
                  Siap mencoba{" "}
                  <span className="bg-gradient-to-br from-blue-500 to-cyan-500 bg-clip-text text-transparent">
                    SmartBank
                  </span>
                  ?
                </h2>
                <p className="text-sm text-muted-foreground max-w-xl mx-auto leading-relaxed">
                  Registrasi gratis, KYC ringan, langsung dapat wallet address &amp; bisa
                  transfer ke pengguna lain di ekosistem SmartBank.
                </p>
              </div>

              <div className="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                <Link href="/register" className="group">
                  <button className="relative inline-flex items-center gap-2 px-7 py-3.5 rounded-xl bg-primary text-primary-foreground font-semibold transition-all duration-300 hover:scale-[1.03] active:scale-[0.97] shadow-[0_10px_30px_-10px_rgba(37,99,235,0.5),inset_0_1px_0_rgba(255,255,255,0.15)] hover:shadow-[0_15px_40px_-10px_rgba(37,99,235,0.7),inset_0_1px_0_rgba(255,255,255,0.2)] overflow-hidden">
                    <span className="relative z-10 flex items-center gap-2">
                      Daftar Sekarang
                      <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform duration-300" />
                    </span>
                    <span className="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/20 to-transparent" />
                  </button>
                </Link>
                <Link href="/guide">
                  <button className="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl text-foreground font-semibold border border-border/60 hover:border-primary/40 transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]">
                    Baca Panduan
                  </button>
                </Link>
              </div>
            </div>
          </div>
        </motion.div>

        {/* Disclaimer */}
        <motion.div
          initial={{ opacity: 0 }}
          whileInView={{ opacity: 1 }}
          viewport={{ once: true }}
          transition={{ duration: 0.6 }}
          className="rounded-2xl border border-destructive/20 bg-destructive/[0.04] backdrop-blur-md p-5 flex items-start gap-4"
        >
          <div className="shrink-0 p-2 rounded-lg bg-destructive/10 text-destructive">
            <AlertTriangle className="w-4 h-4" />
          </div>
          <div className="text-xs text-muted-foreground leading-relaxed">
            <p className="font-semibold text-foreground mb-1">Disclaimer Akademis</p>
            SmartBank adalah prototipe akademis untuk tugas besar RPL 2. Tidak terhubung dengan
            Bank Indonesia, BCA, bank komersial nyata, atau payment network nyata. Semua saldo,
            transaksi, dan identitas adalah simulasi. Jangan gunakan untuk aktivitas finansial
            produksi.
          </div>
        </motion.div>

        {/* Footer bar */}
        <div className="flex flex-col md:flex-row items-center justify-between gap-4 pt-4 border-t border-border/40">
          <div className="flex items-center gap-3">
            <div className="size-8 rounded-lg bg-gradient-to-br from-primary to-blue-700 flex items-center justify-center text-primary-foreground shadow-md shadow-primary/30">
              <ShieldCheck className="w-4 h-4" />
            </div>
            <span className="text-sm font-display font-semibold text-foreground">SmartBank</span>
            <span className="text-xs text-muted-foreground hidden sm:inline">
              · Two-tier CBDC Prototype · RPL 2
            </span>
          </div>
          <div className="flex items-center gap-4 text-xs text-muted-foreground">
            <Link href="/guide" className="hover:text-foreground transition-colors">
              Panduan
            </Link>
            <Link href="/login" className="hover:text-foreground transition-colors">
              Masuk
            </Link>
            <a
              href="https://github.com/RPL2-Becode/SmartBank"
              target="_blank"
              rel="noreferrer"
              className="hover:text-foreground transition-colors flex items-center gap-1.5 group"
            >
              <ExternalLink className="w-3.5 h-3.5 group-hover:scale-110 group-hover:rotate-12 transition-transform" />
              Source
            </a>
            <a
              href="mailto:team@smartbank.local"
              className="hover:text-foreground transition-colors flex items-center gap-1.5 group"
            >
              <Mail className="w-3.5 h-3.5 group-hover:scale-110 transition-transform" />
              Kontak
            </a>
            <Link
              href="/guide"
              className="hover:text-foreground transition-colors flex items-center gap-1.5 group"
            >
              <Code2 className="w-3.5 h-3.5 group-hover:scale-110 transition-transform" />
              Docs
            </Link>
          </div>
          <p className="text-[10px] text-muted-foreground font-mono uppercase tracking-wider">
            © {new Date().getFullYear()} SmartBank Team
          </p>
        </div>
      </div>
    </footer>
  );
}
