"use client";

import { FormEvent, useCallback, useEffect, useMemo, useState } from "react";
import {
  AlertTriangle,
  ArrowDownLeft,
  ArrowRight,
  ArrowUpRight,
  BadgeDollarSign,
  CheckCircle2,
  ChevronDown,
  ChevronUp,
  Clock,
  Copy,
  CreditCard as CreditCardIcon,
  Eye,
  EyeOff,
  KeyRound,
  Loader2,
  RefreshCw,
  Send,
  ShieldCheck,
  Sparkles,
  TrendingUp,
  User,
  Wallet,
  Zap,
} from "lucide-react";
import { motion, AnimatePresence, useMotionValue, useSpring, useTransform } from "framer-motion";
import { Area, AreaChart, ResponsiveContainer, Tooltip, XAxis } from "recharts";
import { fetchApi } from "@/lib/api";
import KycDocumentCard from "@/components/KycDocumentCard";
import { CreditCard3D } from "@/components/landing/CreditCard3D";

type Transaction = {
  id: string;
  transaction_type: string;
  status: string;
  gross_amount: string | number;
  total_debit: string | number;
  created_at: string;
  direction: "CREDIT" | "DEBIT" | "IN" | "OUT";
  other_party?: string;
};

type BalanceInfo = {
  account_number?: string | null;
  holder_name?: string | null;
  wallet_id?: string;
  currency?: string;
  available_balance?: string | number;
  hold_balance?: string | number;
};

type RetailMode = "all" | "overview" | "transfer" | "loans" | "activity" | "kyc";

const money = (value: string | number | null | undefined) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value || 0));

const unwrap = <T,>(response: { data?: T } | T): T =>
  typeof response === "object" && response !== null && "data" in response ? (response as { data: T }).data : (response as T);

const isCredit = (direction: Transaction["direction"]) => direction === "CREDIT" || direction === "IN";

const formatAccountDisplay = (value: string): string => {
  const digits = value.replace(/\D/g, "").slice(0, 10);
  if (digits.length <= 4) return digits;
  if (digits.length <= 8) return `${digits.slice(0, 4)}-${digits.slice(4)}`;
  return `${digits.slice(0, 4)}-${digits.slice(4, 8)}-${digits.slice(8)}`;
};

const isValidAccountNumberFormat = (value: string): boolean => /^\d{10}$/.test(value.replace(/\D/g, ""));

type RecipientLookup = {
  account_number: string;
  holder_name: string;
  wallet_id: string;
};

type ActiveLoan = {
  id: string;
  principal: number;
  interest_amount: number;
  total_due: number;
  paid_amount: number;
  remaining: number;
  status: "PENDING" | "DISBURSED" | "PARTIAL_PAID";
  created_at: string;
  disbursed_at: string | null;
  due_at: string | null;
  recommended_by: string | null;
  recommended_at: string | null;
  recommendation_note: string | null;
};

type LoanLimit = {
  cap: number;
  outstanding: number;
  remaining: number;
};

/* -------------------------------------------------------------------------- */
/*  3D Wallet Card — interactive tilt + magnetic hover                         */
/* -------------------------------------------------------------------------- */
function WalletCard3D({ balance, accountNumber, holderName }: { balance: number; accountNumber: string; holderName?: string | null }) {
  const ref = useState<HTMLElement | null>(null);
  const mx = useMotionValue(0);
  const my = useMotionValue(0);
  const rx = useSpring(useTransform(my, [-0.5, 0.5], [12, -12]), { stiffness: 140, damping: 20 });
  const ry = useSpring(useTransform(mx, [-0.5, 0.5], [-16, 16]), { stiffness: 140, damping: 20 });
  const glareX = useTransform(mx, [-0.5, 0.5], ["0%", "100%"]);
  const glareY = useTransform(my, [-0.5, 0.5], ["0%", "100%"]);

  return (
    <motion.div
      initial={{ opacity: 0, y: 24 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.8, ease: [0.16, 1, 0.3, 1] }}
      className="relative [perspective:1400px]"
      onMouseMove={(e) => {
        const el = ref[0];
        if (!el) return;
        const r = el.getBoundingClientRect();
        mx.set((e.clientX - r.left) / r.width - 0.5);
        my.set((e.clientY - r.top) / r.height - 0.5);
      }}
      onMouseLeave={() => { mx.set(0); my.set(0); }}
    >
      <motion.div
        style={{ rotateX: rx, rotateY: ry }}
        className="relative [transform-style:preserve-3d]"
      >
        {/* Glassmorphic card with double-bezel */}
        <div
          ref={(el) => ref[1](el)}
          className="relative w-full max-w-md rounded-[2rem] p-1 ring-1 ring-blue-500/10 bg-gradient-to-br from-blue-500/10 via-white/40 to-cyan-500/10 dark:from-blue-500/10 dark:via-white/5 dark:to-cyan-500/10 backdrop-blur-2xl shadow-[0_30px_80px_-15px_rgba(37,99,235,0.35)]"
        >
          <div className="relative rounded-[calc(2rem-0.25rem)] bg-gradient-to-br from-blue-600 via-blue-700 to-cyan-700 dark:from-blue-700 dark:via-blue-800 dark:to-indigo-900 p-6 overflow-hidden">
            {/* Inner refraction */}
            <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent pointer-events-none" />

            {/* Holographic animated sheen */}
            <motion.div
              style={{ background: `radial-gradient(circle at ${glareX.get()} ${glareY.get()}, rgba(255,255,255,0.25), transparent 50%)` }}
              className="absolute inset-0 pointer-events-none mix-blend-overlay"
            />

            {/* Top row: brand + chip icon */}
            <div className="relative flex items-start justify-between mb-8">
              <div>
                <p className="font-display text-base font-bold text-white tracking-tight">SmartBank</p>
                <p className="text-[9px] text-white/70 uppercase tracking-[0.25em] font-mono mt-0.5">CBDC · Tier-2</p>
              </div>
              <motion.div
                animate={{ rotate: [0, 360] }}
                transition={{ duration: 8, repeat: Infinity, ease: "linear" }}
                className="size-10 rounded-xl bg-gradient-to-br from-amber-300/90 to-amber-500/90 border border-amber-700/30 shadow-inner"
                style={{ transformStyle: "preserve-3d" }}
              />
            </div>

            {/* Balance */}
            <div className="relative">
              <p className="text-[10px] text-white/60 uppercase tracking-[0.25em] font-mono">Saldo tersedia</p>
              <p className="mt-1 font-display text-3xl md:text-4xl font-bold text-white tabular-nums tracking-tighter">
                {money(balance)}
              </p>
            </div>

            {/* Account number + holder */}
            <div className="relative mt-5 space-y-1.5">
              <p className="font-mono text-base sm:text-lg font-medium text-white tracking-[0.22em]">
                {accountNumber ? formatAccountDisplay(accountNumber) : "—"}
              </p>
              <div className="flex items-end justify-between gap-2">
                <p className="font-mono text-[10px] text-white/70 uppercase tracking-[0.2em]">
                  {holderName?.trim() ? `A/N ${holderName}` : "SmartBank Wallet"}
                </p>
                <p className="font-mono text-[10px] text-white/70 uppercase tracking-[0.2em] tabular-nums">VERIFIED</p>
              </div>
            </div>

            {/* Bottom-row ambient glows */}
            <div className="absolute -bottom-12 -right-12 w-40 h-40 rounded-full bg-cyan-300/20 blur-3xl pointer-events-none" />
            <div className="absolute -top-12 -left-12 w-32 h-32 rounded-full bg-white/10 blur-2xl pointer-events-none" />
          </div>
        </div>

        {/* Floating satellite */}
        <motion.div
          animate={{ y: [0, -8, 0] }}
          transition={{ duration: 4, repeat: Infinity, ease: "easeInOut" }}
          className="absolute -top-2 -right-2 size-3 rounded-full bg-cyan-400 shadow-[0_0_20px_rgba(34,211,238,0.9)]"
        />
      </motion.div>
    </motion.div>
  );
}

