"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { AlertTriangle, CheckCircle2, Receipt, RefreshCw, Save } from "lucide-react";
import { fetchApi } from "@/lib/api";

type FeeConfig = {
  id: string;
  type: string;
  mode: string;
  value: string | number;
  minFee?: string | number | null;
  maxFee?: string | number | null;
  isActive: boolean;
  updatedBy?: string | null;
  createdAt: string;
  updatedAt: string;
};

const money = (v: string | number | undefined | null) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(v || 0));

const unwrap = <T,>(r: { data?: T } | T): T =>
  typeof r === "object" && r !== null && "data" in r ? (r as { data: T }).data : (r as T);

const TX_TYPES = ["TOP_UP", "WITHDRAWAL", "TRANSFER", "PAYMENT"] as const;

export default function AdminFee() {
  const [fees, setFees] = useState<FeeConfig[]>([]);
  const [loading, setLoading] = useState(true);
  const [notice, setNotice] = useState<{ tone: "success" | "error"; text: string } | null>(null);
  const [processing, setProcessing] = useState(false);

  const [type, setType] = useState<string>(TX_TYPES[0]);
  const [mode, setMode] = useState<"FLAT" | "PERCENT">("FLAT");
  const [value, setValue] = useState("");
  const [minFee, setMinFee] = useState("");
  const [maxFee, setMaxFee] = useState("");
  const [isActive, setIsActive] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const r = await fetchApi<{ data?: FeeConfig[] } | FeeConfig[]>("/api/bank/central-bank/fees");
      setFees(unwrap(r) ?? []);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void load(); }, [load]);

  const editConfig = (cfg: FeeConfig) => {
    setType(cfg.type);
    setMode(cfg.mode as "FLAT" | "PERCENT");
    setValue(String(cfg.value));
    setMinFee(cfg.minFee ? String(cfg.minFee) : "");
    setMaxFee(cfg.maxFee ? String(cfg.maxFee) : "");
    setIsActive(cfg.isActive);
    setNotice(null);
    window.scrollTo({ top: document.body.scrollHeight, behavior: "smooth" });
  };

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setNotice(null);

    const numValue = Number(value);
    if (!Number.isFinite(numValue) || numValue < 0) {
      setNotice({ tone: "error", text: "Value harus angka non-negatif." });
      return;
    }

    setProcessing(true);
    try {
      await fetchApi("/api/bank/central-bank/fees", {
        method: "PUT",
        body: JSON.stringify({
          type,
          mode,
          value: String(Math.trunc(numValue)),
          min_fee: minFee ? String(Math.trunc(Number(minFee))) : undefined,
          max_fee: maxFee ? String(Math.trunc(Number(maxFee))) : undefined,
          is_active: isActive,
        }),
      });
      setNotice({ tone: "success", text: `Fee ${type} berhasil disimpan.` });
      setValue("");
      setMinFee("");
      setMaxFee("");
      void load();
    } catch (err) {
      setNotice({ tone: "error", text: err instanceof Error ? err.message : "Gagal menyimpan fee config." });
    } finally {
      setProcessing(false);
    }
  };

  return (
    <div className="space-y-8">
      <header className="flex items-end justify-between">
        <div>
          <p className="mb-2 font-mono text-xs font-semibold uppercase text-primary">Fee configuration</p>
          <h1 className="font-display text-3xl font-semibold">Pengaturan fee transaksi</h1>
          <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
            Atur biaya flat (IDR) atau persentase (basis points, 1 bps = 0.01%) untuk setiap jenis transaksi yang dikenai fee.
          </p>
        </div>
        <button onClick={() => void load()} disabled={loading} className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2 text-sm font-semibold shadow-sm hover:bg-secondary disabled:opacity-50">
          <RefreshCw size={16} className={loading ? "animate-spin" : ""} /> Segarkan
        </button>
      </header>

      {notice && (
        <div className={`flex items-start gap-3 rounded-xl border p-4 text-sm ${notice.tone === "success" ? "border-primary/30 bg-primary/10 text-primary" : "border-destructive/30 bg-destructive/10 text-destructive"}`}>
          {notice.tone === "success" ? <CheckCircle2 size={18} /> : <AlertTriangle size={18} />}
          {notice.text}
        </div>
      )}

      <section className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
        <div className="border-b border-border p-5">
          <div className="flex items-center gap-3">
            <Receipt className="text-primary" size={20} />
            <h2 className="font-display text-xl font-semibold">Fee aktif</h2>
          </div>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[700px] text-left text-sm">
            <thead className="bg-secondary/50 text-xs uppercase text-muted-foreground">
              <tr>
                <th className="p-4">Jenis</th>
                <th className="p-4">Mode</th>
                <th className="p-4 text-right">Value</th>
                <th className="p-4 text-right">Min fee</th>
                <th className="p-4 text-right">Max fee</th>
                <th className="p-4 text-center">Status</th>
                <th className="p-4">Aksi</th>
              </tr>
            </thead>
            <tbody>
              {fees.length === 0 ? (
                <tr><td colSpan={7} className="p-8 text-center text-muted-foreground">Belum ada fee config. Buat lewat form di bawah.</td></tr>
              ) : fees.map(cfg => (
                <tr key={cfg.id} className="border-t border-border">
                  <td className="p-4 font-mono text-xs font-semibold">{cfg.type}</td>
                  <td className="p-4">
                    <span className="rounded-full bg-secondary px-2 py-1 font-mono text-xs">{cfg.mode}</span>
                  </td>
                  <td className="p-4 text-right font-mono tabular-nums">
                    {cfg.mode === "FLAT" ? money(cfg.value) : `${Number(cfg.value) / 100}%`}
                  </td>
                  <td className="p-4 text-right font-mono tabular-nums text-muted-foreground">
                    {cfg.minFee ? money(cfg.minFee) : "—"}
                  </td>
                  <td className="p-4 text-right font-mono tabular-nums text-muted-foreground">
                    {cfg.maxFee ? money(cfg.maxFee) : "—"}
                  </td>
                  <td className="p-4 text-center">
                    <span className={`rounded-full px-2 py-1 text-xs font-semibold ${cfg.isActive ? "bg-primary/10 text-primary" : "bg-destructive/10 text-destructive"}`}>
                      {cfg.isActive ? "AKTIF" : "NONAKTIF"}
                    </span>
                  </td>
                  <td className="p-4">
                    <button onClick={() => editConfig(cfg)} className="rounded-lg border border-border px-3 py-1.5 text-xs font-semibold hover:bg-secondary">
                      Edit
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>

      <form onSubmit={submit} className="max-w-2xl space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
        <div className="flex items-center gap-3">
          <Receipt className="text-primary" size={20} />
          <h2 className="font-display text-xl font-semibold">Form fee</h2>
        </div>
        <div className="grid gap-3 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-xs font-semibold uppercase text-muted-foreground">Jenis transaksi</label>
            <select
              value={type}
              onChange={(e) => setType(e.target.value)}
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary"
            >
              {TX_TYPES.map(t => <option key={t} value={t}>{t}</option>)}
            </select>
          </div>
          <div className="space-y-2">
            <label className="text-xs font-semibold uppercase text-muted-foreground">Mode</label>
            <div className="flex gap-4 rounded-lg border border-border bg-background px-3 py-2">
              <label className="flex cursor-pointer items-center gap-2 text-sm">
                <input type="radio" name="mode" value="FLAT" checked={mode === "FLAT"} onChange={() => setMode("FLAT")} className="accent-primary" />
                FLAT (IDR)
              </label>
              <label className="flex cursor-pointer items-center gap-2 text-sm">
                <input type="radio" name="mode" value="PERCENT" checked={mode === "PERCENT"} onChange={() => setMode("PERCENT")} className="accent-primary" />
                PERCENT (bps)
              </label>
            </div>
          </div>
        </div>
        <div className="space-y-2">
          <label className="text-xs font-semibold uppercase text-muted-foreground">
            Value {mode === "FLAT" ? "(nominal IDR)" : "(basis points, 100 = 1%)"}
          </label>
          <input
            required
            type="number"
            min={0}
            value={value}
            onChange={(e) => setValue(e.target.value)}
            placeholder={mode === "FLAT" ? "Contoh: 5000" : "Contoh: 100 (= 1%)"}
            className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary"
          />
        </div>
        {mode === "PERCENT" && (
          <div className="grid gap-3 md:grid-cols-2">
            <div className="space-y-2">
              <label className="text-xs font-semibold uppercase text-muted-foreground">Min fee (IDR, opsional)</label>
              <input
                type="number"
                min={0}
                value={minFee}
                onChange={(e) => setMinFee(e.target.value)}
                placeholder="Minimum fee"
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary"
              />
            </div>
            <div className="space-y-2">
              <label className="text-xs font-semibold uppercase text-muted-foreground">Max fee (IDR, opsional)</label>
              <input
                type="number"
                min={0}
                value={maxFee}
                onChange={(e) => setMaxFee(e.target.value)}
                placeholder="Maksimum fee"
                className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary"
              />
            </div>
          </div>
        )}
        <label className="flex cursor-pointer items-center gap-2 text-sm">
          <input type="checkbox" checked={isActive} onChange={(e) => setIsActive(e.target.checked)} className="accent-primary" />
          Aktif
        </label>
        <button
          disabled={processing}
          className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
        >
          <Save size={16} /> {processing ? "Menyimpan..." : "Simpan fee"}
        </button>
      </form>
    </div>
  );
}
