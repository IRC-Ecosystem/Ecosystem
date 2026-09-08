"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { Check, ChevronDown, Receipt, Search, X } from "lucide-react";
import { fetchApi } from "@/lib/api";

type TransactionSummary = {
  id: string;
  transaction_type: string;
  status: string;
  gross_amount: string;
  payer_wallet_id: string | null;
  payee_wallet_id: string | null;
  created_at: string;
  settled_at: string | null;
};

type Props = {
  value: string;
  onChange: (id: string) => void;
  placeholder?: string;
  required?: boolean;
  className?: string;
  allowedTypes?: string[];
};

const money = (v: string | undefined) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(v || 0));

const shortId = (id: string) => `${id.slice(0, 8)}…${id.slice(-4)}`;

export default function TransactionPicker({
  value,
  onChange,
  placeholder = "Pilih transaksi…",
  required,
  className,
  allowedTypes,
}: Props) {
  const [transactions, setTransactions] = useState<TransactionSummary[]>([]);
  const [search, setSearch] = useState("");
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const containerRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    let cancelled = false;
    const load = async () => {
      setLoading(true);
      setError(null);
      try {
        const response = await fetchApi<{ data?: TransactionSummary[] } | TransactionSummary[]>(
          "/api/bank/central-bank/transactions?limit=200",
        );
        const list = Array.isArray(response) ? response : response.data ?? [];
        if (!cancelled) setTransactions(list);
      } catch (err) {
        if (!cancelled) setError(err instanceof Error ? err.message : "Gagal memuat daftar transaksi.");
      } finally {
        if (!cancelled) setLoading(false);
      }
    };
    void load();
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    const onClick = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", onClick);
    return () => document.removeEventListener("mousedown", onClick);
  }, []);

  const selected = useMemo(() => transactions.find((t) => t.id === value) ?? null, [transactions, value]);

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    return transactions
      .filter((t) => (allowedTypes ? allowedTypes.includes(t.transaction_type) : true))
      .filter((t) => (t.status === "SETTLED" || t.status === "REVERSED"))
      .filter((t) => {
        if (!term) return true;
        return t.id.toLowerCase().includes(term) || t.transaction_type.toLowerCase().includes(term);
      })
      .slice(0, 100);
  }, [transactions, search, allowedTypes]);

  return (
    <div ref={containerRef} className={`relative ${className ?? ""}`}>
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="flex w-full items-center justify-between gap-3 rounded-lg border border-border bg-background px-3 py-2 text-left text-sm outline-none focus:border-primary"
      >
        {selected ? (
          <span className="flex min-w-0 items-center gap-3">
            <Receipt size={16} className="shrink-0 text-primary" />
            <span className="flex min-w-0 flex-col">
              <span className="truncate font-mono text-xs text-muted-foreground">{shortId(selected.id)}</span>
              <span className="truncate text-sm">
                {selected.transaction_type} · {money(selected.gross_amount)} · {selected.status}
              </span>
            </span>
          </span>
        ) : (
          <span className="flex items-center gap-2 text-muted-foreground">
            <Receipt size={16} />
            {placeholder}
          </span>
        )}
        <span className="flex items-center gap-1 text-muted-foreground">
          {selected && (
            <span
              role="button"
              tabIndex={0}
              aria-label="Bersihkan pilihan"
              onClick={(e) => {
                e.stopPropagation();
                onChange("");
              }}
              className="rounded p-1 hover:bg-secondary"
            >
              <X size={14} />
            </span>
          )}
          <ChevronDown size={16} className={open ? "rotate-180 transition-transform" : "transition-transform"} />
        </span>
      </button>
      {required && !selected && <input type="text" required className="sr-only" value={value} readOnly />}

      {open && (
        <div className="absolute z-30 mt-1 w-full overflow-hidden rounded-lg border border-border bg-card shadow-xl">
          <div className="flex items-center gap-2 border-b border-border px-3 py-2">
            <Search size={14} className="text-muted-foreground" />
            <input
              autoFocus
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari ID atau tipe…"
              className="w-full bg-transparent text-sm outline-none"
            />
          </div>
          <ul className="max-h-72 overflow-y-auto py-1">
            {loading && <li className="px-3 py-4 text-center text-sm text-muted-foreground">Memuat…</li>}
            {error && !loading && (
              <li className="px-3 py-4 text-center text-sm text-destructive">{error}</li>
            )}
            {!loading && !error && filtered.length === 0 && (
              <li className="px-3 py-4 text-center text-sm text-muted-foreground">Transaksi tidak ditemukan.</li>
            )}
            {filtered.map((t) => {
              const isSelected = t.id === value;
              const ts = t.settled_at ?? t.created_at;
              return (
                <li key={t.id}>
                  <button
                    type="button"
                    onClick={() => {
                      onChange(t.id);
                      setOpen(false);
                      setSearch("");
                    }}
                    className={`flex w-full items-start gap-3 px-3 py-2 text-left text-sm transition-colors ${isSelected ? "bg-primary/10" : "hover:bg-secondary/60"}`}
                  >
                    <Receipt size={16} className="mt-1 shrink-0 text-primary" />
                    <span className="flex min-w-0 flex-1 flex-col">
                      <span className="truncate font-mono text-xs text-muted-foreground">{shortId(t.id)}</span>
                      <span className="truncate">
                        {t.transaction_type} · {money(t.gross_amount)} · {t.status}
                      </span>
                      <span className="truncate text-xs text-muted-foreground">{new Date(ts).toLocaleString("id-ID")}</span>
                    </span>
                    {isSelected && <Check size={16} className="mt-1 shrink-0 text-primary" />}
                  </button>
                </li>
              );
            })}
          </ul>
        </div>
      )}
    </div>
  );
}