/* -------------------------------------------------------------------------- */
/*  Magnetic Quick Action Button — pulls toward cursor                         */
/* -------------------------------------------------------------------------- */
function MagneticAction({
  icon: Icon,
  label,
  href,
  accent,
  index = 0,
}: {
  icon: React.ElementType;
  label: string;
  href: string;
  accent: "blue" | "emerald" | "cyan" | "violet";
  index?: number;
}) {
  const ref = useState<HTMLElement | null>(null);
  const mx = useMotionValue(0);
  const my = useMotionValue(0);
  const sx = useSpring(mx, { stiffness: 250, damping: 20 });
  const sy = useSpring(my, { stiffness: 250, damping: 20 });

  const accentClasses: Record<typeof accent, string> = {
    blue: "from-blue-500/15 to-cyan-500/10 text-blue-600 dark:text-blue-400",
    emerald: "from-emerald-500/15 to-teal-500/10 text-emerald-600 dark:text-emerald-400",
    cyan: "from-cyan-500/15 to-blue-500/10 text-cyan-600 dark:text-cyan-400",
    violet: "from-violet-500/15 to-purple-500/10 text-violet-600 dark:text-violet-400",
  };

  return (
    <motion.a
      href={href}
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ delay: 0.1 + index * 0.06, duration: 0.5, ease: [0.16, 1, 0.3, 1] }}
      whileHover={{ y: -4 }}
      whileTap={{ scale: 0.97 }}
      onMouseMove={(e) => {
        const el = ref[0];
        if (!el) return;
        const r = el.getBoundingClientRect();
        const cx = e.clientX - r.left - r.width / 2;
        const cy = e.clientY - r.top - r.height / 2;
        mx.set(cx * 0.2);
        my.set(cy * 0.2);
      }}
      onMouseLeave={() => { mx.set(0); my.set(0); }}
      ref={(el) => ref[1](el)}
      className="group relative block"
    >
      <motion.div style={{ x: sx, y: sy }} className="relative rounded-2xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/60 backdrop-blur-xl p-5 [backdrop-filter:saturate(180%)] shadow-[0_10px_30px_-15px_rgba(2,6,23,0.1),inset_0_1px_0_rgba(255,255,255,0.5)] transition-all hover:border-primary/30 hover:shadow-[0_20px_50px_-15px_rgba(37,99,235,0.25)]">
        {/* Inner refraction */}
        <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 dark:via-white/10 to-transparent rounded-t-2xl pointer-events-none" />

        <div className={`relative inline-flex p-2.5 rounded-xl bg-gradient-to-br ${accentClasses[accent]} backdrop-blur-sm`}>
          <Icon className="w-4 h-4" strokeWidth={2} />
        </div>
        <p className="relative mt-3 font-display font-semibold text-foreground text-sm tracking-tight">{label}</p>
        <div className="relative mt-1 flex items-center gap-1 text-[11px] text-muted-foreground font-mono uppercase tracking-wider">
          Buka
          <ArrowRight className="w-3 h-3 group-hover:translate-x-1 transition-transform" />
        </div>
      </motion.div>
    </motion.a>
  );
}

