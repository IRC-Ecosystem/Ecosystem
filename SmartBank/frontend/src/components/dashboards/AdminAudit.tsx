"use client";

import { FormEvent, Fragment, useCallback, useEffect, useState } from "react";
import { ClipboardList, RefreshCw, Search, ChevronDown, ChevronRight } from "lucide-react";
import { fetchApi } from "@/lib/api";

type AuditLog = {
  id: string;
  actorUserId: string | null;
  serviceName: string;
  action: string;
  targetType: string;
  targetId: string;
  requestId: string;
  reasonCode: string | null;
  metadata: unknown;
  createdAt: string;
};

const unwrap = <T,>(r: { data?: T } | T): T =>
  typeof r === "object" && r !== null && "data" in r ? (r as { data: T }).data : (r as T);

export default function AdminAudit() {
  const [logs, setLogs] = useState<AuditLog[]>([]);
  const [total, setTotal] = useState(0);
  const [search, setSearch] = useState("");
  const [serviceName, setServiceName] = useState("");
  const [loading, setLoading] = useState(true);
  const [expanded, setExpanded] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const p = new URLSearchParams();
      if (search.trim()) p.set("search", search.trim());
      if (serviceName.trim()) p.set("service_name", serviceName.trim());
      const r = await fetchApi<{ data?: { logs: AuditLog[]; total: number } } | { logs: AuditLog[]; total: number }>(
        `/api/bank/central-bank/audit-logs${p.size ? `?${p}` : ""}`,
      );
      const d = unwrap(r);
      setLogs(d.logs ?? []);
      setTotal(d.total ?? 0);
    } finally {
      setLoading(false);
    }
  }, [search, serviceName]);

  useEffect(() => { void load(); }, [load]);

  const submit = (e: FormEvent) => { e.preventDefault(); void load(); };

  return (
    <div className="space-y-8">
      <header className="flex items-end justify-between">
        <div>
          <p className="mb-2 font-mono text-xs font-semibold uppercase text-primary">Audit log</p>
          <h1 className="font-display text-3xl font-semibold">Daftar audit terpusat</h1>
          <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
            Riwayat lengkap seluruh aksi terverifikasi — issuance, burn, reversal, settlement, dan perubahan konfigurasi.
          </p>
        </div>
        <button onClick={() => void load()} disabled={loading} className="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-4 py-2 text-sm font-semibold shadow-sm hover:bg-secondary disabled:opacity-50">
          <RefreshCw size={16} className={loading ? "animate-spin" : ""} /> Segarkan
        </button>
      </header>

      <section className="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
        <div className="border-b border-border p-5">
          <div className="flex items-center gap-3">
            <ClipboardList className="text-primary" size={20} />
            <h2 className="font-display text-xl font-semibold">Filter entri</h2>
            {total > 0 && <span className="ml-auto rounded-full bg-secondary px-3 py-1 font-mono text-xs">{total} entri</span>}
          </div>
          <form onSubmit={submit} className="mt-4 grid gap-3 md:grid-cols-[1fr_1fr_auto]">
            <input
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              placeholder="Cari aktor, aksi, target, request ID…"
              className="rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary"
            />
            <select
              value={serviceName}
              onChange={(e) => setServiceName(e.target.value)}
              className="rounded-lg border border-border bg-background px-3 py-2 text-sm font-mono outline-none focus:border-primary"
            >
              <option value="">Semua service</option>
              <option value="CENTRAL-BANK">CENTRAL-BANK</option>
              <option value="SETTLEMENT">SETTLEMENT</option>
              <option value="MONEY">MONEY</option>
              <option value="FEES">FEES</option>
              <option value="IDEMPOTENCY">IDEMPOTENCY</option>
            </select>
            <button type="submit" className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
              <Search size={16} /> Cari
            </button>
          </form>
        </div>
        <div className="overflow-x-auto">
          <table className="w-full min-w-[800px] text-left text-sm">
            <thead className="bg-secondary/50 text-xs uppercase text-muted-foreground">
              <tr>
                <th className="w-8 p-4"></th>
                <th className="p-4">Waktu</th>
                <th className="p-4">Aktor</th>
                <th className="p-4">Service</th>
                <th className="p-4">Aksi</th>
                <th className="p-4">Target</th>
                <th className="p-4">Reason</th>
              </tr>
            </thead>
            <tbody>
              {logs.length === 0 ? (
                <tr><td colSpan={7} className="p-8 text-center text-muted-foreground">Tidak ada entri audit.</td></tr>
              ) : logs.slice(0, 200).map((log) => {
                const isOpen = expanded === log.id;
                return (
                  <Fragment key={log.id}>
                    <tr
                      className="cursor-pointer border-t border-border hover:bg-secondary/30"
                      onClick={() => setExpanded(isOpen ? null : log.id)}
                    >
                      <td className="p-4 text-muted-foreground">
                        {isOpen ? <ChevronDown size={14} /> : <ChevronRight size={14} />}
                      </td>
                      <td className="p-4 text-xs text-muted-foreground">{new Date(log.createdAt).toLocaleString("id-ID")}</td>
                      <td className="max-w-32 truncate p-4 font-mono text-xs" title={log.actorUserId ?? "system"}>{log.actorUserId?.slice(0, 8) ?? "system"}</td>
                      <td className="p-4"><span className="rounded-full bg-secondary px-2 py-1 font-mono text-xs">{log.serviceName}</span></td>
                      <td className="p-4 font-mono text-xs font-semibold">{log.action}</td>
                      <td className="p-4 text-xs">
                        <span className="text-muted-foreground">{log.targetType}</span>
                        {log.targetId && <span className="ml-1 font-mono" title={log.targetId}>{log.targetId.length > 12 ? log.targetId.slice(0, 12) + "…" : log.targetId}</span>}
                      </td>
                      <td className="max-w-32 truncate p-4 text-xs text-muted-foreground" title={log.reasonCode ?? ""}>{log.reasonCode ?? "—"}</td>
                    </tr>
                    {isOpen && (
                      <tr className="border-t border-border bg-secondary/20">
                        <td colSpan={7} className="p-4">
                          <div className="space-y-2 text-xs">
                            <p><span className="font-semibold text-muted-foreground">Request ID:</span> <span className="font-mono">{log.requestId}</span></p>
                            <p><span className="font-semibold text-muted-foreground">Target ID:</span> <span className="font-mono">{log.targetId}</span></p>
                            {log.actorUserId && <p><span className="font-semibold text-muted-foreground">Actor:</span> <span className="font-mono">{log.actorUserId}</span></p>}
                            {log.metadata !== null && log.metadata !== undefined && (
                              <details className="mt-2">
                                <summary className="cursor-pointer font-semibold text-muted-foreground">Metadata</summary>
                                <pre className="mt-1 max-h-48 overflow-auto rounded-lg bg-background p-3 font-mono text-xs">{JSON.stringify(log.metadata, null, 2)}</pre>
                              </details>
                            )}
                          </div>
                        </td>
                      </tr>
                    )}
                  </Fragment>
                );
              })}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}
