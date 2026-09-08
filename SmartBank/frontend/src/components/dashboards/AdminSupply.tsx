"use client";

import { useEffect, useState, useCallback } from "react";
import { AlertTriangle, CheckCircle2, RefreshCw, ShieldCheck } from "lucide-react";
import { fetchApi } from "@/lib/api";

type Supply = {
  total_supply: string | number;
  reserve_balance: string | number;
  circulating_supply: string | number;
  sink_or_burn_accounting: string | number;
  invariant_total: string | number;
  invariant_valid: boolean;
};

const money = (v: string | number | undefined) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(v || 0));

const unwrap = <T,>(r: { data?: T } | T): T =>
  typeof r === "object" && r !== null && "data" in r ? (r as { data: T }).data : (r as T);

export default function AdminSupply() {
  const [supply, setSupply] = useState<Supply | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    setLoading(true);
    try {
      const r = await fetchApi<{ data?: Supply } | Supply>("/api/bank/central-bank/supply");
      setSupply(unwrap(r));
    } finally { setLoading(false); }
  }, []);

  useEffect(() => { void refresh(); }, [refresh]);

  return (
    <div className="space-y-8">
      <header className="flex items-end justify-between">
        <div>
          <p className="mb-2 font-mono text-xs font-semibold uppercase text-primary">Central bank operations</p>
          <h1 className="font-display text-3xl font-semibold">Kendali moneter</h1>
        </div>
        <button onClick={() => void refresh()} disabled={loading} className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2 text-sm font-semibold shadow-sm hover:bg-secondary disabled:opacity-50">
          <RefreshCw size={16} className={loading ? "animate-spin" : ""} /> Segarkan
        </button>
      </header>

      <section className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        {[
          ["Total supply", supply?.total_supply],
          ["Circulating supply", supply?.circulating_supply],
          ["Reserve balance", supply?.reserve_balance],
          ["Sink / burn accounting", supply?.sink_or_burn_accounting],
        ].map(([label, value], i) => (
          <article key={String(label)} className={`rounded-2xl border p-5 shadow-sm ${i === 0 ? "border-primary/30 bg-primary text-primary-foreground md:col-span-2" : "border-border bg-card"}`}>
            <p className={`text-xs font-semibold uppercase ${i === 0 ? "text-primary-foreground/70" : "text-muted-foreground"}`}>{label}</p>
            <p className="mt-4 font-mono text-2xl font-semibold tabular-nums">{loading ? "Memuat..." : money(value)}</p>
          </article>
        ))}
        <article className="rounded-2xl border border-border bg-card p-5 shadow-sm md:col-span-2">
          <div className="flex items-center gap-3">
            <div className="rounded-xl bg-primary/10 p-2 text-primary"><ShieldCheck size={20} /></div>
            <div>
              <p className="text-sm font-semibold">Invariant ledger</p>
              <p className="text-xs text-muted-foreground">Total: <span className="font-mono tabular-nums">{money(supply?.invariant_total)}</span></p>
            </div>
            <span className={`ml-auto rounded-full px-3 py-1 text-xs font-semibold ${supply?.invariant_valid ? "bg-primary/10 text-primary" : "bg-destructive/10 text-destructive"}`}>
              {supply?.invariant_valid ? "VALID" : "PERLU INVESTIGASI"}
            </span>
          </div>
        </article>
      </section>

      <aside className="rounded-2xl border border-border bg-card p-6 shadow-sm">
        <h2 className="font-display text-lg font-semibold">Cakupan API</h2>
        <div className="mt-4 space-y-3 text-sm">
          {["Monitoring supply", "Pencarian ledger", "Reversal transaksi", "Issuance manual", "Burn manual", "Pengaturan fee", "Daftar audit terpusat"].map(i => <div key={i} className="flex items-center gap-3"><CheckCircle2 size={17} className="text-primary" /><span>{i}</span></div>)}
        </div>
      </aside>
    </div>
  );
}
