"use client";

import { FormEvent, useState } from "react";
import { AlertTriangle, BadgeDollarSign, CheckCircle2 } from "lucide-react";
import { fetchApi } from "@/lib/api";
import WalletPicker from "@/components/admin/WalletPicker";

export default function AdminIssuance() {
  const [targetWalletId, setTargetWalletId] = useState("");
  const [amount, setAmount] = useState("");
  const [reasonCode, setReasonCode] = useState("MONETARY_EXPANSION");
  const [note, setNote] = useState("");
  const [notice, setNotice] = useState<{ tone: "success" | "error"; text: string } | null>(null);
  const [processing, setProcessing] = useState(false);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setNotice(null);

    const numericAmount = Number(amount);
    if (!targetWalletId) {
      setNotice({ tone: "error", text: "Pilih wallet target terlebih dahulu." });
      return;
    }
    if (!Number.isFinite(numericAmount) || numericAmount <= 0) {
      setNotice({ tone: "error", text: "Nominal harus angka lebih dari 0." });
      return;
    }

    setProcessing(true);
    try {
      await fetchApi("/api/bank/central-bank/issuance", {
        method: "POST",
        body: JSON.stringify({
          target_wallet_id: targetWalletId,
          amount: String(Math.trunc(numericAmount)),
          reason_code: reasonCode.trim(),
          note: note.trim() || undefined,
        }),
      });
      setNotice({ tone: "success", text: "Issuance berhasil dicatat pada ledger dan supply bertambah." });
      setTargetWalletId("");
      setAmount("");
      setNote("");
    } catch (err) {
      setNotice({ tone: "error", text: err instanceof Error ? err.message : "Issuance gagal diproses." });
    } finally {
      setProcessing(false);
    }
  };

  return (
    <div className="space-y-8">
      <header>
        <p className="mb-2 font-mono text-xs font-semibold uppercase text-primary">Issuance</p>
        <h1 className="font-display text-3xl font-semibold">Cetak CBDC baru</h1>
        <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
          Transfer saldo dari <span className="font-mono">CENTRAL_RESERVE</span> ke wallet target. Total supply akan bertambah dan invariant
          <span className="font-mono"> reserve + circulating + sink</span> tetap terjaga.
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
          <BadgeDollarSign className="text-primary" size={20} />
          <h2 className="font-display text-xl font-semibold">Form issuance</h2>
        </div>
        <div className="space-y-2">
          <label className="text-xs font-semibold uppercase text-muted-foreground">Wallet target</label>
          <WalletPicker
            value={targetWalletId}
            onChange={setTargetWalletId}
            required
            placeholder="Pilih wallet penerima issuance…"
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
              placeholder="Nominal issuance (IDR)"
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
          <BadgeDollarSign size={16} /> {processing ? "Memproses..." : "Proses issuance"}
        </button>
      </form>
    </div>
  );
}