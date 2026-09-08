"use client";

import { motion } from "framer-motion";
import { Building2, ShieldCheck, UserCog, Users } from "lucide-react";

const roles = [
  {
    icon: Users,
    role: "Nasabah (Retail / Merchant)",
    gradient: "from-blue-500 to-cyan-500",
    rights: [
      "Cek saldo & mutasi sendiri",
      "Transfer P2P & bayar QR/invoice",
      "Ajukan & cicil pinjaman UMKM",
      "Top up & tarik tunai melalui Teller",
    ],
    limit: "Tidak boleh lihat data nasabah lain atau ubah status akun sendiri.",
  },
  {
    icon: UserCog,
    role: "Teller (Layanan Cabang)",
    gradient: "from-emerald-500 to-teal-500",
    rights: [
      "Registrasi nasabah baru",
      "Verifikasi KYC (BASIC → VERIFIED)",
      "Top up / withdrawal nasabah",
      "Rekomendasi pinjaman ≤ 50k",
    ],
    limit: "Tidak boleh approve pinjaman atau freeze akun sepihak.",
  },
  {
    icon: ShieldCheck,
    role: "Manager (Pimpinan Cabang)",
    gradient: "from-cyan-500 to-blue-600",
    rights: [
      "Seluruh hak akses Teller",
      "Approve / reject pinjaman",
      "Suspend akun nasabah sementara",
      "Set limit harian khusus merchant",
    ],
    limit: "Tidak boleh mint/burn uang atau ubah tarif pajak global.",
  },
  {
    icon: Building2,
    role: "Admin Central Bank",
    gradient: "from-blue-700 to-indigo-700",
    rights: [
      "Issuance & Burn CBDC",
      "Audit global ledger",
      "Reversal transaksi ACID",
      "Atur tarif fee & pajak",
    ],
    limit: "Akses tertinggi, hanya untuk admin infrastruktur bank sentral.",
  },
];

export default function RolesCards() {
  return (
    <section className="relative px-6 py-24 overflow-hidden">
      <div className="max-w-7xl mx-auto">
        <motion.div
          initial={{ opacity: 0, y: 16 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-80px" }}
          transition={{ duration: 0.6 }}
          className="text-center max-w-2xl mx-auto mb-14 space-y-3"
        >
          <p className="text-xs font-mono text-primary uppercase tracking-widest">
            Peran & Otoritas
          </p>
          <h2 className="text-3xl md:text-5xl font-display font-semibold text-foreground leading-[1.1] tracking-tight">
            Empat peran,{" "}
            <span className="bg-gradient-to-br from-blue-500 to-cyan-500 bg-clip-text text-transparent">
              satu matriks akses
            </span>
          </h2>
          <p className="text-sm text-muted-foreground max-w-[55ch] mx-auto leading-relaxed">
            Pemisahan wewenang tegas: User tidak bisa Introspeksi sistem, Admin tidak bypass audit.
          </p>
        </motion.div>

        {/* 4-column grid — equal but with 3D tilt + escalating glow */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 [perspective:2000px]">
          {roles.map((r, i) => (
            <motion.div
              key={r.role}
              initial={{ opacity: 0, y: 16 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, margin: "-60px" }}
              transition={{ delay: i * 0.08, duration: 0.6, ease: [0.16, 1, 0.3, 1] }}
              className="group relative"
            >
              <div className="relative h-full rounded-2xl border border-white/10 bg-gradient-to-br from-zinc-950 via-neutral-950 to-black backdrop-blur-xl p-5 shadow-[0_15px_40px_-15px_rgba(0,0,0,0.85),inset_0_1px_0_rgba(255,255,255,0.12),inset_0_-1px_0_rgba(255,255,255,0.04)] transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_30px_70px_-15px_rgba(0,0,0,0.95),inset_0_1px_0_rgba(255,255,255,0.18)] overflow-hidden">
                {/* Gloss highlight — top half lighter for shine */}
                <div className="absolute inset-x-0 top-0 h-1/2 bg-gradient-to-b from-white/[0.05] to-transparent pointer-events-none" />

                {/* Specular edge — sharp white hairline on top */}
                <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/30 to-transparent" />

                {/* Bottom rim */}
                <div className="absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent pointer-events-none" />

                {/* Role number watermark */}
                <div className="absolute top-2 right-3 text-[60px] leading-none font-display font-bold text-foreground/[0.04] dark:text-foreground/[0.06] pointer-events-none tabular-nums">
                  {String(i + 1).padStart(2, "0")}
                </div>

                {/* Corner glow on hover */}
                <div
                  className={`absolute -top-16 -right-16 w-40 h-40 rounded-full bg-gradient-to-br ${r.gradient} opacity-0 group-hover:opacity-25 blur-3xl transition-opacity duration-500`}
                />

                <div className="relative z-10">
                  <div className="flex items-center justify-between mb-3">
                    <div className={`relative p-2.5 rounded-xl bg-gradient-to-br ${r.gradient} text-white shadow-lg`}>
                      <r.icon className="w-5 h-5" />
                      <div className="absolute inset-0 rounded-xl bg-gradient-to-t from-transparent to-white/20 pointer-events-none" />
                    </div>
                    <span className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground">
                      L{i + 1}
                    </span>
                  </div>

                  <h3 className="text-base font-display font-semibold text-foreground mb-3 leading-tight">
                    {r.role}
                  </h3>

                  <ul className="space-y-1.5 mb-4">
                    {r.rights.map((rw) => (
                      <li
                        key={rw}
                        className="flex items-start gap-2 text-xs text-foreground/80"
                      >
                        <span className="w-1 h-1 rounded-full bg-foreground/40 mt-1.5 shrink-0" />
                        {rw}
                      </li>
                    ))}
                  </ul>

                  <p className="text-[10px] text-muted-foreground italic border-t border-white/10 pt-3 leading-relaxed">
                    {r.limit}
                  </p>
                </div>
              </div>
            </motion.div>
          ))}
        </div>

        {/* Hierarchy line */}
        <motion.div
          initial={{ opacity: 0, scaleX: 0 }}
          whileInView={{ opacity: 1, scaleX: 1 }}
          viewport={{ once: true }}
          transition={{ delay: 0.5, duration: 0.8 }}
          className="mt-10 hidden lg:flex items-center justify-center gap-3 text-[10px] font-mono uppercase tracking-widest text-muted-foreground"
        >
          <span>L1 → L2 → L3 → L4</span>
          <span className="h-px w-32 bg-gradient-to-r from-blue-500/40 via-cyan-500/60 to-indigo-500/40" />
          <span>Escalating authority</span>
        </motion.div>
      </div>
    </section>
  );
}
