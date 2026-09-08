"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { Check, ChevronDown, Search, Wallet as WalletIcon, X } from "lucide-react";
import { fetchApi } from "@/lib/api";

type WalletSummary = {
  id: string;
  account_type: string;
  account_code: string | null;
  available_balance: string;
  status: string;
  owner: { id: string; name: string | null; email: string | null; role: string } | null;
  created_at: string;
};

type Props = {
  value: string;
  onChange: (id: string) => void;
  accountTypeFilter?: "USER_WALLET" | "MERCHANT_WALLET";
  placeholder?: string;
  required?: boolean;
  className?: string;
};

const money = (v: string | undefined) =>
  new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", maximumFractionDigits: 0 }).format(Number(v || 0));

const shortId = (id: string) => `${id.slice(0, 8)}…${id.slice(-4)}`;

export default function WalletPicker({
  value,
  onChange,
  accountTypeFilter,
  placeholder = "Pilih wallet…",
  required,
  className,
}: Props) {
  const [wallets, setWallets] = useState<WalletSummary[]>([]);
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
        const params = new URLSearchParams();
        if (accountTypeFilter) params.set("account_type", accountTypeFilter);
        const qs = params.toString();
        const response = await fetchApi<{ data?: WalletSummary[] } | WalletSummary[]>(
          `/api/bank/central-bank/wallets${qs ? `?${qs}` : ""}`,
        );
        const list = Array.isArray(response) ? response : response.data ?? [];
        if (!cancelled) setWallets(list);
      } catch (err) {
        if (!cancelled) setError(err instanceof Error ? err.message : "Gagal memuat daftar wallet.");
      } finally {
        if (!cancelled) setLoading(false);
      }
    };
    void load();
    return () => {
      cancelled = true;
    };
  }, [accountTypeFilter]);

  useEffect(() => {
    const onClick = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener("mousedown", onClick);
    return () => document.removeEventListener("mousedown", onClick);
  }, []);

  const selected = useMemo(() => wallets.find((w) => w.id === value) ?? null, [wallets, value]);

  const filtered = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return wallets;
    return wallets.filter((w) => {
      const ownerName = w.owner?.name?.toLowerCase() ?? "";
      const ownerEmail = w.owner?.email?.toLowerCase() ?? "";
      return (
        w.id.toLowerCase().includes(term) ||
        (w.account_code ?? "").toLowerCase().includes(term) ||
        ownerName.includes(term) ||
        ownerEmail.includes(term)
      );
    });
  }, [wallets, search]);

  return (
    <div ref={containerRef} className={`relative ${className ?? ""}`}>
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="flex w-full items-center justify-between gap-3 rounded-lg border border-border bg-background px-3 py-2 text-left text-sm outline-none focus:border-primary"
      >
        {selected ? (
          <span className="flex min-w-0 items-center gap-3">
            <WalletIcon size={16} className="shrink-0 text-primary" />
            <span className="flex min-w-0 flex-col">
              <span className="truncate font-medium text-foreground">
                {selected.owner?.name ?? selected.owner?.email ?? shortId(selected.id)}
              </span>
              <span className="truncate font-mono text-xs text-muted-foreground">
                {selected.account_code ?? shortId(selected.id)} · {selected.account_type} · {money(selected.available_balance)}
              </span>
            </span>
          </span>
        ) : (
          <span className="flex items-center gap-2 text-muted-foreground">
            <WalletIcon size={16} />
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
              placeholder="Cari nama, email, atau ID…"
              className="w-full bg-transparent text-sm outline-none"
            />
          </div>
          <ul className="max-h-72 overflow-y-auto py-1">
            {loading && <li className="px-3 py-4 text-center text-sm text-muted-foreground">Memuat…</li>}
            {error && !loading && (
              <li className="px-3 py-4 text-center text-sm text-destructive">{error}</li>
            )}
            {!loading && !error && filtered.length === 0 && (
              <li className="px-3 py-4 text-center text-sm text-muted-foreground">Wallet tidak ditemukan.</li>
            )}
            {filtered.map((w) => {
              const isSelected = w.id === value;
              return (
                <li key={w.id}>
                  <button
                    type="button"
                    onClick={() => {
                      onChange(w.id);
                      setOpen(false);
                      setSearch("");
                    }}
                    className={`flex w-full items-start gap-3 px-3 py-2 text-left text-sm transition-colors ${isSelected ? "bg-primary/10" : "hover:bg-secondary/60"}`}
                  >
                    <WalletIcon size={16} className="mt-1 shrink-0 text-primary" />
                    <span className="flex min-w-0 flex-1 flex-col">
                      <span className="truncate font-medium">
                        {w.owner?.name ?? w.owner?.email ?? shortId(w.id)}
                      </span>
                      <span className="truncate font-mono text-xs text-muted-foreground">
                        {w.account_code ?? shortId(w.id)} · {w.account_type} · {money(w.available_balance)}
                      </span>
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