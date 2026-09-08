"use client";

import { FormEvent, useState } from "react";
import { AlertTriangle, CheckCircle2, RotateCcw } from "lucide-react";
import { fetchApi } from "@/lib/api";
import TransactionPicker from "@/components/admin/TransactionPicker";

export default function AdminReversal() {
  const [reversalId, setReversalId] = useState("");
  const [reasonCode, setReasonCode] = useState("ADMIN_CORRECTION");
  const [note, setNote] = useState("");
  const [notice, setNotice] = useState<{ tone: "success" | "error"; text: string } | null>(null);
  const [processing, setProcessing] = useState(false);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    setProcessing(true);
    setNotice(null);
    try {
      await fetchApi("/api/bank/central-bank/reversals", {
        method: "POST",
        body: JSON.stringify({
          original_transaction_id: reversalId.trim(),
          reason_code: reasonCode.trim(),
          note: note.trim() || undefined,
        }),
      });
      setNotice({ tone: "success", text: "Reversal diterima dan tercatat pada ledger." });
      setReversalId("");
      setNote("");
    } catch (err) {
      setNotice({ tone: "error", text: err instanceof Error ? err.message : "Reversal gagal diproses." });
    } finally {
      setProcessing(false);
    }
  };

  return (
    <div className="space-y-8">
      <header>
        <p className="mb-2 font-mono text-xs font-semibold uppercase text-primary">Reversal</p>
        <h1 className="font-display text-3xl font-semibold">Reversal transaksi</h1>
        <p className="mt-2 max-w-2xl text-sm text-muted-foreground">Koreksi operasional yang sudah diverifikasi. Pilih transaksi yang akan dibalik dan catat alasan untuk jejak audit.</p>
      </header>

      {notice && (
        <div className={`flex items-start gap-3 rounded-xl border p-4 text-sm ${notice.tone === "success" ? "border-primary/30 bg-primary/10 text-primary" : "border-destructive/30 bg-destructive/10 text-destructive"}`}>
          {notice.tone === "success" ? <CheckCircle2 size={18} /> : <AlertTriangle size={18} />}
          {notice.text}
        </div>
      )}

      <form onSubmit={submit} className="max-w-2xl space-y-4 rounded-2xl border border-border bg-card p-6 shadow-sm">
        <div className="flex items-center gap-3">
          <RotateCcw className="text-primary" size={20} />
          <h2 className="font-display text-xl font-semibold">Form reversal</h2>
        </div>
        <div className="space-y-2">
          <label className="text-xs font-semibold uppercase text-muted-foreground">Transaksi asal</label>
          <TransactionPicker
            value={reversalId}
            onChange={setReversalId}
            required
            placeholder="Pilih transaksi SETTLED yang akan di-reverse…"
            allowedTypes={[
              "TOP_UP",
              "WITHDRAWAL",
              "TRANSFER",
              "PAYMENT",
              "LOAN_DISBURSEMENT",
              "LOAN_REPAYMENT",
              "INITIAL_DISTRIBUTION",
              "ISSUANCE",
              "BURN",
            ]}
          />
        </div>
        <div className="grid gap-3 md:grid-cols-2">
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
          <div className="space-y-2">
            <label className="text-xs font-semibold uppercase text-muted-foreground">Catatan (opsional)</label>
            <input
              value={note}
              onChange={(e) => setNote(e.target.value)}
              placeholder="Catatan opsional"
              className="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm outline-none focus:border-primary"
            />
          </div>
        </div>
        <button
          disabled={processing}
          className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
        >
          <RotateCcw size={16} /> {processing ? "Memproses..." : "Proses reversal"}
        </button>
      </form>
    </div>
  );
}