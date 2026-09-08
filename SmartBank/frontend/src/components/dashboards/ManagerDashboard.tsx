"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { AlertTriangle, BadgeDollarSign, CheckCircle2, RefreshCw, Search, ShieldAlert, UserCheck, UserX, Wallet, XCircle } from "lucide-react";
import { fetchApi } from "@/lib/api";

type Customer = {
  id: string;
  name: string;
  email: string;
  phone?: string;
  role: string;
  status: string;
  kycTier: string;
  wallets?: Array<{ id: string; availableBalance: string | number; status: string }>;
};

type PendingLoan = {
  id: string;
  principal: string | number;
  interest_amount: string | number;
  total_due: string | number;
  status: string;
  created_at: string;
  recommended_by?: string | null;
  recommended_at?: string | null;
  recommendation_note?: string | null;
  borrower?: {
    id: string;
    name: string;
    email: string;
    phone?: string;
    kycTier: string;
    status: string;
    identityDocumentType?: string | null;
    identityDocumentNumber?: string | null;
    identityDocumentName?: string | null;
  } | null;
  wallet?: { id: string; available_balance: string | number; status: string };
};

type LoanPool = {
  wallet_id: string;
  account_code: string;
  available_balance: string;
  hold_balance: string;
  status: string;
};

type LoanFilter = "all" | "large" | "recommended";

const money = (value: string | number | undefined) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(value || 0));
const unwrap = <T,>(response: { data?: T } | T): T | undefined =>
  typeof response === "object" && response !== null && "data" in response ? (response as { data?: T }).data : (response as T);

