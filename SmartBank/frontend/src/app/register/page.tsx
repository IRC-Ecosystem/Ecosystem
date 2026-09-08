"use client";

import { useMemo, useState } from "react";
import { motion } from "framer-motion";
import {
  ArrowRight,
  KeyRound,
  Loader2,
  Mail,
  Phone,
  ShieldCheck,
  Sparkles,
  User as UserIcon,
} from "lucide-react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { type User, useAuthStore } from "@/store/auth";
import { fetchApi } from "@/lib/api";
import { ThemeToggle } from "@/components/theme-toggle";
import { AuthInput } from "@/components/auth/AuthInput";
import AuthBrandPanel from "@/components/auth/AuthBrandPanel";
import { MeshBackground } from "@/components/landing/MeshBackground";

type LoginResponse = {
  data: {
    accessToken: string;
    user: User;
  };
};

type StrengthTone = "muted" | "destructive" | "amber" | "emerald";

function pinStrength(pin: string): { level: 0 | 1 | 2 | 3; label: string; tone: StrengthTone } {
  if (!pin) return { level: 0, label: "Kosong", tone: "muted" };
  if (pin.length < 6) return { level: 1, label: "Terlalu pendek", tone: "destructive" };

  const unique = new Set(pin.split("")).size;
  if (unique === 1) return { level: 1, label: "Sangat lemah", tone: "destructive" };
  if (unique === 2) return { level: 2, label: "Lemah", tone: "amber" };

  // Sequential ascending/descending
  const isSeq = pin.split("").every((d, i, arr) => {
    if (i === 0) return true;
    return Number(d) === Number(arr[i - 1]) + 1 || Number(d) === Number(arr[i - 1]) - 1;
  });
  if (isSeq) return { level: 2, label: "Lemah (berurutan)", tone: "amber" };

  return { level: 3, label: "Kuat", tone: "emerald" };
}

const STRENGTH_BAR_COLORS: Record<StrengthTone, string> = {
  muted: "bg-slate-300 dark:bg-slate-700",
  destructive: "bg-destructive",
  amber: "bg-amber-500",
  emerald: "bg-emerald-500",
};

const STRENGTH_TEXT_COLORS: Record<StrengthTone, string> = {
  muted: "text-muted-foreground",
  destructive: "text-destructive",
  amber: "text-amber-600 dark:text-amber-400",
  emerald: "text-emerald-600 dark:text-emerald-400",
};

const container = {
  hidden: { opacity: 0 },
  show: {
    opacity: 1,
    transition: { staggerChildren: 0.08, delayChildren: 0.1 },
  },
};

const item = {
  hidden: { opacity: 0, y: 16 },
  show: { opacity: 1, y: 0, transition: { duration: 0.6, ease: [0.16, 1, 0.3, 1] as const } },
};