/* -------------------------------------------------------------------------- */
/*  Animated number — counts up on mount                                       */
/* -------------------------------------------------------------------------- */
function AnimatedNumber({ value, duration = 1.4 }: { value: number; duration?: number }) {
  const [display, setDisplay] = useState(0);
  useEffect(() => {
    const start = performance.now();
    let raf = 0;
    const tick = (now: number) => {
      const t = Math.min(1, (now - start) / (duration * 1000));
      // easeOutCubic
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
/*  Main Dashboard                                                             */
/* -------------------------------------------------------------------------- */
export default function RetailDashboard({ mode = "all" }: { mode?: RetailMode } = {}) {
  const [balance, setBalance] = useState(0);
  const [balanceInfo, setBalanceInfo] = useState<BalanceInfo | null>(null);
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [kycTier, setKycTier] = useState("BASIC");
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState("");
  const [notice, setNotice] = useState<{ tone: "success" | "error"; text: string } | null>(null);
  const [copied, setCopied] = useState(false);
  const [transfer, setTransfer] = useState({ accountNumber: "", amount: "", note: "", pin: "" });
  const [lookupCache, setLookupCache] = useState<Record<string, RecipientLookup | null>>({});
  const [lookingUp, setLookingUp] = useState(false);
  const [loan, setLoan] = useState({ amount: "", term: "12" });
  const [activeLoans, setActiveLoans] = useState<ActiveLoan[]>([]);
  const [loanLimit, setLoanLimit] = useState<LoanLimit>({ cap: 100_000, outstanding: 0, remaining: 100_000 });
  const [expandedRepayId, setExpandedRepayId] = useState<string | null>(null);
  const [repayDraft, setRepayDraft] = useState<{ amount: string; pin: string }>({ amount: "", pin: "" });
  const [showPin, setShowPin] = useState(false);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const [balanceResponse, transactionResponse, kycResponse, loansResponse] = await Promise.all([
        fetchApi<{ data?: BalanceInfo } | BalanceInfo>("/api/wallet/v1/wallets/me/balance"),
        fetchApi<{ data?: Transaction[] } | Transaction[]>(
          "/api/wallet/v1/wallets/me/transactions",
        ),
        fetchApi<{ data?: { kycTier?: string }; kycTier?: string }>("/api/wallet/v1/wallets/me/kyc-document"),
        fetchApi<{ data?: { loans?: ActiveLoan[]; limit?: LoanLimit } } | { loans: ActiveLoan[]; limit: LoanLimit }>(
          "/api/wallet/v1/loans/me",
        ).catch(() => null),
      ]);
      const balanceData = unwrap(balanceResponse);
      const transactionData = unwrap(transactionResponse);
      const kycData = unwrap(kycResponse);
      setBalance(Number(balanceData.available_balance || 0));
      setBalanceInfo(balanceData);
      setTransactions(Array.isArray(transactionData) ? transactionData : []);
      setKycTier(kycData.kycTier || "BASIC");
      if (loansResponse) {
        const loansData = unwrap(loansResponse);
        setActiveLoans(Array.isArray(loansData.loans) ? loansData.loans : []);
        if (loansData.limit) setLoanLimit(loansData.limit);
      } else {
        setActiveLoans([]);
      }
    } catch (error) {
      setNotice({ tone: "error", text: error instanceof Error ? error.message : "Data dompet gagal dimuat." });
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    const timer = window.setTimeout(() => void refresh(), 0);
    return () => window.clearTimeout(timer);
  }, [refresh]);

  const chartData = useMemo(() => {
    let running = balance;
    return [...transactions].slice(0, 12).map((item) => {
      const debit = !isCredit(item.direction);
      const amount = Number(debit ? item.total_debit : item.gross_amount);
      const point = {
        date: new Date(item.created_at).toLocaleDateString("id-ID", { day: "2-digit", month: "short" }),
        value: running,
      };
      running += debit ? amount : -amount;
      return point;
    }).reverse();
  }, [balance, transactions]);

  const mutate = async (key: string, endpoint: string, payload: object, success: string) => {
    setProcessing(key);
    setNotice(null);
    try {
      await fetchApi(endpoint, { method: "POST", body: JSON.stringify(payload) });
      setNotice({ tone: "success", text: success });
      await refresh();
      return true;
    } catch (error) {
      setNotice({ tone: "error", text: error instanceof Error ? error.message : "Permintaan gagal diproses." });
      return false;
    } finally {
      setProcessing("");
    }
  };

  const transferDigits = transfer.accountNumber.replace(/\D/g, "");
  useEffect(() => {
    if (transferDigits.length !== 10) return;
    if (Object.prototype.hasOwnProperty.call(lookupCache, transferDigits)) return;
    const timer = window.setTimeout(async () => {
      setLookingUp(true);
      try {
        const data = await fetchApi<{ data?: RecipientLookup } | RecipientLookup>(
          `/api/wallet/v1/wallets/lookup?account_number=${transferDigits}`,
        );
        const recipient = unwrap(data);
        setLookupCache((prev) => ({ ...prev, [transferDigits]: recipient }));
      } catch {
        setLookupCache((prev) => ({ ...prev, [transferDigits]: null }));
      } finally {
        setLookingUp(false);
      }
    }, 400);
    return () => window.clearTimeout(timer);
  }, [transferDigits, lookupCache]);

  const recipient: RecipientLookup | null =
    transferDigits.length === 10 && Object.prototype.hasOwnProperty.call(lookupCache, transferDigits)
      ? lookupCache[transferDigits]
      : null;
  const recipientLookupAttempted =
    transferDigits.length === 10 && Object.prototype.hasOwnProperty.call(lookupCache, transferDigits);

  const submitTransfer = async (event: FormEvent) => {
    event.preventDefault();
    const digits = transfer.accountNumber.replace(/\D/g, "");
    if (!isValidAccountNumberFormat(digits)) {
      setNotice({ tone: "error", text: "Nomor rekening tujuan harus 10 digit." });
      return;
    }
    if (!recipient) {
      setNotice({ tone: "error", text: "Nomor rekening tujuan belum diverifikasi. Tunggu konfirmasi nama pemilik." });
      return;
    }
    if (await mutate(
      "transfer",
      "/api/wallet/v1/transfers",
      {
        to_account_number: digits,
        amount: transfer.amount,
        note: transfer.note.trim() || "Transfer",
        pin: transfer.pin,
      },
      `Transfer ke ${recipient.holder_name} berhasil diproses.`,
    )) {
      setTransfer({ accountNumber: "", amount: "", note: "", pin: "" });
      setLookupCache({});
    }
  };

  const submitLoan = async (event: FormEvent) => {
    event.preventDefault();
    if (kycTier !== "VERIFIED") {
      setNotice({ tone: "error", text: "Pengajuan pinjaman hanya tersedia setelah KYC diverifikasi Teller." });
      return;
    }
    if (await mutate(
      "loan",
      "/api/wallet/v1/loans/apply",
      { amount: loan.amount, term_months: Number(loan.term) },
      "Pengajuan pinjaman tercatat dan menunggu persetujuan Manager.",
    )) {
      setLoan({ amount: "", term: "12" });
    }
  };

  const submitRepayment = async (event: FormEvent, loanId: string) => {
    event.preventDefault();
    if (await mutate(
      `repayment-${loanId}`,
      `/api/wallet/v1/loans/${loanId}/repay`,
      { amount: repayDraft.amount, pin: repayDraft.pin },
      "Pembayaran pinjaman berhasil diproses.",
    )) {
      setRepayDraft({ amount: "", pin: "" });
      setExpandedRepayId(null);
    }
  };

  const openRepayForm = (loan: ActiveLoan) => {
    if (expandedRepayId === loan.id) {
      setExpandedRepayId(null);
      setRepayDraft({ amount: "", pin: "" });
      return;
    }
    setExpandedRepayId(loan.id);
    setRepayDraft({ amount: String(loan.remaining), pin: "" });
  };

  const copyAccountNumber = async () => {
    if (!balanceInfo?.account_number) return;
    try {
      await navigator.clipboard.writeText(balanceInfo.account_number);
      setCopied(true);
      window.setTimeout(() => setCopied(false), 1500);
    } catch {
      // silent fail
    }
  };

  const showOverview = mode === "all" || mode === "overview";
  const showKyc = mode === "all" || mode === "kyc";
  const showTransfer = mode === "transfer";
  const showLoans = mode === "all" || mode === "loans";
  const showActivity = mode === "all" || mode === "activity";

  const ownAccountDisplay = balanceInfo?.account_number ? formatAccountDisplay(balanceInfo.account_number) : "—";
  const ownHolderName = balanceInfo?.holder_name?.trim();
  const lookupError: boolean = transferDigits.length === 10 && !lookingUp && recipientLookupAttempted && !recipient;

  return (
    <div className="space-y-8">
      {/* ---- HEADER ---- */}
      <header className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div className="space-y-1.5">
          <p className="font-mono text-[10px] text-primary uppercase tracking-widest flex items-center gap-2">
            <Sparkles className="w-3 h-3" />
            Retail wallet
          </p>
          <h1 className="font-display text-3xl md:text-4xl font-semibold text-foreground tracking-tight">
            Halo, {ownHolderName?.split(" ")[0] || "Nasabah"}
          </h1>
          <p className="text-sm text-muted-foreground max-w-xl">
            Saldo, transfer, KYC, aktivitas, dan pinjaman dalam route yang terhubung ke ledger.
          </p>
        </div>
        <button
          onClick={() => void refresh()}
          disabled={loading}
          className="group inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/60 backdrop-blur-xl px-4 py-2.5 text-sm font-semibold shadow-[0_8px_24px_-12px_rgba(2,6,23,0.15)] transition-all hover:scale-[1.02] active:scale-[0.97] hover:border-primary/30 disabled:opacity-50"
        >
          <RefreshCw size={15} className={loading ? "animate-spin" : ""} />
          Segarkan
        </button>
      </header>

      <AnimatePresence mode="wait">
        {notice && (
          <motion.div
            key={notice.text}
            initial={{ opacity: 0, y: -8, height: 0 }}
            animate={{ opacity: 1, y: 0, height: "auto" }}
            exit={{ opacity: 0, y: -8, height: 0 }}
            transition={{ duration: 0.3, ease: [0.16, 1, 0.3, 1] }}
            className={`overflow-hidden rounded-xl border p-4 text-sm flex items-start gap-2.5 ${
              notice.tone === "success"
                ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                : "border-destructive/30 bg-destructive/10 text-destructive"
            }`}
          >
            {notice.tone === "success" ? <CheckCircle2 className="size-4 shrink-0 mt-0.5" /> : <AlertTriangle className="size-4 shrink-0 mt-0.5" />}
            <span>{notice.text}</span>
          </motion.div>
        )}
      </AnimatePresence>

      {/* ---- OVERVIEW (Asymmetric Bento: Card+Actions LEFT, Chart+Info RIGHT) ---- */}
      {showOverview && (
        <section id="overview" className="scroll-mt-8 space-y-6">
          <div className="grid gap-5 lg:grid-cols-12 [perspective:1500px]">

            {/* ============== LEFT COLUMN: 3D Card + Quick Actions ============== */}
            <motion.div
              initial={{ opacity: 0, y: 24, rotateX: -10 }}
              animate={{ opacity: 1, y: 0, rotateX: 0 }}
              transition={{ duration: 0.7, delay: 0.05, ease: [0.16, 1, 0.3, 1] }}
              className="lg:col-span-5 flex flex-col gap-5 [transform-style:preserve-3d]"
            >
              {/* Big 3D debit card — uses shared CreditCard3D for real-debit-card look */}
              <motion.div
                animate={{ y: [0, -6, 0] }}
                transition={{ duration: 5, repeat: Infinity, ease: "easeInOut" }}
                className="relative flex justify-center"
              >
                <CreditCard3D
                  width={340}
                  variant="blue"
                  holderName={balanceInfo?.holder_name?.trim() || ownHolderName || "A. WIJAYA K."}
                  last4={balanceInfo?.account_number ? balanceInfo.account_number.replace(/\D/g, "").slice(-4) || "8472" : "8472"}
                  validThru="12/28"
                />
                {/* Floating satellite */}
                <motion.div
                  animate={{ y: [0, -8, 0], opacity: [0.7, 1, 0.7] }}
                  transition={{ duration: 3, repeat: Infinity, ease: "easeInOut", delay: 0.5 }}
                  className="absolute -top-1 -right-2 size-2.5 rounded-full bg-cyan-400 shadow-[0_0_18px_rgba(34,211,238,0.9)]"
                />
                <motion.div
                  animate={{ y: [0, 8, 0], opacity: [0.5, 1, 0.5] }}
                  transition={{ duration: 4, repeat: Infinity, ease: "easeInOut", delay: 1 }}
                  className="absolute bottom-2 -left-2 size-2 rounded-full bg-blue-300 shadow-[0_0_14px_rgba(147,197,253,0.85)]"
                />
              </motion.div>

              {/* Quick Actions BELOW the card */}
              <div>
                <motion.div
                  initial={{ opacity: 0, y: 12 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ duration: 0.5, delay: 0.22 }}
                  className="flex items-center justify-between mb-3"
                >
                  <p className="font-mono text-[10px] text-muted-foreground uppercase tracking-widest">
                    Aksi cepat
                  </p>
                  <Zap className="size-3 text-primary" />
                </motion.div>

                <div className="grid grid-cols-2 gap-3">
                  <MagneticAction icon={Send} label="Transfer" href="/transfer" accent="blue" index={0} />
                  <MagneticAction icon={BadgeDollarSign} label="Pinjaman" href="/pinjaman" accent="emerald" index={1} />
                  <MagneticAction icon={User} label="KYC" href="/kyc" accent="cyan" index={2} />
                  <MagneticAction icon={CreditCardIcon} label="Aktivitas" href="/aktivitas" accent="violet" index={3} />
                </div>
              </div>
            </motion.div>

            {/* ============== RIGHT COLUMN: Chart + Info ============== */}
            <motion.div
              initial={{ opacity: 0, y: 24, rotateX: 6 }}
              animate={{ opacity: 1, y: 0, rotateX: 0 }}
              transition={{ duration: 0.7, delay: 0.12, ease: [0.16, 1, 0.3, 1] }}
              className="lg:col-span-7 relative rounded-[2rem] p-1 ring-1 ring-blue-500/10 bg-gradient-to-br from-blue-500/10 via-white/20 to-cyan-500/10 dark:from-blue-500/10 dark:via-white/5 dark:to-cyan-500/10 backdrop-blur-2xl shadow-[0_30px_80px_-15px_rgba(37,99,235,0.25)]"
            >
              <div className="relative rounded-[calc(2rem-0.25rem)] bg-white/80 dark:bg-slate-900/50 backdrop-blur-xl p-6 md:p-8 overflow-hidden">
                <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent pointer-events-none" />

                {/* Header: Greeting + Balance snapshot */}
                <div className="flex items-start justify-between gap-4 mb-6">
                  <div>
                    <p className="font-mono text-[10px] text-muted-foreground uppercase tracking-widest">Saldo tersedia</p>
                    <motion.p
                      key={balance}
                      initial={{ opacity: 0, y: 8 }}
                      animate={{ opacity: 1, y: 0 }}
                      transition={{ duration: 0.6, ease: [0.16, 1, 0.3, 1] }}
                      className="mt-1 font-display text-3xl md:text-4xl font-bold tabular-nums text-foreground tracking-tighter leading-none"
                    >
                      {loading ? "Memuat..." : money(balance)}
                    </motion.p>
                    {ownHolderName && (
                      <p className="mt-1.5 text-xs text-muted-foreground">
                        a/n <span className="font-mono text-foreground/80">{ownHolderName}</span>
                      </p>
                    )}
                  </div>

                  {/* Account number + copy */}
                  <div className="text-right">
                    <p className="font-mono text-[10px] text-muted-foreground uppercase tracking-widest">Nomor rekening</p>
                    <div className="mt-1 flex items-center gap-2 justify-end">
                      <p className="font-mono text-base font-semibold tracking-wider tabular-nums text-foreground">
                        {ownAccountDisplay}
                      </p>
                      <motion.button
                        whileTap={{ scale: 0.92 }}
                        onClick={copyAccountNumber}
                        disabled={!balanceInfo?.account_number}
                        className="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/60 px-2 py-1 text-[10px] font-mono uppercase tracking-wider text-muted-foreground hover:text-foreground hover:border-primary/30 transition-all disabled:opacity-40"
                        title="Salin nomor rekening"
                      >
                        <AnimatePresence mode="wait" initial={false}>
                          {copied ? (
                            <motion.span key="ok" initial={{ opacity: 0, y: 4 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -4 }} className="flex items-center gap-1">
                              <CheckCircle2 className="size-3 text-emerald-500" />
                            </motion.span>
                          ) : (
                            <motion.span key="copy" initial={{ opacity: 0, y: 4 }} animate={{ opacity: 1, y: 0 }} exit={{ opacity: 0, y: -4 }} className="flex items-center gap-1">
                              <Copy className="size-3" />
                            </motion.span>
                          )}
                        </AnimatePresence>
                      </motion.button>
                    </div>

                    {/* Status pill */}
                    <div className="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full border border-primary/30 bg-primary/5 backdrop-blur-sm">
                      <span className="relative flex size-1.5">
                        <span className="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-75" />
                        <span className="relative inline-flex size-1.5 rounded-full bg-emerald-500" />
                      </span>
                      <span className="text-[9px] font-mono uppercase tracking-widest text-primary font-semibold">
                        Ledger · v1.0.0
                      </span>
                    </div>
                  </div>
                </div>

                {/* Quick stats row */}
                <div className="grid grid-cols-3 gap-3 mb-6">
                  <div className="rounded-xl border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-900/40 backdrop-blur-sm p-3">
                    <p className="font-mono text-[9px] text-muted-foreground uppercase tracking-widest">Saldo ditahan</p>
                    <p className="mt-1 font-mono text-sm font-semibold tabular-nums text-foreground">
                      {money(balanceInfo?.hold_balance || 0)}
                    </p>
                  </div>
                  <div className="rounded-xl border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-900/40 backdrop-blur-sm p-3">
                    <p className="font-mono text-[9px] text-muted-foreground uppercase tracking-widest">Tier KYC</p>
                    <span className={`mt-1 inline-flex items-center gap-1 text-xs font-semibold ${
                      kycTier === "VERIFIED" ? "text-emerald-600 dark:text-emerald-400" : "text-amber-600 dark:text-amber-400"
                    }`}>
                      <span className={`size-1.5 rounded-full ${kycTier === "VERIFIED" ? "bg-emerald-500" : "bg-amber-500"} animate-pulse`} />
                      {kycTier}
                    </span>
                  </div>
                  <div className="rounded-xl border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-900/40 backdrop-blur-sm p-3">
                    <p className="font-mono text-[9px] text-muted-foreground uppercase tracking-widest">Mutasi</p>
                    <p className="mt-1 font-mono text-sm font-semibold tabular-nums text-foreground">
                      {transactions.length}
                    </p>
                  </div>
                </div>

                {/* Chart — Saldo rolling */}
                <div className="relative pt-5 border-t border-border/40">
                  <div className="flex items-center justify-between mb-3">
                    <div>
                      <p className="font-mono text-[10px] text-muted-foreground uppercase tracking-widest">Saldo rolling</p>
                      <p className="text-[10px] text-muted-foreground/70 mt-0.5">12 mutasi terakhir</p>
                    </div>
                    <span className="inline-flex items-center gap-1 text-[10px] font-mono uppercase tracking-widest text-primary">
                      <TrendingUp className="size-3" />
                      Trend
                    </span>
                  </div>
                  <div className="h-44">
                    {chartData.length > 1 ? (
                      <ResponsiveContainer width="100%" height="100%">
                        <AreaChart data={chartData}>
                          <defs>
                            <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                              <stop offset="0%" stopColor="currentColor" stopOpacity={0.35} />
                              <stop offset="100%" stopColor="currentColor" stopOpacity={0} />
                            </linearGradient>
                          </defs>
                          <XAxis
                            dataKey="date"
                            tick={{ fontSize: 10, fontFamily: "var(--font-mono)", fill: "var(--muted-foreground)" }}
                            tickLine={false}
                            axisLine={false}
                          />
                          <Tooltip
                            formatter={(value) => money(Number(value))}
                            contentStyle={{
                              backgroundColor: "var(--card)",
                              border: "1px solid var(--border)",
                              borderRadius: "12px",
                              fontSize: "12px",
                              fontFamily: "var(--font-mono)",
                              padding: "8px 12px",
                            }}
                            labelStyle={{ color: "var(--muted-foreground)", fontSize: 10 }}
                          />
                          <Area
                            dataKey="value"
                            type="monotone"
                            stroke="currentColor"
                            fill="url(#chartGradient)"
                            strokeWidth={2.5}
                            isAnimationActive={false}
                            dot={{ r: 3, fill: "currentColor", strokeWidth: 2, stroke: "var(--card)" }}
                            activeDot={{ r: 5 }}
                          />
                        </AreaChart>
                      </ResponsiveContainer>
                    ) : (
                      <div className="flex h-full items-center justify-center rounded-xl border border-dashed border-border/60 text-sm text-muted-foreground">
                        Grafik akan terbentuk setelah ada aktivitas transaksi.
                      </div>
                    )}
                  </div>
                </div>

                {/* Loan limit (optional) */}
                {showLoans && (
                  <div className="mt-5 pt-5 border-t border-border/40">
                    <LoanLimitCard kycTier={kycTier} limit={loanLimit} />
                  </div>
                )}
              </div>
            </motion.div>
          </div>
        </section>
      )}

      {showKyc && <KycDocumentCard onStatusChange={setKycTier} />}

      {/* ---- TRANSFER ---- */}
      {showTransfer && (
        <section id="transfer" className="scroll-mt-8 grid gap-5 lg:grid-cols-5">
          <motion.form
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.1 }}
            onSubmit={submitTransfer}
            className="lg:col-span-3 relative rounded-2xl border border-slate-200 dark:border-white/10 bg-white/80 dark:bg-slate-900/60 backdrop-blur-2xl p-6 md:p-8 shadow-[0_20px_60px_-20px_rgba(2,6,23,0.15),inset_0_1px_0_rgba(255,255,255,0.5)] overflow-hidden"
          >
            <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent pointer-events-none" />

            <div className="mb-5 flex items-center gap-3">
              <div className="p-2 rounded-xl bg-gradient-to-br from-blue-500/15 to-cyan-500/10 text-blue-600 dark:text-blue-400">
                <Send className="w-4 h-4" strokeWidth={2} />
              </div>
              <h2 className="font-display text-xl font-semibold tracking-tight">Transfer cepat</h2>
            </div>
            <p className="text-xs text-muted-foreground mb-5">Masukkan nomor rekening tujuan (10 digit). Wallet ID internal tidak lagi dipakai.</p>

            <div className="space-y-3.5">
              <FieldShell label="Nomor rekening tujuan" mono>
                <input
                  id="to-account"
                  required
                  value={formatAccountDisplay(transfer.accountNumber)}
                  onChange={(e) => setTransfer({ ...transfer, accountNumber: e.target.value })}
                  placeholder="1234-5678-90"
                  inputMode="numeric"
                  maxLength={12}
                  className="w-full bg-transparent px-3 py-2.5 text-sm font-mono tabular-nums outline-none"
                />
              </FieldShell>

              <AnimatePresence mode="wait">
                {transferDigits.length === 10 && (
                  <motion.div
                    key={recipient ? "ok" : lookingUp ? "loading" : lookupError ? "error" : "init"}
                    initial={{ opacity: 0, y: -8, height: 0 }}
                    animate={{ opacity: 1, y: 0, height: "auto" }}
                    exit={{ opacity: 0, y: -8, height: 0 }}
                    transition={{ duration: 0.25 }}
                    className={`overflow-hidden rounded-lg border p-3 text-xs ${
                      recipient
                        ? "border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                        : lookingUp || !recipientLookupAttempted
                          ? "border-border bg-secondary/40 text-muted-foreground"
                          : "border-destructive/30 bg-destructive/10 text-destructive"
                    }`}
                  >
                    <div className="flex items-start gap-2">
                      {lookingUp ? (
                        <Loader2 className="size-3.5 mt-0.5 shrink-0 animate-spin" />
                      ) : recipient ? (
                        <CheckCircle2 className="size-3.5 mt-0.5 shrink-0" />
                      ) : (
                        <AlertTriangle className="size-3.5 mt-0.5 shrink-0" />
                      )}
                      <div>
                        {recipient ? (
                          <>
                            <p className="font-semibold">{recipient.holder_name}</p>
                            <p className="text-[11px] opacity-80 mt-0.5">Pemilik rekening terverifikasi.</p>
                          </>
                        ) : lookingUp || !recipientLookupAttempted ? (
                          <p>Memverifikasi nomor rekening...</p>
                        ) : (
                          <p>Nomor rekening tidak ditemukan.</p>
                        )}
                      </div>
                    </div>
                  </motion.div>
                )}
              </AnimatePresence>

              <div className="grid gap-3 sm:grid-cols-2">
                <FieldShell label="Nominal" mono>
                  <input
                    id="tx-amount"
                    required
                    min="1"
                    type="number"
                    value={transfer.amount}
                    onChange={(e) => setTransfer({ ...transfer, amount: e.target.value })}
                    placeholder="10000"
                    className="w-full bg-transparent px-3 py-2.5 text-sm font-mono outline-none"
                  />
                </FieldShell>
                <FieldShell label="PIN 6 digit" mono>
                  <div className="relative">
                    <KeyRound className="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground pointer-events-none" />
                    <input
                      id="tx-pin"
                      required
                      inputMode="numeric"
                      maxLength={6}
                      type={showPin ? "text" : "password"}
                      value={transfer.pin}
                      onChange={(e) => setTransfer({ ...transfer, pin: e.target.value })}
                      placeholder="••••••"
                      className="w-full bg-transparent pl-9 pr-9 py-2.5 text-sm font-mono tracking-widest outline-none"
                    />
                    <button
                      type="button"
                      onClick={() => setShowPin((v) => !v)}
                      tabIndex={-1}
                      className="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 text-muted-foreground hover:text-foreground rounded"
                    >
                      {showPin ? <EyeOff className="size-3.5" /> : <Eye className="size-3.5" />}
                    </button>
                  </div>
                </FieldShell>
              </div>

              <FieldShell label="Catatan (opsional)">
                <input
                  id="tx-note"
                  value={transfer.note}
                  onChange={(e) => setTransfer({ ...transfer, note: e.target.value })}
                  placeholder="Catatan transaksi"
                  className="w-full bg-transparent px-3 py-2.5 text-sm outline-none"
                />
              </FieldShell>

              <PremiumButton
                type="submit"
                disabled={processing === "transfer" || !recipient || lookupError}
                loading={processing === "transfer"}
                label={recipient ? `Kirim ke ${recipient.holder_name.split(" ")[0]}` : "Kirim dana"}
                icon={<Send className="size-4" />}
              />
            </div>
          </motion.form>

          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.2 }}
            className="lg:col-span-2 relative rounded-2xl border border-slate-200 dark:border-white/10 bg-white/80 dark:bg-slate-900/60 backdrop-blur-2xl p-6 shadow-[0_20px_60px_-20px_rgba(2,6,23,0.15),inset_0_1px_0_rgba(255,255,255,0.5)] overflow-hidden"
          >
            <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent pointer-events-none" />
            <div className="flex items-center gap-2 mb-3">
              <ShieldCheck className="size-4 text-emerald-500" />
              <h3 className="font-display font-semibold text-foreground">Catatan transfer</h3>
            </div>
            <p className="text-xs text-muted-foreground leading-relaxed">
              Transfer butuh PIN 6-digit. Disettle via Central Bank dengan fee, pajak, dan cooldown.
              Hanya nomor rekening — bukan Wallet ID internal.
            </p>
            <div className="mt-5 space-y-2">
              {[
                { label: "Cooldown", value: "10 detik" },
                { label: "Daily limit", value: "10 transaksi" },
                { label: "Fee bank", value: "1.00%" },
              ].map((s) => (
                <div key={s.label} className="flex items-center justify-between text-xs py-1.5 border-b border-border/40 last:border-0">
                  <span className="text-muted-foreground">{s.label}</span>
                  <span className="font-mono font-semibold text-foreground tabular-nums">{s.value}</span>
                </div>
              ))}
            </div>
          </motion.div>
        </section>
      )}

      {/* ---- LOANS ---- */}
      {showLoans && (
        <section id="loans" className="scroll-mt-8 space-y-6">
          <div className="grid gap-5 lg:grid-cols-12">
            <motion.form
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.1 }}
              onSubmit={submitLoan}
              className="lg:col-span-5 relative rounded-2xl border border-slate-200 dark:border-white/10 bg-white/80 dark:bg-slate-900/60 backdrop-blur-2xl p-6 md:p-8 shadow-[0_20px_60px_-20px_rgba(2,6,23,0.15),inset_0_1px_0_rgba(255,255,255,0.5)] overflow-hidden"
            >
              <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent pointer-events-none" />

              <div className="flex items-center gap-3 mb-3">
                <div className="p-2 rounded-xl bg-gradient-to-br from-emerald-500/15 to-teal-500/10 text-emerald-600 dark:text-emerald-400">
                  <BadgeDollarSign className="w-4 h-4" strokeWidth={2} />
                </div>
                <h2 className="font-display text-xl font-semibold tracking-tight">Ajukan pinjaman</h2>
              </div>
              <p className="text-xs text-muted-foreground mb-5">
                Pengajuan tidak langsung dicairkan. Manager harus menyetujui.
              </p>

              {kycTier !== "VERIFIED" && (
                <div className="mb-4 flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-xs text-amber-700 dark:text-amber-400">
                  <AlertTriangle size={15} className="shrink-0 mt-0.5" />
                  Upload dokumen dan tunggu Teller memverifikasi KYC.
                </div>
              )}

              <div className="space-y-3.5">
                <FieldShell label="Nominal" mono>
                  <input
                    required
                    min="1"
                    type="number"
                    value={loan.amount}
                    onChange={(e) => setLoan({ ...loan, amount: e.target.value })}
                    placeholder={`Max ${money(loanLimit.remaining)}`}
                    className="w-full bg-transparent px-3 py-2.5 text-sm font-mono outline-none"
                  />
                </FieldShell>
                <FieldShell label="Jangka waktu" mono>
                  <select
                    value={loan.term}
                    onChange={(e) => setLoan({ ...loan, term: e.target.value })}
                    className="w-full bg-transparent px-3 py-2.5 text-sm outline-none"
                  >
                    <option value="3">3 bulan</option>
                    <option value="6">6 bulan</option>
                    <option value="12">12 bulan</option>
                    <option value="24">24 bulan</option>
                  </select>
                </FieldShell>

                <PremiumButton
                  type="submit"
                  disabled={processing === "loan" || kycTier !== "VERIFIED" || loanLimit.remaining <= 0}
                  loading={processing === "loan"}
                  label={loanLimit.remaining <= 0 ? "Limit habis" : "Kirim pengajuan"}
                  icon={<Send className="size-4" />}
                  variant="emerald"
                />
              </div>
            </motion.form>

            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.18 }}
              className="lg:col-span-7 relative rounded-2xl border border-slate-200 dark:border-white/10 bg-white/80 dark:bg-slate-900/60 backdrop-blur-2xl p-6 md:p-8 shadow-[0_20px_60px_-20px_rgba(2,6,23,0.15),inset_0_1px_0_rgba(255,255,255,0.5)] overflow-hidden"
            >
              <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent pointer-events-none" />

              <div className="flex items-center gap-3 mb-5">
                <div className="p-2 rounded-xl bg-gradient-to-br from-cyan-500/15 to-blue-500/10 text-cyan-600 dark:text-cyan-400">
                  <CheckCircle2 className="w-4 h-4" strokeWidth={2} />
                </div>
                <h2 className="font-display text-xl font-semibold tracking-tight">Pinjaman Anda</h2>
              </div>

              {activeLoans.length === 0 ? (
                <div className="rounded-xl border border-dashed border-border/60 bg-background/30 p-8 text-center">
                  <BadgeDollarSign className="size-8 text-muted-foreground/40 mx-auto mb-2" />
                  <p className="text-sm text-muted-foreground">Belum ada pinjaman aktif.</p>
                </div>
              ) : (
                <div className="space-y-3">
                  {activeLoans.map((ln) => {
                    const isExpanded = expandedRepayId === ln.id;
                    const progressPct = ln.total_due > 0 ? Math.min(100, (ln.paid_amount / ln.total_due) * 100) : 0;
                    return (
                      <motion.div
                        key={ln.id}
                        layout
                        className="rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-slate-900/40 p-4"
                      >
                        <div className="flex items-start justify-between gap-2">
                          <div>
                            <p className="font-mono text-[10px] uppercase tracking-widest text-muted-foreground">{ln.status.replaceAll("_", " ")}</p>
                            <p className="mt-1 font-display text-lg font-semibold tabular-nums text-foreground">{money(ln.total_due)}</p>
                            <p className="text-[11px] text-muted-foreground mt-0.5">Pokok {money(ln.principal)} · Terbayar {money(ln.paid_amount)}</p>
                          </div>
                          <div className="text-right">
                            <p className="text-[10px] text-muted-foreground uppercase tracking-widest">Sisa</p>
                            <p className="font-mono text-base font-semibold tabular-nums text-primary">{money(ln.remaining)}</p>
                          </div>
                        </div>
                        <div className="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-secondary/40">
                          <motion.div
                            initial={{ width: 0 }}
                            animate={{ width: `${progressPct}%` }}
                            transition={{ duration: 1, ease: [0.16, 1, 0.3, 1] }}
                            className="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full"
                          />
                        </div>
                        <AnimatePresence>
                          {(ln.status === "DISBURSED" || ln.status === "PARTIAL_PAID") && (
                            <button
                              type="button"
                              onClick={() => openRepayForm(ln)}
                              className="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                            >
                              {isExpanded ? <ChevronUp className="size-3" /> : <ChevronDown className="size-3" />}
                              {isExpanded ? "Tutup" : "Bayar cicilan"}
                            </button>
                          )}
                        </AnimatePresence>
                        <AnimatePresence>
                          {isExpanded && (
                            <motion.form
                              initial={{ opacity: 0, height: 0 }}
                              animate={{ opacity: 1, height: "auto" }}
                              exit={{ opacity: 0, height: 0 }}
                              onSubmit={(e) => submitRepayment(e, ln.id)}
                              className="mt-3 space-y-2.5 border-t border-border/40 pt-3 overflow-hidden"
                            >
                              <div className="grid gap-2 sm:grid-cols-2">
                                <FieldShell label="Nominal bayar" mono>
                                  <input
                                    required
                                    min="1"
                                    type="number"
                                    value={repayDraft.amount}
                                    onChange={(e) => setRepayDraft({ ...repayDraft, amount: e.target.value })}
                                    placeholder="Nominal bayar"
                                    className="w-full bg-transparent px-3 py-2 text-sm font-mono outline-none"
                                  />
                                </FieldShell>
                                <FieldShell label="PIN 6 digit" mono>
                                  <input
                                    required
                                    type="password"
                                    inputMode="numeric"
                                    maxLength={6}
                                    value={repayDraft.pin}
                                    onChange={(e) => setRepayDraft({ ...repayDraft, pin: e.target.value })}
                                    placeholder="••••••"
                                    className="w-full bg-transparent px-3 py-2 text-sm font-mono tracking-widest outline-none"
                                  />
                                </FieldShell>
                              </div>
                              <PremiumButton
                                type="submit"
                                disabled={processing === `repayment-${ln.id}`}
                                loading={processing === `repayment-${ln.id}`}
                                size="sm"
                                label={`Bayar ${money(ln.remaining)}`}
                                icon={<Send className="size-3.5" />}
                              />
                            </motion.form>
                          )}
                        </AnimatePresence>
                      </motion.div>
                    );
                  })}
                </div>
              )}
            </motion.div>
          </div>
        </section>
      )}

      {/* ---- ACTIVITY ---- */}
      {showActivity && (
        <section id="activity" className="scroll-mt-8">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ duration: 0.6, delay: 0.1 }}
            className="relative overflow-hidden rounded-2xl border border-slate-200 dark:border-white/10 bg-white/80 dark:bg-slate-900/60 backdrop-blur-2xl shadow-[0_20px_60px_-20px_rgba(2,6,23,0.15),inset_0_1px_0_rgba(255,255,255,0.5)]"
          >
            <div className="flex items-center justify-between border-b border-border/40 p-5">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-xl bg-gradient-to-br from-violet-500/15 to-purple-500/10 text-violet-600 dark:text-violet-400">
                  <Clock className="w-4 h-4" strokeWidth={2} />
                </div>
                <h2 className="font-display text-xl font-semibold tracking-tight">Aktivitas terbaru</h2>
              </div>
              <span className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground">10 terbaru</span>
            </div>

            {transactions.length === 0 ? (
              <div className="p-12 text-center">
                <Wallet className="size-10 text-muted-foreground/30 mx-auto mb-3" />
                <p className="text-sm text-muted-foreground">Belum ada transaksi untuk ditampilkan.</p>
              </div>
            ) : (
              <div className="divide-y divide-border/30">
                {transactions.slice(0, 10).map((item, i) => (
                  <motion.div
                    key={item.id}
                    initial={{ opacity: 0, x: -8 }}
                    animate={{ opacity: 1, x: 0 }}
                    transition={{ duration: 0.4, delay: i * 0.04 }}
                    className="flex items-center justify-between gap-4 p-4 md:px-6 hover:bg-secondary/20 transition-colors"
                  >
                    <div className="flex min-w-0 items-center gap-3">
                      <div className={`shrink-0 size-9 rounded-full flex items-center justify-center ${
                        isCredit(item.direction) ? "bg-emerald-500/15 text-emerald-600 dark:text-emerald-400" : "bg-blue-500/15 text-blue-600 dark:text-blue-400"
                      }`}>
                        {isCredit(item.direction) ? <ArrowDownLeft size={16} /> : <ArrowUpRight size={16} />}
                      </div>
                      <div className="min-w-0">
                        <p className="truncate text-sm font-semibold text-foreground">{item.transaction_type.replaceAll("_", " ")}</p>
                        <p className="text-[11px] text-muted-foreground font-mono uppercase tracking-wider">
                          {new Date(item.created_at).toLocaleString("id-ID")} · {item.status}
                        </p>
                      </div>
                    </div>
                    <p className={`shrink-0 font-mono text-sm font-semibold tabular-nums ${isCredit(item.direction) ? "text-emerald-600 dark:text-emerald-400" : "text-foreground"}`}>
                      {isCredit(item.direction) ? "+" : "−"} {money(isCredit(item.direction) ? item.gross_amount : item.total_debit)}
                    </p>
                  </motion.div>
                ))}
              </div>
            )}
          </motion.div>
        </section>
      )}
    </div>
  );
}