export default function ManagerDashboard() {
  const [query, setQuery] = useState("");
  const [customer, setCustomer] = useState<Customer | null>(null);
  const [pendingLoans, setPendingLoans] = useState<PendingLoan[]>([]);
  const [loanPool, setLoanPool] = useState<LoanPool | null>(null);
  const [loanFilter, setLoanFilter] = useState<LoanFilter>("all");
  const [reasonCode, setReasonCode] = useState("MANAGER_REVIEW");
  const [processing, setProcessing] = useState("");
  const [notice, setNotice] = useState<{ tone: "success" | "error"; text: string } | null>(null);

  const searchCustomer = async (event?: FormEvent) => {
    event?.preventDefault();
    if (!query.trim()) return;
    setProcessing("search");
    setNotice(null);
    try {
      const response = await fetchApi<{ data?: Customer } | Customer>(`/api/bank/teller/customer?query=${encodeURIComponent(query.trim())}`);
      const data = unwrap<Customer>(response);
      setCustomer(data || null);
    } catch (error) {
      setCustomer(null);
      setNotice({ tone: "error", text: error instanceof Error ? error.message : "Nasabah tidak ditemukan." });
    } finally {
      setProcessing("");
    }
  };

  // Bangun query string untuk /manager/loans/pending berdasarkan filter aktif
  const buildPendingQuery = (filter: LoanFilter): string => {
    const params = new URLSearchParams();
    if (filter === "large") params.set("min_amount", "50001");
    if (filter === "recommended") params.set("recommended", "true");
    const qs = params.toString();
    return qs ? `?${qs}` : "";
  };

  const loadPendingLoans = useCallback(async (filter: LoanFilter = loanFilter) => {
    try {
      const response = await fetchApi<{ data?: PendingLoan[] } | PendingLoan[]>(`/api/bank/manager/loans/pending${buildPendingQuery(filter)}`);
      const data = unwrap<PendingLoan[]>(response);
      setPendingLoans(Array.isArray(data) ? data : []);
    } catch (error) {
      setNotice({ tone: "error", text: error instanceof Error ? error.message : "Daftar pinjaman pending gagal dimuat." });
    }
  }, [loanFilter]);

  const loadLoanPool = useCallback(async () => {
    try {
      const response = await fetchApi<{ data?: LoanPool } | LoanPool>("/api/bank/manager/loan-pool");
      const data = unwrap<LoanPool>(response);
      setLoanPool(data || null);
    } catch {
      // Pool mungkin belum di-seed; abaikan agar UI tidak crash
      setLoanPool(null);
    }
  }, []);

  useEffect(() => {
    const t1 = window.setTimeout(() => void loadPendingLoans("all"), 0);
    const t2 = window.setTimeout(() => void loadLoanPool(), 0);
    return () => { window.clearTimeout(t1); window.clearTimeout(t2); };
  }, [loadPendingLoans, loadLoanPool]);

  // Reload saat filter tab berubah
  useEffect(() => {
    const t = window.setTimeout(() => void loadPendingLoans(loanFilter), 0);
    return () => window.clearTimeout(t);
  }, [loanFilter, loadPendingLoans]);

  const runAction = async (key: string, endpoint: string, payload: object, success: string) => {
    setProcessing(key);
    setNotice(null);
    try {
      await fetchApi(endpoint, { method: "POST", body: JSON.stringify(payload) });
      setNotice({ tone: "success", text: success });
      if (customer) await searchCustomer();
      await loadPendingLoans();
      await loadLoanPool();
    } catch (error) {
      setNotice({ tone: "error", text: error instanceof Error ? error.message : "Aksi gagal diproses." });
    } finally {
      setProcessing("");
    }
  };

  const userAction = (action: "suspend" | "activate") => {
    if (!customer) return;
    void runAction(action, `/api/bank/manager/users/${action}`, { userId: customer.id, reasonCode: reasonCode.trim() }, `Status ${customer.name} berhasil diperbarui.`);
  };

  const loanAction = (loanId: string, action: "approve" | "reject") => {
    void runAction(action, `/api/bank/manager/loans/${action}`, { loanId, reasonCode: reasonCode.trim() }, `Pinjaman ${loanId} berhasil ${action === "approve" ? "disetujui" : "ditolak"}.`);
  };

  const poolBalance = loanPool ? Number(loanPool.available_balance) : null;
  const poolLow = poolBalance !== null && poolBalance < 100_000;

  return (
    <div className="space-y-8">
      <header>
        <p className="mb-2 font-mono text-xs font-semibold uppercase text-primary">Manager control center</p>
        <h1 className="text-balance font-display text-3xl font-semibold">Keputusan risiko yang dapat ditelusuri</h1>
        <p className="mt-2 max-w-2xl text-pretty text-sm text-muted-foreground">Cari profil nasabah sebelum mengubah status akun dan sertakan reason code pada setiap keputusan sensitif.</p>
      </header>

      {notice && <div className={`rounded-xl border p-4 text-sm ${notice.tone === "success" ? "border-primary/30 bg-primary/10 text-primary" : "border-destructive/30 bg-destructive/10 text-destructive"}`}>{notice.text}</div>}

      <section id="loan-pool" className="grid scroll-mt-8 gap-6 lg:grid-cols-3">
        <article className={`rounded-2xl border p-6 shadow-sm lg:col-span-1 ${poolLow ? "border-destructive/30 bg-destructive/5" : "border-border bg-card"}`}>
          <div className="flex items-center justify-between gap-2">
            <div className="flex items-center gap-3"><BadgeDollarSign className={poolLow ? "text-destructive" : "text-primary"} size={20} /><h2 className="font-display text-lg font-semibold">Saldo Loan Pool</h2></div>
            <button onClick={() => void loadLoanPool()} disabled={Boolean(processing)} className="rounded-md p-1.5 text-muted-foreground hover:bg-secondary" title="Segarkan"><RefreshCw size={14} /></button>
          </div>
          <p className="mt-3 font-mono text-3xl font-semibold tabular-nums">{poolBalance === null ? "…" : money(poolBalance)}</p>
          <p className="mt-1 text-xs text-muted-foreground">Dana tersedia untuk dicairkan ke nasabah. Saldo rendah = warning, approval baru akan ditolak settlement.</p>
          {poolLow && <p className="mt-3 inline-flex rounded-full bg-destructive/15 px-3 py-1 text-xs font-semibold text-destructive">Saldo menipis (kurang dari 100.000)</p>}
        </article>

        <article className="rounded-2xl border border-border bg-card p-6 shadow-sm lg:col-span-2">
          <h2 className="font-display text-lg font-semibold">Distribusi antrean pinjaman</h2>
          <p className="mt-2 text-sm text-muted-foreground">Filter antrean berdasarkan rekomendasi Teller atau nominal besar. Pinjaman kecil (≤ 50.000) melalui Teller dulu, pinjaman besar langsung Manager.</p>
          <div className="mt-5 flex flex-wrap gap-2">
            <TabButton active={loanFilter === "all"} onClick={() => setLoanFilter("all")} label={`Semua (${pendingLoans.length})`} />
            <TabButton active={loanFilter === "large"} onClick={() => setLoanFilter("large")} label="Besar (> 50.000)" />
            <TabButton active={loanFilter === "recommended"} onClick={() => setLoanFilter("recommended")} label="Sudah Direkomendasikan Teller" />
          </div>
        </article>
      </section>

      <section id="risk" className="grid scroll-mt-8 gap-6 lg:grid-cols-5">
        <article className="rounded-2xl border border-border bg-card p-6 shadow-sm lg:col-span-3">
          <div className="mb-5 flex items-center gap-3"><ShieldAlert className="text-primary" size={20} /><h2 className="font-display text-xl font-semibold">Kontrol risiko akun</h2></div>
          <form onSubmit={searchCustomer} className="flex flex-col gap-3 sm:flex-row">
            <input required value={query} onChange={(e) => setQuery(e.target.value)} placeholder="Email, telepon, atau User ID" className="min-w-0 flex-1 rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-primary" />
            <button disabled={processing === "search"} className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"><Search size={16} /> {processing === "search" ? "Mencari..." : "Cari nasabah"}</button>
          </form>

          {customer ? (
            <div className="mt-6 rounded-xl border border-border bg-background p-5">
              <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div><p className="font-display text-lg font-semibold">{customer.name}</p><p className="text-sm text-muted-foreground">{customer.email} · {customer.phone || "Tanpa telepon"}</p><p className="mt-2 font-mono text-xs text-muted-foreground">{customer.id}</p></div>
                <div className="flex gap-2"><span className="rounded-full bg-secondary px-3 py-1 text-xs font-semibold">{customer.status}</span><span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">{customer.kycTier}</span></div>
              </div>
              <div className="mt-5 flex items-center gap-3 border-t border-border pt-4"><Wallet size={17} className="text-primary" /><div><p className="text-xs text-muted-foreground">Saldo dompet utama</p><p className="font-mono font-semibold tabular-nums">{money(customer.wallets?.[0]?.availableBalance)}</p></div></div>
              <div className="mt-5 grid gap-3 sm:grid-cols-2">
                <button onClick={() => userAction("suspend")} disabled={Boolean(processing)} className="inline-flex items-center justify-center gap-2 rounded-lg border border-destructive/30 bg-destructive/10 px-4 py-2 text-sm font-semibold text-destructive hover:bg-destructive/20 disabled:opacity-50"><UserX size={16} /> Bekukan akun</button>
                <button onClick={() => userAction("activate")} disabled={Boolean(processing)} className="inline-flex items-center justify-center gap-2 rounded-lg border border-primary/30 bg-primary/10 px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/20 disabled:opacity-50"><UserCheck size={16} /> Aktifkan akun</button>
              </div>
            </div>
          ) : <div className="mt-6 rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">Cari nasabah untuk melihat konteks sebelum mengambil tindakan.</div>}
        </article>

        <aside className="rounded-2xl border border-border bg-card p-6 shadow-sm lg:col-span-2">
          <h2 className="font-display text-lg font-semibold">Alasan keputusan</h2>
          <p className="mt-2 text-sm text-muted-foreground">Reason code yang sama digunakan untuk aksi berikutnya dan disimpan pada audit log.</p>
          <label className="mt-5 block text-xs font-semibold uppercase text-muted-foreground">Reason code</label>
          <input required value={reasonCode} onChange={(e) => setReasonCode(e.target.value)} className="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 font-mono text-sm outline-none focus:border-primary" />
          <div className="mt-6 rounded-xl bg-secondary/50 p-4 text-sm text-muted-foreground"><AlertTriangle size={18} className="mb-2 text-primary" /> Verifikasi identitas dan konteks kasus sebelum suspend, activate, approve, atau reject.</div>
        </aside>
      </section>

      <section id="loans" className="scroll-mt-8 rounded-2xl border border-border bg-card p-6 shadow-sm">
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <div>
            <div className="flex items-center gap-3"><CheckCircle2 className="text-primary" size={20} /><h2 className="font-display text-xl font-semibold">Antrean approval pinjaman</h2></div>
            <p className="mt-2 text-sm text-muted-foreground">Pinjaman PENDING muncul otomatis. Badge menunjukkan apakah sudah di-screening Teller atau langsung dari Nasabah.</p>
          </div>
          <button onClick={() => void loadPendingLoans(loanFilter)} disabled={Boolean(processing)} className="rounded-lg border border-border bg-secondary px-4 py-2 text-sm font-semibold hover:bg-secondary/80 disabled:opacity-50">Segarkan</button>
        </div>

        <div className="mt-6 space-y-4">
          {pendingLoans.length === 0 ? (
            <div className="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">Tidak ada pinjaman yang menunggu approval pada filter ini.</div>
          ) : pendingLoans.map((loan) => {
            const isSmall = Number(loan.principal) <= 50_000;
            return (
              <article key={loan.id} className="rounded-2xl border border-border bg-background p-5">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                  <div>
                    <p className="font-mono text-xs text-muted-foreground">{loan.id}</p>
                    <h3 className="mt-2 font-display text-lg font-semibold">{loan.borrower?.name || "Nasabah"}</h3>
                    <p className="text-sm text-muted-foreground">{loan.borrower?.email} - {loan.borrower?.phone || "Tanpa telepon"}</p>
                    <div className="mt-3 flex flex-wrap gap-2">
                      <span className="rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold text-primary">{loan.borrower?.kycTier}</span>
                      <span className="rounded-full bg-secondary px-3 py-1 text-xs font-semibold">{loan.borrower?.status}</span>
                      <span className="rounded-full bg-secondary px-3 py-1 text-xs font-semibold">{loan.wallet?.status}</span>
                      {loan.recommended_by ? (
                        <span className="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-700">Sudah direkomendasikan Teller</span>
                      ) : isSmall ? (
                        <span className="rounded-full bg-secondary px-3 py-1 text-xs font-semibold">≤ 50k • Belum screening Teller</span>
                      ) : (
                        <span className="rounded-full bg-secondary px-3 py-1 text-xs font-semibold">&gt; 50k • Direct</span>
                      )}
                    </div>
                    {loan.recommendation_note && (
                      <p className="mt-2 rounded-md bg-amber-500/5 px-3 py-2 text-xs text-amber-700">Catatan Teller: {loan.recommendation_note}</p>
                    )}
                  </div>
                  <div className="grid gap-3 text-sm sm:grid-cols-4 lg:min-w-[560px]">
                    <div><p className="text-xs text-muted-foreground">Saldo</p><p className="font-mono font-semibold">{money(loan.wallet?.available_balance)}</p></div>
                    <div><p className="text-xs text-muted-foreground">Pokok</p><p className="font-mono font-semibold">{money(loan.principal)}</p></div>
                    <div><p className="text-xs text-muted-foreground">Bunga</p><p className="font-mono font-semibold">{money(loan.interest_amount)}</p></div>
                    <div><p className="text-xs text-muted-foreground">Total due</p><p className="font-mono font-semibold">{money(loan.total_due)}</p></div>
                  </div>
                </div>
                <div className="mt-5 rounded-xl bg-secondary/50 p-4 text-xs text-muted-foreground">
                  Dokumen: {loan.borrower?.identityDocumentType || "-"} {loan.borrower?.identityDocumentNumber || ""} {loan.borrower?.identityDocumentName ? `atas nama ${loan.borrower.identityDocumentName}` : ""}
                </div>
                <div className="mt-5 grid gap-3 sm:grid-cols-2">
                  <button onClick={() => loanAction(loan.id, "approve")} disabled={Boolean(processing)} className="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"><CheckCircle2 size={16} /> Setujui pinjaman</button>
                  <button onClick={() => loanAction(loan.id, "reject")} disabled={Boolean(processing)} className="inline-flex items-center justify-center gap-2 rounded-lg border border-border bg-secondary px-4 py-2 text-sm font-semibold hover:bg-secondary/80 disabled:opacity-50"><XCircle size={16} /> Tolak pinjaman</button>
                </div>
              </article>
            );
          })}
        </div>
      </section>
    </div>
  );
}

function TabButton({ active, onClick, label }: { active: boolean; onClick: () => void; label: string }) {
  return (
    <button
      type="button"
      onClick={onClick}
      className={`rounded-full px-3 py-1.5 text-xs font-semibold transition ${active ? "bg-primary text-primary-foreground" : "border border-border bg-card text-muted-foreground hover:text-foreground"}`}
    >
      {label}
    </button>
  );
}
