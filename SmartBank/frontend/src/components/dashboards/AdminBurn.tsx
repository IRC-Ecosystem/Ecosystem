"use client";

import { FormEvent, useState } from "react";
import { AlertTriangle, CheckCircle2, Flame } from "lucide-react";
import { fetchApi } from "@/lib/api";
import WalletPicker from "@/components/admin/WalletPicker";

export default function AdminBurn() {
  const [sourceWalletId, setSourceWalletId] = useState("");
  const [amount, setAmount] = useState("");
  const [reasonCode, setReasonCode] = useState("MONETARY_CONTRACTION");
  const [note, setNote] = useState("");
  const [notice, setNotice] = useState<{ tone: "success" | "error"; text: string } | null>(null);
  const [processing, setProcessing] = useState(false);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setNotice(null);

    const numericAmount = Number(amount);
    if (!sourceWalletId) {
      setNotice({ tone: "error", text: "Pilih wallet sumber terlebih dahulu." });
      return;
    }
    if (!Number.isFinite(numericAmount) || numericAmount <= 0) {
      setNotice({ tone: "error", text: "Nominal harus angka lebih dari 0." });
      return;
    }

    setProcessing(true);
    try {
      await fetchApi("/api/bank/central-bank/burn", {
        method: "POST",
        body: JSON.stringify({
          source_wallet_id: sourceWalletId,
          amount: String(Math.trunc(numericAmount)),
          reason_code: reasonCode.trim(),
          note: note.trim() || undefined,
        }),
      });
      setNotice({ tone: "success", text: "Burn berhasil dicatat pada ledger dan dipindahkan ke sink account." });
      setSourceWalletId("");
      setAmount("");
      setNote("");
    } catch (err) {
      setNotice({ tone: "error", text: err instanceof Error ? err.message : "Burn gagal diproses." });
    } finally {
      setProcessing(false);
    }
  };

  return (
    <div className="space-y-8">
      <header>
        <p className="mb-2 font-mono text-xs font-semibold uppercase text-primary">Burn</p>
        <h1 className="font-display text-3xl font-semibold">Musnahkan CBDC dari wallet</h1>
        <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
          Tarik saldo dari wallet sumber ke <span className="font-mono">BURN_OR_SINK_ACCOUNT</span>. Hanya berlaku untuk wallet USER_WALLET atau
          MERCHANT_WALLET. Total supply akan berkurang.
        </p>
      </header>

      {notice && (
        <div className={`flex items-start gap-3 rounded-xl border p-4 text-sm ${notice.tone === "success" ? "border-primary/30 bg-primary/10 text-primary" : "border-destructive/30 bg-destructive/10 text-destructive"}`}>
          {notice.tone === "success" ? <CheckCircle2 size={18} /> : <AlertTriangle size={18} />}
          {notice.text}
        </div>
      )}

      <form onSubmit={submit} className="max-w-2xl space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
        <div className="flex items-center gap-3">
          <Flame className="text-primary" size={20} />
          <h2 className="font-display text-xl font-semibold">Form burn</h2>
        </div>
        <div className="space-y-2">
          <label className="text-xs font-semibold uppercase text-muted-foreground">Wallet sumber</label>
          <WalletPicker
            value={sourceWalletId}
            onChange={setSourceWalletId}
            required
            placeholder="Pilih wallet yang akan di-burn…"
          />
        </div>
        <div className="grid gap-3 md:grid-cols-2">
          <div className="space-y-2">
            <label className="text-xs font-semibold uppercase text-muted-foreground">Nominal</label>
            <input
              required
              type="number"
              min={1}
              value={amount}
              onChange={(e) => setAmount(e.target.value)}
              placeholder="Nominal burn (IDR)"
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary"
            />
          </div>
          <div className="space-y-2">
            <label className="text-xs font-semibold uppercase text-muted-foreground">Reason code</label>
            <input
              required
              value={reasonCode}
              onChange={(e) => setReasonCode(e.target.value)}
              placeholder="Reason code"
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary"
            />
          </div>
        </div>
        <div className="space-y-2">
          <label className="text-xs font-semibold uppercase text-muted-foreground">Catatan (opsional)</label>
          <input
            value={note}
            onChange={(e) => setNote(e.target.value)}
            placeholder="Catatan opsional"
            className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-primary"
          />
        </div>
        <button
          disabled={processing}
          className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
        >
          <Flame size={16} /> {processing ? "Memproses..." : "Proses burn"}
        </button>
      </form>
    </div>
  );
}