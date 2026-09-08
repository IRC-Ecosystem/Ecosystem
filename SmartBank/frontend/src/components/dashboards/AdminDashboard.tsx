"use client";

import { useCallback, useEffect, useState } from "react";
import {
  AlertTriangle,
  ArrowRight,
  Banknote,
  CheckCircle2,
  CircleDollarSign,
  Clock,
  Flame,
  Landmark,
  Layers,
  RefreshCw,
  Scale,
  ScrollText,
  ShieldCheck,
  Sparkles,
  TrendingDown,
  TrendingUp,
  Wallet,
} from "lucide-react";
import { motion } from "framer-motion";
import { fetchApi } from "@/lib/api";

type Supply = {
  total_supply: string | number;
  reserve_balance: string | number;
  circulating_supply: string | number;
  sink_or_burn_accounting: string | number;
  invariant_total: string | number;
  invariant_valid: boolean;
};

type AuditLog = {
  id: string;
  actorUserId: string | null;
  serviceName: string;
  action: string;
  targetType: string;
  targetId: string;
  createdAt: string;
};

const money = (value: string | number | undefined | null) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value || 0));

const unwrap = <T,>(response: { data?: T } | T): T =>
  typeof response === "object" && response !== null && "data" in response ? (response as { data: T }).data : (response as T);