/* -------------------------------------------------------------------------- */
/*  Sub-components (extracted for clarity)                                    */
/* -------------------------------------------------------------------------- */

function LoanLimitCard({ kycTier, limit }: { kycTier: string; limit: LoanLimit }) {
  const usedPct = limit.cap > 0 ? Math.min(100, (limit.outstanding / limit.cap) * 100) : 0;
  return (
    <motion.div
      initial={{ opacity: 0, y: 16 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.5, delay: 0.32 }}
      className="rounded-2xl border border-slate-200 dark:border-white/10 bg-white/80 dark:bg-slate-900/60 backdrop-blur-2xl p-5 shadow-[0_20px_60px_-20px_rgba(2,6,23,0.15),inset_0_1px_0_rgba(255,255,255,0.5)] overflow-hidden relative"
    >
      <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent pointer-events-none" />
      <p className="font-mono text-[10px] text-muted-foreground uppercase tracking-widest">Limit Pinjaman</p>
      {kycTier !== "VERIFIED" ? (
        <>
          <p className="mt-2 text-sm text-amber-700 dark:text-amber-400">Selesaikan verifikasi KYC untuk membuka akses pinjaman.</p>
          <p className="mt-1 text-[11px] text-muted-foreground">Setelah KYC VERIFIED, limit hingga {money(limit.cap)}.</p>
        </>
      ) : (
        <>
          <p className="mt-2 font-mono text-lg font-semibold tabular-nums text-foreground">
            {money(limit.remaining)} <span className="text-xs text-muted-foreground font-normal">/ {money(limit.cap)}</span>
          </p>
          <div className="mt-3 h-2 w-full overflow-hidden rounded-full bg-secondary/40">
            <motion.div
              initial={{ width: 0 }}
              animate={{ width: `${usedPct}%` }}
              transition={{ duration: 1.2, ease: [0.16, 1, 0.3, 1] }}
              className={`h-full rounded-full ${limit.remaining <= 0 ? "bg-destructive" : "bg-gradient-to-r from-emerald-500 to-cyan-500"}`}
            />
          </div>
          <p className="mt-2 text-[11px] text-muted-foreground">Aktif {money(limit.outstanding)} · Sisa {money(limit.remaining)}</p>
        </>
      )}
    </motion.div>
  );
}

