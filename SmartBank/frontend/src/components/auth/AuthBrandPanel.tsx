"use client";

import { motion, useMotionValue, useSpring, useTransform } from "framer-motion";
import {
  Building2,
  Database,
  Eye,
  Fingerprint,
  KeyRound,
  Layers,
  Lock,
  ShieldCheck,
} from "lucide-react";
import { CreditCard3D } from "@/components/landing/CreditCard3D";
import Link from "next/link";
import { ThemeToggle } from "@/components/theme-toggle";

const FEATURES = [
  { icon: KeyRound, label: "Idempotent" },
  { icon: Database, label: "Append-only" },
  { icon: Eye, label: "KYC masked" },
  { icon: Fingerprint, label: "JWT + PIN" },
  { icon: Lock, label: "AES-256" },
  { icon: Layers, label: "Multi-tier" },
];

export default function AuthBrandPanel() {
  // Parallax tilt for 3D card
  const x = useMotionValue(0);
  const y = useMotionValue(0);
  const rotateX = useTransform(y, [-100, 100], [10, -10]);
  const rotateY = useTransform(x, [-100, 100], [-12, 12]);
  const springX = useSpring(rotateX, { stiffness: 100, damping: 20 });
  const springY = useSpring(rotateY, { stiffness: 100, damping: 20 });

  const handleMove = (e: React.MouseEvent<HTMLDivElement>) => {
    const r = e.currentTarget.getBoundingClientRect();
    x.set(e.clientX - r.left - r.width / 2);
    y.set(e.clientY - r.top - r.height / 2);
  };
  const handleLeave = () => {
    x.set(0);
    y.set(0);
  };

  return (
    <div
      className="relative h-full min-h-dvh bg-gradient-to-br from-blue-50/70 via-white to-cyan-50/70 dark:from-slate-950 dark:via-slate-900 dark:to-blue-950/30 border-r border-border overflow-hidden flex flex-col p-6 lg:p-8 [perspective:1500px]"
    >
      {/* Subtle ambient blobs */}
      <div className="absolute top-0 left-0 w-[420px] h-[420px] bg-blue-500/12 dark:bg-primary/15 blur-[130px] rounded-full pointer-events-none animate-[float_20s_ease-in-out_infinite]" />
      <div className="absolute bottom-0 right-0 w-[340px] h-[340px] bg-cyan-500/12 dark:bg-cyan-500/12 blur-[130px] rounded-full pointer-events-none animate-[float_25s_ease-in-out_infinite_reverse]" />

      {/* Grid overlay */}
      <div
        className="absolute inset-0 opacity-[0.04] dark:opacity-[0.06] pointer-events-none"
        style={{
          backgroundImage:
            "linear-gradient(to right, currentColor 1px, transparent 1px), linear-gradient(to bottom, currentColor 1px, transparent 1px)",
          backgroundSize: "60px 60px",
        }}
      />

      {/* ---- TOP: Logo (→ landing) + Live + Theme toggle ---- */}
      <div className="relative z-10 flex items-center justify-between gap-2.5 shrink-0">
        <Link
          href="/"
          className="group flex items-center gap-2.5 rounded-lg px-2 py-1.5 -mx-2 transition-colors hover:bg-secondary/40"
          aria-label="Kembali ke landing page"
        >
          <div className="relative size-9 rounded-xl bg-gradient-to-br from-primary to-blue-700 dark:from-blue-500 dark:to-blue-700 flex items-center justify-center text-primary-foreground shadow-lg shadow-primary/25 transition-transform duration-300 group-hover:scale-105 group-hover:rotate-3">
            <Building2 className="w-4 h-4" />
            <div className="absolute inset-0 rounded-xl bg-gradient-to-t from-transparent to-white/20 pointer-events-none" />
          </div>
          <div>
            <p className="font-display font-bold text-foreground text-base leading-none tracking-tight">
              SmartBank
            </p>
            <p className="text-[9px] font-mono uppercase tracking-widest text-muted-foreground mt-0.5">
              v1.0.0
            </p>
          </div>
        </Link>

        <div className="flex items-center gap-2">
          <div className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 backdrop-blur-sm">
            <span className="relative flex size-1.5">
              <span className="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-75" />
              <span className="relative inline-flex size-1.5 rounded-full bg-emerald-500" />
            </span>
            <span className="text-[10px] font-mono uppercase tracking-widest text-emerald-700 dark:text-emerald-400 font-semibold">
              Live
            </span>
          </div>
          <ThemeToggle />
        </div>
      </div>

      {/* ---- TAGLINE (compact) ---- */}
      <motion.div
        initial={{ opacity: 0, y: 12 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5, delay: 0.1 }}
        className="relative z-10 mt-6 shrink-0 space-y-1.5"
      >
        <p className="text-[10px] font-mono text-primary uppercase tracking-widest">
          Two-Tier CBDC
        </p>
        <h2 className="text-xl lg:text-2xl font-display font-semibold text-foreground leading-[1.15] tracking-tight">
          Akses terminal ke{" "}
          <span className="bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-500 dark:from-blue-400 dark:via-blue-500 dark:to-cyan-400 bg-clip-text text-transparent">
            jaringan uang digital.
          </span>
        </h2>
      </motion.div>

      {/* ---- 3D CREDIT CARD (hero element) ---- */}
      <motion.div
        initial={{ opacity: 0, y: 24, rotateX: 12 }}
        animate={{ opacity: 1, y: 0, rotateX: 0 }}
        transition={{ duration: 0.8, delay: 0.2, ease: [0.16, 1, 0.3, 1] }}
        className="relative z-10 mt-6 mb-6 flex-1 min-h-0 flex items-center justify-center [perspective:1500px]"
        onMouseMove={handleMove}
        onMouseLeave={handleLeave}
      >
        <motion.div
          style={{ rotateX: springX, rotateY: springY, transformStyle: "preserve-3d" }}
          animate={{ y: [0, -6, 0] }}
          transition={{ duration: 5, repeat: Infinity, ease: "easeInOut" }}
        >
          <CreditCard3D
            width={480}
            variant="blue"
            holderName="A. WIJAYA K."
            last4="8472"
            validThru="12/28"
          />
        </motion.div>
      </motion.div>

      {/* ---- FEATURE PILLS (slim, no clutter) ---- */}
      <motion.div
        initial={{ opacity: 0, y: 12 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5, delay: 0.4 }}
        className="relative z-10 shrink-0"
      >
        <div className="flex flex-wrap gap-1.5">
          {FEATURES.map((f) => (
            <div
              key={f.label}
              className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-slate-200 dark:border-white/10 bg-white/70 dark:bg-slate-900/40 backdrop-blur-sm"
            >
              <f.icon className="w-3 h-3 text-primary" />
              <span className="text-[10px] font-mono text-foreground/80 tracking-wide">
                {f.label}
              </span>
            </div>
          ))}
        </div>
      </motion.div>
    </div>
  );
}