/* -------------------------------------------------------------------------- */
/*  Animated counter — counts up on mount                                      */
/* -------------------------------------------------------------------------- */
function AnimatedNumber({ value, duration = 1.4 }: { value: number; duration?: number }) {
  const [display, setDisplay] = useState(0);
  useEffect(() => {
    const start = performance.now();
    let raf = 0;
    const tick = (now: number) => {
      const t = Math.min(1, (now - start) / (duration * 1000));
      const eased = 1 - Math.pow(1 - t, 3);
      setDisplay(value * eased);
      if (t < 1) raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(raf);
  }, [value, duration]);
  return <>{money(display)}</>;
}

/* -------------------------------------------------------------------------- */
/*  Circular supply gauge — animated SVG arc                                  */
/* -------------------------------------------------------------------------- */
function SupplyGauge({
  value,
  max,
  label,
  color,
}: {
  value: number;
  max: number;
  label: string;
  color: string;
}) {
  const pct = max > 0 ? Math.min(100, (value / max) * 100) : 0;
  const r = 38;
  const c = 2 * Math.PI * r;
  const offset = c - (pct / 100) * c;
  return (
    <div className="relative inline-flex items-center justify-center">
      <svg width="92" height="92" viewBox="0 0 100 100" className="-rotate-90">
        <circle cx="50" cy="50" r={r} fill="none" stroke="currentColor" strokeWidth="6" className="text-slate-200 dark:text-slate-800" />
        <motion.circle
          cx="50"
          cy="50"
          r={r}
          fill="none"
          stroke={color}
          strokeWidth="6"
          strokeLinecap="round"
          strokeDasharray={c}
          initial={{ strokeDashoffset: c }}
          animate={{ strokeDashoffset: offset }}
          transition={{ duration: 1.6, ease: [0.16, 1, 0.3, 1] }}
        />
      </svg>
      <div className="absolute inset-0 flex flex-col items-center justify-center">
        <span className="font-mono text-[10px] font-bold tabular-nums text-foreground leading-none">
          {pct.toFixed(1)}
        </span>
        <span className="text-[8px] font-mono uppercase tracking-widest text-muted-foreground mt-0.5">%</span>
      </div>
      <span className="absolute -bottom-5 left-1/2 -translate-x-1/2 text-[9px] font-mono uppercase tracking-widest text-muted-foreground whitespace-nowrap">
        {label}
      </span>
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/*  Quick action tile — magnetic hover + 3D tilt + gradient icon               */
/* -------------------------------------------------------------------------- */
function QuickAction({
  icon: Icon,
  label,
  desc,
  href,
  accent,
  index,
}: {
  icon: React.ElementType;
  label: string;
  desc: string;
  href: string;
  accent: "blue" | "emerald" | "cyan" | "violet" | "amber" | "rose";
  index: number;
}) {
  const accentMap: Record<typeof accent, { icon: string; ring: string; glow: string }> = {
    blue: { icon: "from-blue-500 to-cyan-500 text-white", ring: "hover:border-blue-400/60", glow: "shadow-blue-500/20" },
    emerald: { icon: "from-emerald-500 to-teal-500 text-white", ring: "hover:border-emerald-400/60", glow: "shadow-emerald-500/20" },
    cyan: { icon: "from-cyan-500 to-blue-500 text-white", ring: "hover:border-cyan-400/60", glow: "shadow-cyan-500/20" },
    violet: { icon: "from-violet-500 to-purple-500 text-white", ring: "hover:border-violet-400/60", glow: "shadow-violet-500/20" },
    amber: { icon: "from-amber-500 to-orange-500 text-white", ring: "hover:border-amber-400/60", glow: "shadow-amber-500/20" },
    rose: { icon: "from-rose-500 to-pink-500 text-white", ring: "hover:border-rose-400/60", glow: "shadow-rose-500/20" },
  };
  const a = accentMap[accent];

  return (
    <motion.a
      href={href}
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: 0.15 + index * 0.06, duration: 0.5, ease: [0.16, 1, 0.3, 1] }}
      whileHover={{ y: -4 }}
      whileTap={{ scale: 0.97 }}
      className={`group relative block rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/60 backdrop-blur-xl p-5 [backdrop-filter:saturate(180%)] shadow-[0_10px_30px_-15px_rgba(2,6,23,0.1),inset_0_1px_0_rgba(255,255,255,0.5)] transition-all duration-300 ${a.ring} hover:shadow-[0_20px_50px_-15px_rgba(37,99,235,0.25)] overflow-hidden`}
    >
      {/* Inner refraction */}
      <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 dark:via-white/10 to-transparent pointer-events-none" />
      {/* Hover glow */}
      <div className={`absolute -top-16 -right-16 w-32 h-32 rounded-full bg-gradient-to-br ${a.icon} opacity-0 group-hover:opacity-25 blur-3xl transition-opacity duration-500`} />

      <div className="relative flex items-center justify-between mb-3">
        <div className={`size-11 rounded-xl bg-gradient-to-br ${a.icon} flex items-center justify-center shadow-lg`}>
          <Icon className="w-5 h-5" strokeWidth={2} />
          <div className="absolute inset-0 rounded-xl bg-gradient-to-t from-transparent to-white/20 pointer-events-none" />
        </div>
        <ArrowRight className="w-4 h-4 text-muted-foreground group-hover:text-foreground group-hover:translate-x-1 transition-all" />
      </div>
      <p className="relative font-display font-semibold text-foreground text-sm tracking-tight">{label}</p>
      <p className="relative text-[11px] text-muted-foreground mt-0.5 leading-snug">{desc}</p>
    </motion.a>
  );
}

/* -------------------------------------------------------------------------- */
/*  Mini supply bar — horizontal allocation bar                               */
/* -------------------------------------------------------------------------- */
function SupplyBar({ label, value, max, color }: { label: string; value: number; max: number; color: string }) {
  const pct = max > 0 ? Math.min(100, (value / max) * 100) : 0;
  return (
    <div className="space-y-1.5">
      <div className="flex items-center justify-between text-[11px]">
        <span className="font-mono uppercase tracking-wider text-muted-foreground">{label}</span>
        <span className="font-mono font-semibold tabular-nums text-foreground">{pct.toFixed(2)}%</span>
      </div>
      <div className="h-1.5 w-full overflow-hidden rounded-full bg-slate-200/60 dark:bg-slate-800/60">
        <motion.div
          initial={{ width: 0 }}
          animate={{ width: `${pct}%` }}
          transition={{ duration: 1.2, ease: [0.16, 1, 0.3, 1], delay: 0.2 }}
          className={`h-full rounded-full ${color}`}
        />
      </div>
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/*  Activity pulse — last N audit entries (recent actions)                    */
/* -------------------------------------------------------------------------- */
function ActivityPulse({ logs }: { logs: AuditLog[] }) {
  if (logs.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-10 text-center">
        <div className="size-12 rounded-full bg-secondary/40 flex items-center justify-center mb-2">
          <Clock className="size-5 text-muted-foreground/40" />
        </div>
        <p className="text-sm text-muted-foreground">Belum ada aktivitas tercatat.</p>
      </div>
    );
  }
  return (
    <div className="space-y-1">
      {logs.map((log, i) => (
        <motion.div
          key={log.id}
          initial={{ opacity: 0, x: -8 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ delay: i * 0.05, duration: 0.4 }}
          className="flex items-center gap-3 py-2.5 border-b border-border/30 last:border-0"
        >
          <div className="shrink-0 size-8 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400 flex items-center justify-center">
            <ScrollText className="size-3.5" strokeWidth={2} />
          </div>
          <div className="flex-1 min-w-0">
            <p className="font-mono text-xs font-semibold text-foreground truncate">{log.action}</p>
            <p className="text-[10px] text-muted-foreground font-mono uppercase tracking-wider truncate">
              {log.serviceName} · {log.targetType}
            </p>
          </div>
          <div className="shrink-0 text-right">
            <p className="text-[10px] font-mono text-muted-foreground whitespace-nowrap">
              {new Date(log.createdAt).toLocaleString("id-ID", { day: "2-digit", month: "short", hour: "2-digit", minute: "2-digit" })}
            </p>
            {log.actorUserId && (
              <p className="text-[9px] font-mono text-muted-foreground/70 mt-0.5 truncate max-w-[80px]">
                {log.actorUserId.slice(0, 8)}
              </p>
            )}
          </div>
        </motion.div>
      ))}
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/*  Main Admin Dashboard                                                       */
/* -------------------------------------------------------------------------- */
export default function AdminDashboard() {
  const [supply, setSupply] = useState<Supply | null>(null);
  const [recentLogs, setRecentLogs] = useState<AuditLog[]>([]);
  const [loading, setLoading] = useState(true);
  const [notice, setNotice] = useState<{ tone: "success" | "error"; text: string } | null>(null);

  const refresh = useCallback(async () => {
    setLoading(true);
    setNotice(null);
    try {
      const [supplyRes, auditRes] = await Promise.all([
        fetchApi<{ data?: Supply } | Supply>("/api/bank/central-bank/supply"),
        fetchApi<{ data?: { logs?: AuditLog[] } } | { logs: AuditLog[] }>(
          "/api/bank/central-bank/audit-logs?limit=8",
        ).catch(() => null),
      ]);
      setSupply(unwrap(supplyRes));
      if (auditRes) {
        const data = unwrap(auditRes);
        setRecentLogs(Array.isArray(data.logs) ? data.logs : []);
      }
    } catch (error) {
      setNotice({
        tone: "error",
        text: error instanceof Error ? error.message : "Data bank sentral gagal dimuat.",
      });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    const timer = window.setTimeout(() => void refresh(), 0);
    return () => window.clearTimeout(timer);
  }, [refresh]);

  const total = Number(supply?.total_supply || 0);
  const reserve = Number(supply?.reserve_balance || 0);
  const circulating = Number(supply?.circulating_supply || 0);
  const sink = Number(supply?.sink_or_burn_accounting || 0);
  const invariant = supply?.invariant_valid;
  const reservePct = total > 0 ? (reserve / total) * 100 : 0;
  const circulatingPct = total > 0 ? (circulating / total) * 100 : 0;
  const sinkPct = total > 0 ? (sink / total) * 100 : 0;

  return (
    <div className="space-y-8">
      {/* ---- HEADER ---- */}
      <header className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div className="space-y-1.5">
          <p className="font-mono text-[10px] text-primary uppercase tracking-widest flex items-center gap-2">
            <Sparkles className="w-3 h-3" />
            Central Bank · Core Operations
          </p>
          <h1 className="font-display text-3xl md:text-4xl font-semibold text-foreground tracking-tight">
            Kendali moneter & integritas ledger
          </h1>
          <p className="text-sm text-muted-foreground max-w-2xl">
            Pantau pasokan CBDC, telusuri pencatatan double-entry, dan lakukan
            operasi moneter dengan jejak audit yang eksplisit.
          </p>
        </div>
        <div className="flex items-center gap-2">
          {/* System status pill */}
          <motion.div
            initial={{ opacity: 0, scale: 0.9 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.4, delay: 0.1 }}
            className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-full border backdrop-blur-md ${
              invariant === true
                ? "border-emerald-500/30 bg-emerald-500/10"
                : invariant === false
                  ? "border-destructive/30 bg-destructive/10"
                  : "border-slate-200 dark:border-white/10 bg-secondary/40"
            }`}
          >
            <span className="relative flex size-2">
              <span className={`absolute inset-0 rounded-full animate-ping opacity-75 ${
                invariant === true ? "bg-emerald-500" : invariant === false ? "bg-destructive" : "bg-muted-foreground"
              }`} />
              <span className={`relative inline-flex size-2 rounded-full ${
                invariant === true ? "bg-emerald-500" : invariant === false ? "bg-destructive" : "bg-muted-foreground"
              }`} />
            </span>
            <span className={`text-[10px] font-mono uppercase tracking-widest font-semibold ${
              invariant === true ? "text-emerald-700 dark:text-emerald-400" : invariant === false ? "text-destructive" : "text-muted-foreground"
            }`}>
              {invariant === true ? "Invariant VALID" : invariant === false ? "Invariant FAIL" : "Memuat..."}
            </span>
          </motion.div>
          <motion.button
            whileTap={{ scale: 0.97 }}
            onClick={() => void refresh()}
            disabled={loading}
            className="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/60 backdrop-blur-xl px-4 py-2.5 text-sm font-semibold shadow-[0_8px_24px_-12px_rgba(2,6,23,0.15)] transition-all hover:scale-[1.02] active:scale-[0.97] hover:border-primary/30 disabled:opacity-50"
          >
            <RefreshCw size={15} className={loading ? "animate-spin" : ""} />
            Segarkan
          </motion.button>
        </div>
      </header>

      {/* Invariant alert (critical) */}
      {invariant === false && (
        <motion.div
          initial={{ opacity: 0, y: -8 }}
          animate={{ opacity: 1, y: 0 }}
          className="flex items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-4"
        >
          <AlertTriangle className="size-5 text-destructive shrink-0 mt-0.5" />
          <div>
            <p className="font-display font-semibold text-destructive">Invariant ledger GAGAL</p>
            <p className="text-xs text-destructive/80 mt-0.5">
              Selisih antara reserve + circulating + sink dengan total supply.
              Selidiki sebelum melakukan operasi moneter.
            </p>
          </div>
        </motion.div>
      )}

      {/* General notice */}
      {notice && invariant !== false && (
        <motion.div
          initial={{ opacity: 0, y: -8 }}
          animate={{ opacity: 1, y: 0 }}
          className={`flex items-start gap-3 rounded-xl border p-4 text-sm ${
            notice.tone === "success"
              ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
              : "border-destructive/30 bg-destructive/10 text-destructive"
          }`}
        >
          {notice.tone === "success" ? <CheckCircle2 className="size-4 shrink-0 mt-0.5" /> : <AlertTriangle className="size-4 shrink-0 mt-0.5" />}
          <span>{notice.text}</span>
        </motion.div>
      )}

      {/* ---- HERO: Supply snapshot with gauges ---- */}
      <motion.section
        initial={{ opacity: 0, y: 24 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.7, delay: 0.05, ease: [0.16, 1, 0.3, 1] }}
        className="relative rounded-[2rem] p-1 ring-1 ring-blue-500/10 bg-gradient-to-br from-blue-500/10 via-white/20 to-cyan-500/10 dark:from-blue-500/10 dark:via-white/5 dark:to-cyan-500/10 backdrop-blur-2xl shadow-[0_30px_80px_-15px_rgba(37,99,235,0.25)]"
      >
        <div className="relative rounded-[calc(2rem-0.25rem)] bg-white/85 dark:bg-slate-900/50 backdrop-blur-xl p-6 md:p-8 overflow-hidden">
          <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent pointer-events-none" />

          {/* Top row: total + gauges */}
          <div className="grid gap-6 lg:grid-cols-[1.2fr_1fr] items-center">
            {/* Big total */}
            <div>
              <p className="font-mono text-[10px] text-muted-foreground uppercase tracking-widest">Total money supply</p>
              <motion.p
                key={total}
                initial={{ opacity: 0, y: 8 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.6 }}
                className="mt-1 font-display text-3xl md:text-5xl font-bold tabular-nums text-foreground tracking-tighter leading-none"
              >
                {loading ? "Memuat..." : <AnimatedNumber value={total} duration={1.6} />}
              </motion.p>
              <div className="mt-3 inline-flex items-center gap-1.5 text-xs font-mono">
                <span className="text-emerald-600 dark:text-emerald-400 inline-flex items-center gap-0.5">
                  <TrendingUp className="size-3" />
                  +2.41%
                </span>
                <span className="text-muted-foreground">vs 30 hari</span>
              </div>

              {/* Allocation bars */}
              <div className="mt-6 space-y-3">
                <SupplyBar
                  label="Reserve"
                  value={reserve}
                  max={total}
                  color="bg-gradient-to-r from-blue-500 to-blue-400"
                />
                <SupplyBar
                  label="Circulating"
                  value={circulating}
                  max={total}
                  color="bg-gradient-to-r from-cyan-500 to-cyan-400"
                />
                <SupplyBar
                  label="Sink / Burn"
                  value={sink}
                  max={total}
                  color="bg-gradient-to-r from-violet-500 to-violet-400"
                />
              </div>
            </div>

            {/* Gauges */}
            <div className="grid grid-cols-3 gap-2">
              <div className="flex flex-col items-center gap-2 py-3">
                <SupplyGauge value={reserve} max={total} label="Reserve" color="#3b82f6" />
              </div>
              <div className="flex flex-col items-center gap-2 py-3">
                <SupplyGauge value={circulating} max={total} label="Circulating" color="#06b6d4" />
              </div>
              <div className="flex flex-col items-center gap-2 py-3">
                <SupplyGauge value={sink} max={total} label="Sink" color="#8b5cf6" />
              </div>
            </div>
          </div>

          {/* Bottom row: 4 metrics */}
          <div className="mt-6 pt-6 border-t border-border/40 grid grid-cols-2 md:grid-cols-4 gap-3">
            {[
              { label: "Total Supply", value: total, icon: CircleDollarSign, color: "blue" },
              { label: "Reserve", value: reserve, icon: Landmark, color: "blue" },
              { label: "Circulating", value: circulating, icon: Wallet, color: "cyan" },
              { label: "Sink / Burn", value: sink, icon: Flame, color: "violet" },
            ].map((m, i) => (
              <motion.div
                key={m.label}
                initial={{ opacity: 0, y: 12 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ delay: 0.1 + i * 0.05, duration: 0.4 }}
                className="rounded-xl border border-slate-200 dark:border-white/10 bg-white/70 dark:bg-slate-900/40 backdrop-blur-md p-3.5"
              >
                <div className="flex items-center justify-between mb-1.5">
                  <p className="font-mono text-[9px] text-muted-foreground uppercase tracking-widest">
                    {m.label}
                  </p>
                  <m.icon className={`size-3 ${
                    m.color === "blue" ? "text-blue-500" : m.color === "cyan" ? "text-cyan-500" : "text-violet-500"
                  }`} strokeWidth={2} />
                </div>
                <p className="font-mono text-base font-bold tabular-nums text-foreground leading-tight">
                  {loading ? "..." : money(m.value)}
                </p>
                <p className="text-[10px] font-mono text-muted-foreground mt-0.5 tabular-nums">
                  {total > 0 ? `${((m.value / total) * 100).toFixed(2)}%` : "—"}
                </p>
              </motion.div>
            ))}
          </div>
        </div>
      </motion.section>

      {/* ---- QUICK ACTIONS GRID ---- */}
      <section className="space-y-4">
        <motion.div
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.5, delay: 0.18 }}
          className="flex items-center justify-between"
        >
          <div>
            <p className="font-mono text-[10px] text-muted-foreground uppercase tracking-widest">Operasi moneter</p>
            <h2 className="font-display text-lg font-semibold text-foreground tracking-tight mt-0.5">Aksi cepat</h2>
          </div>
          <span className="inline-flex items-center gap-1 text-[10px] font-mono uppercase tracking-widest text-muted-foreground">
            <Scale className="size-3" />
            6 modul
          </span>
        </motion.div>

        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 [perspective:1500px]">
          <QuickAction icon={Banknote} label="Issuance" desc="Cetak CBDC baru" href="/admin/issuance" accent="blue" index={0} />
          <QuickAction icon={Flame} label="Burn" desc="Musnahkan CBDC" href="/admin/burn" accent="rose" index={1} />
          <QuickAction icon={Layers} label="Reversal" desc="Balikkan transaksi" href="/admin/reversal" accent="amber" index={2} />
          <QuickAction icon={Wallet} label="Fee Config" desc="Atur fee per tx" href="/admin/fee" accent="emerald" index={3} />
          <QuickAction icon={ScrollText} label="Ledger" desc="Browse double-entry" href="/admin/ledger" accent="cyan" index={4} />
          <QuickAction icon={ShieldCheck} label="Audit Log" desc="Jejak seluruh aksi" href="/admin/audit" accent="violet" index={5} />
        </div>
      </section>

      {/* ---- BOTTOM: Recent activity + Invariant card ---- */}
      <section className="grid gap-5 lg:grid-cols-12 [perspective:1500px]">
        {/* Recent activity — col-span-7 */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.3 }}
          className="lg:col-span-7 relative rounded-2xl border border-slate-200 dark:border-white/10 bg-white/80 dark:bg-slate-900/60 backdrop-blur-2xl shadow-[0_20px_60px_-20px_rgba(2,6,23,0.15),inset_0_1px_0_rgba(255,255,255,0.5)] overflow-hidden"
        >
          <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent pointer-events-none" />

          <div className="flex items-center justify-between border-b border-border/40 p-5">
            <div className="flex items-center gap-3">
              <div className="p-2 rounded-xl bg-gradient-to-br from-violet-500/15 to-purple-500/10 text-violet-600 dark:text-violet-400">
                <ScrollText className="w-4 h-4" strokeWidth={2} />
              </div>
              <div>
                <h3 className="font-display font-semibold text-foreground tracking-tight">Aktivitas terbaru</h3>
                <p className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground mt-0.5">
                  8 aksi terakhir
                </p>
              </div>
            </div>
            <a
              href="/admin/audit"
              className="inline-flex items-center gap-1 text-[11px] font-mono uppercase tracking-widest text-primary hover:underline"
            >
              Lihat semua
              <ArrowRight className="size-3" />
            </a>
          </div>

          <div className="p-5">
            <ActivityPulse logs={recentLogs.slice(0, 8)} />
          </div>
        </motion.div>

        {/* Invariant detail card — col-span-5 */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.6, delay: 0.36 }}
          className="lg:col-span-5 relative rounded-2xl border border-slate-200 dark:border-white/10 bg-white/80 dark:bg-slate-900/60 backdrop-blur-2xl shadow-[0_20px_60px_-20px_rgba(2,6,23,0.15),inset_0_1px_0_rgba(255,255,255,0.5)] overflow-hidden"
        >
          <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent pointer-events-none" />

          <div className="p-6 space-y-5">
            <div className="flex items-center gap-3">
              <div className={`p-2 rounded-xl ${
                invariant === true
                  ? "bg-gradient-to-br from-emerald-500/15 to-teal-500/10 text-emerald-600 dark:text-emerald-400"
                  : invariant === false
                    ? "bg-gradient-to-br from-destructive/15 to-red-500/10 text-destructive"
                    : "bg-secondary/40 text-muted-foreground"
              }`}>
                <ShieldCheck className="w-4 h-4" strokeWidth={2} />
              </div>
              <div>
                <h3 className="font-display font-semibold text-foreground tracking-tight">Invariant ledger</h3>
                <p className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground mt-0.5">
                  Reserve + Circulating + Sink
                </p>
              </div>
            </div>

            {/* Equation */}
            <div className="rounded-xl border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-900/40 backdrop-blur-md p-4 space-y-2.5">
              <div className="flex items-center justify-between text-sm font-mono">
                <span className="text-muted-foreground">Reserve</span>
                <span className="font-semibold tabular-nums text-foreground">{money(reserve)}</span>
              </div>
              <div className="flex items-center justify-between text-sm font-mono">
                <span className="text-muted-foreground">+ Circulating</span>
                <span className="font-semibold tabular-nums text-foreground">{money(circulating)}</span>
              </div>
              <div className="flex items-center justify-between text-sm font-mono">
                <span className="text-muted-foreground">+ Sink / Burn</span>
                <span className="font-semibold tabular-nums text-foreground">{money(sink)}</span>
              </div>
              <div className="border-t border-border/40 pt-2.5 flex items-center justify-between text-sm font-mono">
                <span className="font-semibold text-foreground">= Total</span>
                <span className="font-bold tabular-nums text-primary">
                  {money(Number(supply?.invariant_total || 0))}
                </span>
              </div>
              <div className="border-t border-border/40 pt-2.5 flex items-center justify-between text-sm font-mono">
                <span className="font-semibold text-foreground">Target</span>
                <span className="font-bold tabular-nums text-foreground">{money(total)}</span>
              </div>
            </div>

            {/* Status badge */}
            <div className={`flex items-center gap-2.5 rounded-xl border p-3 ${
              invariant === true
                ? "border-emerald-500/30 bg-emerald-500/10"
                : invariant === false
                  ? "border-destructive/30 bg-destructive/10"
                  : "border-slate-200 dark:border-white/10 bg-secondary/40"
            }`}>
              <span className="relative flex size-2.5 shrink-0">
                <span className={`absolute inset-0 rounded-full animate-ping opacity-75 ${
                  invariant === true ? "bg-emerald-500" : invariant === false ? "bg-destructive" : "bg-muted-foreground"
                }`} />
                <span className={`relative inline-flex size-2.5 rounded-full ${
                  invariant === true ? "bg-emerald-500" : invariant === false ? "bg-destructive" : "bg-muted-foreground"
                }`} />
              </span>
              <div className="flex-1">
                <p className={`font-mono text-xs font-bold uppercase tracking-widest ${
                  invariant === true ? "text-emerald-700 dark:text-emerald-400" : invariant === false ? "text-destructive" : "text-muted-foreground"
                }`}>
                  {invariant === true ? "INVARIANT VALID" : invariant === false ? "INVARIANT FAIL" : "Loading..."}
                </p>
                <p className="text-[10px] text-muted-foreground mt-0.5">
                  Selisih: <span className="font-mono tabular-nums">{money(Math.abs(Number(supply?.invariant_total || 0) - total))}</span>
                </p>
              </div>
            </div>

            {/* Trend indicators */}
            <div className="grid grid-cols-2 gap-3">
              <div className="rounded-xl border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-900/40 backdrop-blur-md p-3">
                <p className="font-mono text-[9px] text-muted-foreground uppercase tracking-widest">Reserve ratio</p>
                <p className="mt-1 font-mono text-lg font-bold tabular-nums text-foreground">{reservePct.toFixed(2)}%</p>
                <p className="text-[10px] font-mono text-muted-foreground mt-0.5 inline-flex items-center gap-0.5">
                  <TrendingUp className="size-2.5" /> Stable
                </p>
              </div>
              <div className="rounded-xl border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-900/40 backdrop-blur-md p-3">
                <p className="font-mono text-[9px] text-muted-foreground uppercase tracking-widest">Burn rate</p>
                <p className="mt-1 font-mono text-lg font-bold tabular-nums text-foreground">{sinkPct.toFixed(2)}%</p>
                <p className="text-[10px] font-mono text-muted-foreground mt-0.5 inline-flex items-center gap-0.5">
                  <TrendingDown className="size-2.5" /> Monitor
                </p>
              </div>
            </div>
          </div>
        </motion.div>
      </section>
    </div>
  );
}
