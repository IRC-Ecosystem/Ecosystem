"use client";

import { motion } from "framer-motion";
import { Calculator, Percent } from "lucide-react";

const rows = [
  { source: "Bank", app: "CentralBank Core", bps: 100, flat: "—", note: "Otoritas moneter" },
  { source: "Gateway", app: "API Gateway", bps: 50, flat: "—", note: "Integrasi lintas service" },
  { source: "Marketplace", app: "PasarKita", bps: 200, flat: "—", note: "Fee marketplace opsional" },
  { source: "POS", app: "WarungPOS", bps: 100, flat: "—", note: "Fee toko fisik" },
  { source: "Supplier", app: "SupplierHub", bps: 300, flat: "—", note: "Fee supplier" },
  { source: "Logistik", app: "LogistiKita", bps: 250, flat: "—", note: "Fee ongkir" },
  { source: "Pajak", app: "Tax Sink", bps: 200, flat: "—", note: "Pajak transaksi" },
];

const exampleAmount = 100_000;
const totalBps = rows.reduce((s, r) => s + r.bps, 0);
const exampleFee = Math.round((exampleAmount * totalBps) / 10_000);
const exampleTotal = exampleAmount + exampleFee;

export default function FeeTable() {
  return (
    <section className="relative px-6 py-24 overflow-hidden">
      <div className="max-w-7xl mx-auto">
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-80px" }}
          transition={{ duration: 0.6 }}
          className="max-w-2xl mb-14 space-y-3"
        >
          <p className="text-xs font-mono text-primary uppercase tracking-widest">
            Struktur Biaya
          </p>
          <h2 className="text-3xl md:text-5xl font-display font-semibold text-foreground leading-[1.1] tracking-tight">
            Transparan, dihitung{" "}
            <span className="bg-gradient-to-br from-blue-500 to-cyan-500 bg-clip-text text-transparent">
              otomatis
            </span>{" "}
            saat konfirmasi
          </h2>
          <p className="text-sm text-muted-foreground max-w-[55ch] leading-relaxed">
            Fee menggunakan basis points (1% = 100 bps). Total potongan ditampilkan sebelum PIN.
          </p>
        </motion.div>

        <div className="grid lg:grid-cols-[1.4fr_1fr] gap-5 [perspective:2000px]">
          {/* Fee table — card */}
          <motion.div
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-80px" }}
            transition={{ duration: 0.6 }}
            className="relative"
          >
            <div className="relative rounded-3xl border border-slate-200 dark:border-border/60 bg-white dark:bg-card/60 backdrop-blur-xl overflow-hidden shadow-[0_20px_60px_-20px_rgba(2,6,23,0.1),inset_0_1px_0_rgba(255,255,255,0.6)]">
              <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 dark:via-white/10 to-transparent" />

              <div className="px-6 py-4 border-b border-border/40 flex items-center gap-2.5">
                <div className="p-1.5 rounded-lg bg-primary/10 text-primary">
                  <Percent className="w-4 h-4" />
                </div>
                <span className="text-sm font-display font-semibold text-foreground tracking-tight">
                  Tabel Fee per Sumber
                </span>
                <span className="ml-auto text-[10px] font-mono uppercase tracking-widest text-muted-foreground">
                  {rows.length} sumber
                </span>
              </div>
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground bg-secondary/30">
                      <th className="text-left px-5 py-3">Sumber</th>
                      <th className="text-left px-5 py-3">Aplikasi</th>
                      <th className="text-right px-5 py-3">bps</th>
                      <th className="text-right px-5 py-3">%</th>
                      <th className="text-left px-5 py-3">Keterangan</th>
                    </tr>
                  </thead>
                  <tbody>
                    {rows.map((r, i) => (
                      <motion.tr
                        key={r.source}
                        initial={{ opacity: 0, x: -8 }}
                        whileInView={{ opacity: 1, x: 0 }}
                        viewport={{ once: true }}
                        transition={{ delay: i * 0.04 }}
                        className="border-t border-border/30 hover:bg-primary/[0.03] transition-colors"
                      >
                        <td className="px-5 py-3 font-medium text-foreground">{r.source}</td>
                        <td className="px-5 py-3 text-muted-foreground font-mono text-xs">
                          {r.app}
                        </td>
                        <td className="px-5 py-3 text-right font-mono tabular-nums text-foreground">
                          {r.bps}
                        </td>
                        <td className="px-5 py-3 text-right font-mono tabular-nums text-foreground">
                          {(r.bps / 100).toFixed(2)}%
                        </td>
                        <td className="px-5 py-3 text-xs text-muted-foreground">{r.note}</td>
                      </motion.tr>
                    ))}
                    <tr className="bg-gradient-to-r from-primary/10 via-primary/5 to-transparent border-t-2 border-primary/30">
                      <td className="px-5 py-3 font-semibold text-foreground" colSpan={2}>
                        Total kumulatif (semua sumber aktif)
                      </td>
                      <td className="px-5 py-3 text-right font-mono font-bold tabular-nums text-primary">
                        {totalBps}
                      </td>
                      <td className="px-5 py-3 text-right font-mono font-bold tabular-nums text-primary">
                        {(totalBps / 100).toFixed(2)}%
                      </td>
                      <td className="px-5 py-3 text-xs text-muted-foreground">Maks debit</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </motion.div>

          {/* Fee calculator */}
          <motion.div
            initial={{ opacity: 0, y: 16 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-80px" }}
            transition={{ duration: 0.6, delay: 0.1 }}
            className="relative"
          >
            <div className="relative h-full rounded-3xl border border-slate-200 dark:border-border/60 bg-white dark:bg-card/60 backdrop-blur-xl p-6 shadow-[0_20px_60px_-20px_rgba(2,6,23,0.1),inset_0_1px_0_rgba(255,255,255,0.6)]">
              <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 dark:via-white/10 to-transparent" />

              {/* Corner glow */}
              <div className="absolute -top-12 -right-12 w-40 h-40 rounded-full bg-emerald-500/15 blur-3xl" />

              <div className="relative z-10">
                <div className="flex items-center gap-2.5 mb-5">
                  <div className="p-1.5 rounded-lg bg-gradient-to-br from-emerald-500 to-cyan-600 text-white shadow-lg shadow-emerald-500/30">
                    <Calculator className="w-4 h-4" />
                  </div>
                  <span className="text-sm font-display font-semibold text-foreground tracking-tight">
                    Contoh Simulasi
                  </span>
                </div>
                <p className="text-xs text-muted-foreground mb-5 leading-relaxed">
                  Nominal transfer{" "}
                  <span className="text-foreground font-medium">Rp 100.000</span>:
                </p>
                <dl className="space-y-3 text-sm">
                  <div className="flex justify-between">
                    <dt className="text-muted-foreground">Nominal utama</dt>
                    <dd className="font-mono tabular-nums text-foreground">
                      Rp {exampleAmount.toLocaleString("id-ID")}
                    </dd>
                  </div>
                  <div className="flex justify-between">
                    <dt className="text-muted-foreground">Total fee</dt>
                    <dd className="font-mono tabular-nums text-foreground">
                      Rp {exampleFee.toLocaleString("id-ID")}
                    </dd>
                  </div>
                  <div className="flex justify-between border-t border-border/40 pt-3 mt-3">
                    <dt className="font-semibold text-foreground">Total debit payer</dt>
                    <dd className="font-mono font-bold tabular-nums text-primary">
                      Rp {exampleTotal.toLocaleString("id-ID")}
                    </dd>
                  </div>
                  <div className="flex justify-between">
                    <dt className="text-muted-foreground">Diterima penerima</dt>
                    <dd className="font-mono tabular-nums text-foreground">
                      Rp {exampleAmount.toLocaleString("id-ID")}
                    </dd>
                  </div>
                </dl>
                <p className="text-[10px] text-muted-foreground italic mt-5 leading-relaxed">
                  * Setiap sumber fee punya flag aktif/nonaktif di tabel fee_rules. Wallet
                  menghitung ulang saat konfirmasi transfer menggunakan endpoint{" "}
                  <code className="text-primary font-mono">GET /api/v1/fees/quote</code>.
                </p>
              </div>
            </div>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