function FieldShell({
  label,
  mono,
  children,
}: {
  label: string;
  mono?: boolean;
  children: React.ReactNode;
}) {
  return (
    <div className="space-y-1.5">
      <label className="text-[11px] font-medium text-muted-foreground uppercase tracking-wider">{label}</label>
      <div className={`relative rounded-lg border border-slate-200 dark:border-white/10 bg-white/60 dark:bg-slate-900/40 backdrop-blur-sm transition-all focus-within:border-primary focus-within:shadow-[0_0_0_3px_rgba(37,99,235,0.12)]`}>
        {children}
      </div>
    </div>
  );
}

function PremiumButton({
  type = "button",
  onClick,
  disabled,
  loading,
  label,
  icon,
  variant = "blue",
  size = "md",
}: {
  type?: "button" | "submit";
  onClick?: () => void;
  disabled?: boolean;
  loading?: boolean;
  label: string;
  icon?: React.ReactNode;
  variant?: "blue" | "emerald";
  size?: "sm" | "md";
}) {
  const variantClasses = variant === "emerald"
    ? "bg-emerald-600 hover:shadow-[0_15px_40px_-10px_rgba(16,185,129,0.7)]"
    : "bg-primary hover:shadow-[0_15px_40px_-10px_rgba(37,99,235,0.7)]";

  return (
    <button
      type={type}
      onClick={onClick}
      disabled={disabled}
      className={`group relative w-full overflow-hidden inline-flex items-center justify-center gap-2 ${variantClasses} text-primary-foreground font-semibold rounded-xl ${
        size === "sm" ? "py-2 text-sm" : "py-3 text-sm"
      } transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none shadow-[0_10px_30px_-10px_rgba(37,99,235,0.5),inset_0_1px_0_rgba(255,255,255,0.15)]`}
    >
      <span className="relative z-10 flex items-center gap-2">
        {loading ? (
          <>
            <Loader2 className="size-4 animate-spin" />
            Memproses...
          </>
        ) : (
          <>
            {icon}
            {label}
          </>
        )}
      </span>
      {!loading && (
        <span className="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/20 to-transparent" />
      )}
    </button>
  );
}
