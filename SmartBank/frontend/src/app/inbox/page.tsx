"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { Bell, Check, CheckCheck, KeyRound, ReceiptText, ShieldCheck } from "lucide-react";
import AppShell from "@/components/AppShell";
import RolePage from "@/components/RolePage";
import {
  listNotifications,
  markAllNotificationsRead,
  markNotificationRead,
  SmartBankNotification,
} from "@/lib/notifications";

type Filter = "ALL" | "OTP" | "PAYMENT" | "UNREAD";

function secondsUntil(value: unknown) {
  if (typeof value !== "string") return 0;
  return Math.max(0, Math.floor((new Date(value).getTime() - Date.now()) / 1000));
}

function countdown(value: unknown) {
  const seconds = secondsUntil(value);
  return `${String(Math.floor(seconds / 60)).padStart(2, "0")}:${String(seconds % 60).padStart(2, "0")}`;
}

function iconFor(type: string) {
  if (type.includes("OTP")) return <KeyRound className="size-5 text-blue-500" />;
  if (type.includes("PAYMENT")) return <ReceiptText className="size-5 text-emerald-500" />;
  if (type.includes("LINKED")) return <ShieldCheck className="size-5 text-violet-500" />;
  return <Bell className="size-5 text-muted-foreground" />;
}

function InboxContent() {
  const [items, setItems] = useState<SmartBankNotification[]>([]);
  const [filter, setFilter] = useState<Filter>("ALL");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [, setClock] = useState(0);

  const refresh = useCallback(async () => {
    try {
      const result = await listNotifications();
      setItems(result.items);
      setError("");
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Inbox gagal dimuat.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    const initial = window.setTimeout(() => void refresh(), 0);
    const polling = window.setInterval(() => void refresh(), 10_000);
    return () => {
      window.clearTimeout(initial);
      window.clearInterval(polling);
    };
  }, [refresh]);

  useEffect(() => {
    const timer = window.setInterval(() => setClock((value) => value + 1), 1_000);
    return () => window.clearInterval(timer);
  }, []);

  const visible = useMemo(() => items.filter((item) => {
    if (filter === "OTP") return item.type.includes("OTP");
    if (filter === "PAYMENT") return item.type.includes("PAYMENT");
    if (filter === "UNREAD") return !item.read_at;
    return true;
  }), [filter, items]);

  const unread = items.filter((item) => !item.read_at).length;

  const markRead = async (item: SmartBankNotification) => {
    if (item.read_at) return;
    const readAt = new Date().toISOString();
    setItems((current) => current.map((entry) => entry.id === item.id ? { ...entry, read_at: readAt } : entry));
    try {
      await markNotificationRead(item.id);
    } catch {
      await refresh();
    }
  };

  const markAll = async () => {
    const readAt = new Date().toISOString();
    setItems((current) => current.map((entry) => ({ ...entry, read_at: entry.read_at ?? readAt })));
    try {
      await markAllNotificationsRead();
    } catch {
      await refresh();
    }
  };

  return (
    <section className="space-y-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p className="font-mono text-xs uppercase tracking-[0.25em] text-primary">SmartBank Inbox</p>
          <h1 className="mt-2 text-3xl font-bold tracking-tight">Notifikasi dan kode OTP</h1>
          <p className="mt-2 text-sm text-muted-foreground">Diperbarui otomatis setiap 10 detik. OTP hanya ditampilkan di akun SmartBank Anda.</p>
        </div>
        <button
          type="button"
          onClick={() => void markAll()}
          disabled={unread === 0}
          className="inline-flex items-center justify-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold transition hover:bg-secondary disabled:cursor-not-allowed disabled:opacity-50"
        >
          <CheckCheck className="size-4" /> Tandai semua dibaca
        </button>
      </div>

      <div className="flex flex-wrap gap-2">
        {(["ALL", "OTP", "PAYMENT", "UNREAD"] as Filter[]).map((value) => (
          <button
            type="button"
            key={value}
            onClick={() => setFilter(value)}
            className={`rounded-full border px-4 py-2 text-xs font-semibold transition ${filter === value ? "border-primary bg-primary text-primary-foreground" : "border-border bg-card hover:bg-secondary"}`}
          >
            {value === "ALL" ? "Semua" : value === "PAYMENT" ? "Pembayaran" : value === "UNREAD" ? `Belum dibaca (${unread})` : "OTP"}
          </button>
        ))}
      </div>

      {error && <div className="rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">{error}</div>}
      {loading ? (
        <div className="rounded-2xl border border-border bg-card p-10 text-center text-sm text-muted-foreground">Memuat inbox…</div>
      ) : visible.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-border bg-card/50 p-12 text-center">
          <Bell className="mx-auto size-10 text-muted-foreground/50" />
          <p className="mt-3 font-semibold">Belum ada notifikasi</p>
        </div>
      ) : (
        <div className="space-y-3">
          {visible.map((item) => {
            const otp = item.payload?.otp_code;
            const expiresAt = item.payload?.expires_at;
            const expired = Boolean(expiresAt) && secondsUntil(expiresAt) === 0;
            return (
              <button
                type="button"
                key={item.id}
                onClick={() => void markRead(item)}
                className={`w-full rounded-2xl border p-5 text-left transition hover:-translate-y-0.5 hover:shadow-md ${item.read_at ? "border-border bg-card" : "border-blue-500/30 bg-blue-500/5 shadow-sm shadow-blue-500/10"}`}
              >
                <div className="flex gap-4">
                  <div className="flex size-11 shrink-0 items-center justify-center rounded-xl border border-border bg-background">{iconFor(item.type)}</div>
                  <div className="min-w-0 flex-1">
                    <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                      <h2 className="font-semibold">{item.title}</h2>
                      <span className="text-xs text-muted-foreground">{new Date(item.created_at).toLocaleString("id-ID")}</span>
                    </div>
                    <p className="mt-1 text-sm leading-6 text-muted-foreground">{item.body}</p>
                    {typeof otp === "string" && (
                      <div className={`mt-4 rounded-xl border p-4 text-center ${expired ? "border-destructive/30 bg-destructive/5" : "border-primary/30 bg-primary/5"}`}>
                        <p className="text-xs uppercase tracking-widest text-muted-foreground">Kode OTP</p>
                        <p className="mt-2 font-mono text-3xl font-bold tracking-[0.35em]">{otp}</p>
                        <p className={`mt-2 text-xs font-semibold ${expired ? "text-destructive" : "text-primary"}`}>
                          {expired ? "Kode sudah kedaluwarsa" : `Berlaku ${countdown(expiresAt)} lagi`}
                        </p>
                      </div>
                    )}
                    <div className="mt-3 flex items-center gap-2 text-xs text-muted-foreground">
                      {item.read_at ? <Check className="size-3.5" /> : <span className="size-2 rounded-full bg-blue-500" />}
                      {item.read_at ? "Sudah dibaca" : "Klik untuk menandai dibaca"}
                      {item.source_app && <span>• {item.source_app}</span>}
                    </div>
                  </div>
                </div>
              </button>
            );
          })}
        </div>
      )}
    </section>
  );
}

export default function InboxPage() {
  return (
    <AppShell>
      <RolePage allowed={["WALLET_USER", "RETAIL", "RETAIL_CUSTOMER", "TELLER", "MANAGER", "ADMIN", "CENTRAL_BANK_ADMIN"]}>
        <InboxContent />
      </RolePage>
    </AppShell>
  );
}
