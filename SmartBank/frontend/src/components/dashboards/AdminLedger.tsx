"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { Landmark, RefreshCw, Search } from "lucide-react";
import { fetchApi } from "@/lib/api";

type LedgerEntry = {
  id: string;
  transactionId: string;
  accountId: string;
  direction: string;
  amount: string | number;
  balanceAfter: string | number;
  description?: string;
  createdAt: string;
};

const money = (v: string | number | undefined) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(v || 0));

const unwrap = <T,>(r: { data?: T } | T): T =>
  typeof r === "object" && r !== null && "data" in r ? (r as { data: T }).data : (r as T);

export default function AdminLedger() {
  const [ledger, setLedger] = useState<LedgerEntry[]>([]);
  const [accountId, setAccountId] = useState("");
  const [transactionId, setTransactionId] = useState("");
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    const p = new URLSearchParams();
    if (accountId.trim()) p.set("account_id", accountId.trim());
    if (transactionId.trim()) p.set("transaction_id", transactionId.trim());
    const r = await fetchApi<{ data?: LedgerEntry[] } | LedgerEntry[]>(
      `/api/bank/central-bank/ledger${p.size ? `?${p}` : ""}`
    );
    const d = unwrap(r);
    setLedger(Array.isArray(d) ? d : []);
  }, [accountId, transactionId]);

  const refresh = useCallback(async () => {
    setLoading(true);
    try { await load(); } finally { setLoading(false); }
  }, [load]);

  useEffect(() => { void refresh(); }, [refresh]);

  const submit = async (e: FormEvent) => { e.preventDefault(); setLoading(true); try { await load(); } finally { setLoading(false); } };

  return (
    <div className="space-y-8">
      <header className="flex items-end justify-between">
        <div>
          <p className="mb-2 font-mono text-xs font-semibold uppercase text-primary">Ledger</p>
          <h1 className="font-display text-3xl font-semibold">Penelusuran ledger</h1>
        </div>
        <button onClick={() => void refresh()} disabled={loading} className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2 text-sm font-semibold shadow-sm hover:bg-secondary disabled:opacity-50">
          <RefreshCw size={16} className={loading ? "animate-spin" : ""} /> Segarkan
        </button>
      </header>

      <section className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
        <div className="border-b border-border p-5">
          <div className="mb-4 flex items-center gap-3"><Landmark className="text-primary" size={20} /><h2 className="font-display text-xl font-semibold">Filter entri</h2></div>
          <form onSubmit={submit} className="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
            <input value={accountId} onChange={e => setAccountId(e.target.value)} placeholder="Account ID" className="rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary" />
            <input value={transactionId} onChange={e => setTransactionId(e.target.value)} placeholder="Transaction ID" className="rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary" />
            <button type="submit" className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90"><Search size={16} /> Cari</button>
          </form>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[760px] text-left text-sm">
            <thead className="bg-secondary/50 text-xs uppercase text-muted-foreground"><tr><th className="p-4">Waktu</th><th className="p-4">Transaksi</th><th className="p-4">Akun</th><th className="p-4">Arah</th><th className="p-4 text-right">Nominal</th><th className="p-4 text-right">Saldo akhir</th></tr></thead>
            <tbody>
              {ledger.length === 0 ? (
                <tr><td colSpan={6} className="p-8 text-center text-muted-foreground">Belum ada entri ledger.</td></tr>
              ) : ledger.slice(0, 50).map(e => (
                <tr key={e.id} className="border-t border-border">
                  <td className="p-4 text-xs text-muted-foreground">{new Date(e.createdAt).toLocaleString("id-ID")}</td>
                  <td className="max-w-48 truncate p-4 font-mono text-xs" title={e.transactionId}>{e.transactionId}</td>
                  <td className="max-w-48 truncate p-4 font-mono text-xs" title={e.accountId}>{e.accountId}</td>
                  <td className="p-4"><span className="rounded-full bg-secondary px-2 py-1 font-mono text-xs">{e.direction}</span></td>
                  <td className="p-4 text-right font-mono tabular-nums">{money(e.amount)}</td>
                  <td className="p-4 text-right font-mono tabular-nums">{money(e.balanceAfter)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}