export default function RegisterPage() {
  const router = useRouter();
  const setAuth = useAuthStore((state) => state.setAuth);

  const [formData, setFormData] = useState({
    name: "",
    email: "",
    phone: "",
    password: "",
    pin: "",
  });

  const [agreeTerms, setAgreeTerms] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState("");

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
  };

  const strength = useMemo(() => pinStrength(formData.pin), [formData.pin]);

  const handleRegister = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!agreeTerms) {
      setError("Anda harus menyetujui syarat & ketentuan untuk melanjutkan.");
      return;
    }
    setIsLoading(true);
    setError("");

    try {
      await fetchApi("/api/wallet/v1/auth/register", {
        method: "POST",
        body: JSON.stringify(formData),
      });

      const loginRes = await fetchApi<LoginResponse>("/api/wallet/v1/auth/login", {
        method: "POST",
        body: JSON.stringify({ email: formData.email, password: formData.password }),
      });

      const token = loginRes.data.accessToken;
      const user = loginRes.data.user;

      if (!token || !user) {
        throw new Error("Invalid response from server during auto-login");
      }

      setAuth(token, user);
      router.push("/dashboard");
    } catch (err) {
      let errorMsg =
        err instanceof Error ? err.message : "Registrasi gagal. Periksa kembali data Anda.";
      if (
        errorMsg.includes("Idempotency-Key dipakai") ||
        errorMsg.includes("Email sudah terdaftar") ||
        errorMsg.includes("Email sudah digunakan")
      ) {
        errorMsg = "Email ini sudah terdaftar. Silakan gunakan email lain atau langsung Login.";
      }
      setError(errorMsg);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <main className="relative h-dvh overflow-hidden grid lg:grid-cols-2 bg-background [perspective:1500px]">
      {/* Page-level MeshBackground (mobile only — desktop has brand panel) */}
      <div className="absolute inset-0 z-0 lg:hidden">
        <MeshBackground />
      </div>

      {/* Left brand panel -- 50/50 cols */}
      <div className="hidden lg:block min-h-0 relative z-10">
        <AuthBrandPanel />
      </div>

      {/* Right form panel -- 50/50 cols (form gets half width) */}
      <div className="relative z-10 flex items-center justify-center p-6 md:p-8 lg:p-10 min-h-0 overflow-y-auto">
        <div className="absolute inset-0 -z-10 hidden lg:block">
          <MeshBackground />
        </div>

        <div className="absolute top-4 right-4 z-30 hidden">
          <ThemeToggle />
        </div>

        {/* Mobile brand mark */}
        <div className="lg:hidden absolute top-4 left-4 flex items-center gap-2">
          <div className="size-7 rounded-md bg-primary/10 border border-primary/20 flex items-center justify-center text-primary">
            <Sparkles className="w-3.5 h-3.5" />
          </div>
          <span className="font-display font-semibold text-sm text-foreground">SmartBank</span>
        </div>

        {/* Glassmorphic form container */}
        <motion.div
          className="relative w-full max-w-lg mt-12 lg:mt-0"
          variants={container}
          initial="hidden"
          animate="show"
        >
          {/* Inner refraction line */}
          <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/60 dark:via-white/15 to-transparent rounded-t-3xl pointer-events-none z-10" />

          <div className="relative rounded-3xl border border-slate-200 dark:border-white/10 bg-white/85 dark:bg-slate-900/60 backdrop-blur-2xl shadow-[0_30px_80px_-20px_rgba(37,99,235,0.25),inset_0_1px_0_rgba(255,255,255,0.6)] dark:shadow-[0_30px_80px_-20px_rgba(0,0,0,0.5)] p-8 md:p-10">
            {/* Header (compact) */}
            <motion.div variants={item} className="space-y-2 mb-6">
              <p className="text-[10px] font-mono text-primary uppercase tracking-widest">
                Daftar
              </p>
              <h1 className="text-2xl lg:text-3xl font-display font-semibold text-foreground leading-tight tracking-tight">
                Buat Identitas
              </h1>
              <p className="text-xs text-muted-foreground leading-relaxed">
                5 field · langsung aktif · PIN terpisah dari password untuk keamanan ekstra.
              </p>
            </motion.div>

            {error && (
              <motion.div
                initial={{ opacity: 0, height: 0 }}
                animate={{ opacity: 1, height: "auto" }}
                className="bg-destructive/10 border border-destructive/30 text-destructive text-xs p-2.5 rounded-lg flex items-start gap-2 mb-3"
              >
                <span className="w-1.5 h-1.5 rounded-full bg-destructive shrink-0 mt-1.5" />
                <span>{error}</span>
              </motion.div>
            )}

            {/* Form (2-col grid for compactness) */}
            <motion.form
              variants={item}
              onSubmit={handleRegister}
              className="space-y-3"
            >
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <AuthInput
                  label="Nama Lengkap"
                  icon={UserIcon}
                  name="name"
                  value={formData.name}
                  onChange={handleChange}
                  placeholder="Nama sesuai KTP"
                  autoComplete="name"
                  required
                />
                <AuthInput
                  label="Email"
                  icon={Mail}
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  placeholder="anda@email.com"
                  autoComplete="email"
                  required
                />
              </div>

              {/* Phone with +62 prefix */}
              <div className="space-y-1.5">
                <div className="flex items-center justify-between gap-2">
                  <label className="text-xs font-medium text-foreground">Nomor Telepon</label>
                  <span className="text-[10px] text-muted-foreground font-mono">
                    Format otomatis +62
                  </span>
                </div>
                <div className="relative group">
                  {/* Inner refraction line */}
                  <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/50 dark:via-white/10 to-transparent rounded-t-lg pointer-events-none z-10" />
                  <Phone className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors pointer-events-none z-10" />
                  <span className="absolute left-10 top-1/2 -translate-y-1/2 text-sm font-mono text-muted-foreground pointer-events-none select-none z-10">
                    +62
                  </span>
                  <input
                    type="tel"
                    name="phone"
                    value={formData.phone}
                    onChange={handleChange}
                    placeholder="8123456789"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    autoComplete="tel-national"
                    required
                    className="w-full bg-secondary/40 border border-border focus:border-primary rounded-lg py-2.5 pl-[68px] pr-4 outline-none transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] text-sm font-mono text-foreground placeholder:text-muted-foreground/50 focus:bg-secondary/70 focus:shadow-[0_0_0_3px_rgba(37,99,235,0.12)]"
                  />
                </div>
              </div>

              {/* Password + PIN row */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <AuthInput
                  label="Password"
                  icon={KeyRound}
                  type="password"
                  name="password"
                  value={formData.password}
                  onChange={handleChange}
                  placeholder="Min. 6 char"
                  autoComplete="new-password"
                  required
                />

                {/* PIN with strength bar */}
                <div className="space-y-1.5">
                  <AuthInput
                    label="PIN 6-digit"
                    icon={KeyRound}
                    type="password"
                    name="pin"
                    maxLength={6}
                    value={formData.pin}
                    onChange={handleChange}
                    placeholder="123456"
                    inputMode="numeric"
                    pattern="[0-9]*"
                    autoComplete="off"
                    required
                  />
                  {formData.pin && (
                    <div className="flex items-center gap-2 pt-0.5">
                      <div className="flex items-center gap-1 flex-1">
                        {[1, 2, 3].map((bar) => (
                          <div
                            key={bar}
                            className={`h-1 flex-1 rounded-full transition-colors duration-300 ${
                              bar <= strength.level
                                ? STRENGTH_BAR_COLORS[strength.tone]
                                : "bg-slate-200 dark:bg-slate-800"
                            }`}
                          />
                        ))}
                      </div>
                      <span
                        className={`text-[10px] font-mono font-semibold ${STRENGTH_TEXT_COLORS[strength.tone]}`}
                      >
                        {strength.label}
                      </span>
                    </div>
                  )}
                </div>
              </div>

              {/* Terms checkbox (custom styled) */}
              <label className="flex items-start gap-2.5 cursor-pointer pt-1">
                <div className="relative shrink-0 mt-0.5">
                  <input
                    type="checkbox"
                    checked={agreeTerms}
                    onChange={(e) => setAgreeTerms(e.target.checked)}
                    className="peer sr-only"
                  />
                  <div
                    className={`size-4 rounded border transition-all duration-200 flex items-center justify-center ${
                      agreeTerms
                        ? "bg-primary border-primary"
                        : "bg-secondary/40 border-border hover:border-primary/50"
                    } peer-focus-visible:ring-2 peer-focus-visible:ring-primary/40`}
                  >
                    {agreeTerms && (
                      <svg
                        className="size-3 text-primary-foreground"
                        viewBox="0 0 12 12"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      >
                        <path d="M2.5 6 L5 8.5 L9.5 3.5" />
                      </svg>
                    )}
                  </div>
                </div>
                <span className="text-[11px] text-muted-foreground leading-relaxed">
                  Saya menyetujui{" "}
                  <a
                    href="/guide"
                    target="_blank"
                    rel="noreferrer"
                    className="text-primary font-medium hover:underline"
                  >
                    Syarat & Ketentuan
                  </a>{" "}
                  serta{" "}
                  <a
                    href="/guide"
                    target="_blank"
                    rel="noreferrer"
                    className="text-primary font-medium hover:underline"
                  >
                    Kebijakan Privasi
                  </a>{" "}
                  SmartBank.
                </span>
              </label>

              {/* CTA — landing pattern */}
              <button
                type="submit"
                disabled={isLoading || !agreeTerms}
                className="group relative w-full flex items-center justify-center gap-2 overflow-hidden rounded-xl bg-primary text-primary-foreground py-3 font-semibold transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50 disabled:pointer-events-none mt-2 shadow-[0_10px_30px_-10px_rgba(37,99,235,0.5),inset_0_1px_0_rgba(255,255,255,0.15)] hover:shadow-[0_15px_40px_-10px_rgba(37,99,235,0.7),inset_0_1px_0_rgba(255,255,255,0.2)]"
              >
                <span className="relative z-10 flex items-center gap-2">
                  {isLoading ? (
                    <>
                      <Loader2 className="w-4 h-4 animate-spin" />
                      Mendaftarkan...
                    </>
                  ) : (
                    <>
                      <ShieldCheck className="w-4 h-4" />
                      Daftar &amp; Masuk
                    </>
                  )}
                </span>
                {!isLoading && (
                  <span className="absolute inset-0 -translate-x-full group-hover:translate-x-full transition-transform duration-700 bg-gradient-to-r from-transparent via-white/20 to-transparent" />
                )}
              </button>
            </motion.form>

            <motion.div
              variants={item}
              className="text-center text-xs text-muted-foreground pt-4 mt-4 border-t border-border/40"
            >
              Sudah punya akun?{" "}
              <Link
                href="/login"
                className="text-primary font-medium hover:underline inline-flex items-center gap-1 group"
              >
                Login di sini
                <ArrowRight className="w-3 h-3 group-hover:translate-x-0.5 transition-transform" />
              </Link>
            </motion.div>
          </div>
        </motion.div>
      </div>
    </main>
  );
}
