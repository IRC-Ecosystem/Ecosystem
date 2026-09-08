"use client";

import { ReactNode, useEffect, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import Link from "next/link";
import { AnimatePresence, motion } from "framer-motion";
import {
  BadgeDollarSign,
  Banknote,
  Bell,
  BookOpen,
  Building2,
  ChevronRight,
  ClipboardList,
  FileCheck2,
  Flame,
  Landmark,
  LayoutDashboard,
  LogOut,
  Menu,
  Receipt,
  ScrollText,
  Send,
  ShieldAlert,
  ShieldCheck,
  Sparkles,
  UserCircle2,
  Users,
  X,
} from "lucide-react";
import OnboardingTour from "@/components/OnboardingTour";
import { ThemeToggle } from "@/components/theme-toggle";
import { Role, useAuthStore } from "@/store/auth";
import { useIsClient } from "@/lib/use-is-client";
import NotificationBell from "@/components/NotificationBell";

type MenuItem = {
  name: string;
  href: string;
  icon: ReactNode;
  /** Optional badge label (e.g. count, "NEW", "LIVE") */
  badge?: string;
  /** Tone for badge styling */
  badgeTone?: "primary" | "emerald" | "amber";
  /** If true, show animated live dot */
  live?: boolean;
};

const menus: Record<Role, MenuItem[]> = {
  WALLET_USER: [
    { name: "Ringkasan Dompet", href: "/dashboard", icon: <LayoutDashboard size={19} /> },
    { name: "Inbox", href: "/inbox", icon: <Bell size={19} />, live: true },
    { name: "Transfer", href: "/transfer", icon: <Send size={19} /> },
    { name: "Verifikasi KYC", href: "/kyc", icon: <FileCheck2 size={19} /> },
    { name: "Pinjaman", href: "/pinjaman", icon: <BadgeDollarSign size={19} />, badge: "Rp", badgeTone: "primary" },
    { name: "Aktivitas", href: "/aktivitas", icon: <ScrollText size={19} /> },
  ],
  RETAIL: [
    { name: "Ringkasan Dompet", href: "/dashboard", icon: <LayoutDashboard size={19} /> },
    { name: "Inbox", href: "/inbox", icon: <Bell size={19} />, live: true },
    { name: "Transfer", href: "/transfer", icon: <Send size={19} /> },
    { name: "Verifikasi KYC", href: "/kyc", icon: <FileCheck2 size={19} /> },
    { name: "Pinjaman", href: "/pinjaman", icon: <BadgeDollarSign size={19} />, badge: "Rp", badgeTone: "primary" },
    { name: "Aktivitas", href: "/aktivitas", icon: <ScrollText size={19} /> },
  ],
  RETAIL_CUSTOMER: [
    { name: "Ringkasan Dompet", href: "/dashboard", icon: <LayoutDashboard size={19} /> },
    { name: "Inbox", href: "/inbox", icon: <Bell size={19} />, live: true },
    { name: "Transfer", href: "/transfer", icon: <Send size={19} /> },
    { name: "Verifikasi KYC", href: "/kyc", icon: <FileCheck2 size={19} /> },
    { name: "Pinjaman", href: "/pinjaman", icon: <BadgeDollarSign size={19} />, badge: "Rp", badgeTone: "primary" },
    { name: "Aktivitas", href: "/aktivitas", icon: <ScrollText size={19} /> },
  ],
  TELLER: [
    { name: "Pencarian Nasabah", href: "/teller/nasabah", icon: <UserCircle2 size={19} /> },
    { name: "Operasi Teller", href: "/teller/operasi", icon: <ShieldAlert size={19} /> },
  ],
  MANAGER: [
    { name: "Kontrol Risiko", href: "/manager/risiko", icon: <Users size={19} /> },
    { name: "Approval Pinjaman", href: "/manager/pinjaman", icon: <BadgeDollarSign size={19} /> },
  ],
  ADMIN: [
    { name: "Operasi Bank Sentral", href: "/admin", icon: <Landmark size={19} /> },
    { name: "Issuance", href: "/admin/issuance", icon: <Banknote size={19} />, live: true },
    { name: "Burn", href: "/admin/burn", icon: <Flame size={19} />, live: true },
    { name: "Reversal", href: "/admin/reversal", icon: <ShieldCheck size={19} /> },
    { name: "Fee Config", href: "/admin/fee", icon: <Receipt size={19} /> },
    { name: "Ledger", href: "/admin/ledger", icon: <ScrollText size={19} /> },
    { name: "Audit Log", href: "/admin/audit", icon: <ClipboardList size={19} />, live: true },
  ],
  CENTRAL_BANK_ADMIN: [
    { name: "Operasi Bank Sentral", href: "/admin", icon: <Landmark size={19} /> },
    { name: "Issuance", href: "/admin/issuance", icon: <Banknote size={19} />, live: true },
    { name: "Burn", href: "/admin/burn", icon: <Flame size={19} />, live: true },
    { name: "Reversal", href: "/admin/reversal", icon: <ShieldCheck size={19} /> },
    { name: "Fee Config", href: "/admin/fee", icon: <Receipt size={19} /> },
    { name: "Ledger", href: "/admin/ledger", icon: <ScrollText size={19} /> },
    { name: "Audit Log", href: "/admin/audit", icon: <ClipboardList size={19} />, live: true },
  ],
};

const badgeToneClasses: Record<NonNullable<MenuItem["badgeTone"]>, string> = {
  primary: "bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30",
  emerald: "bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30",
  amber: "bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-500/30",
};

export default function AppShell({ children }: { children: ReactNode }) {
  const router = useRouter();
  const pathname = usePathname();
  const { user, token, logout, hydrated, rehydrate } = useAuthStore();
  const isClient = useIsClient();
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  useEffect(() => {
    rehydrate();
  }, [rehydrate]);

  useEffect(() => {
    if (isClient && hydrated && (!token || !user)) router.push("/login");
  }, [isClient, hydrated, token, user, router]);

  // Sebelum hydrate dari localStorage, render placeholder identik di SSR &
  // client untuk menghindari hydration mismatch. Setelah hydrated, baru
  // tentukan apakah user null (belum login) atau tampilkan UI.
  if (!isClient || !hydrated) {
    return (
      <div className="flex min-h-dvh items-center justify-center bg-background text-sm text-muted-foreground">
        Memuat sesi…
      </div>
    );
  }

  if (!user) return null;

  const handleLogout = () => {
    logout();
    router.push("/");
  };

  const role = user.role;
  const isAdmin = role === "ADMIN" || role === "CENTRAL_BANK_ADMIN";

  return (
    <div className="flex min-h-dvh bg-background">
      {/* Mobile overlay */}
      <AnimatePresence>
        {isSidebarOpen && (
          <motion.button
            aria-label="Tutup menu"
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            onClick={() => setIsSidebarOpen(false)}
            className="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm md:hidden"
          />
        )}
      </AnimatePresence>

      {/* Sidebar */}
      <aside
        className={`fixed left-0 top-0 z-50 flex h-dvh w-64 flex-col border-r border-border bg-card transition-transform md:relative ${isSidebarOpen ? "translate-x-0" : "-translate-x-full md:translate-x-0"}`}
      >
        {/* Brand header with live role badge */}
        <div className="relative flex h-16 shrink-0 items-center justify-between border-b border-border/60 px-5">
          <Link href="/" className="flex items-center gap-2.5 group">
            <div className="relative size-8 rounded-xl bg-gradient-to-br from-primary to-blue-700 dark:from-blue-500 dark:to-blue-700 flex items-center justify-center text-primary-foreground shadow-md shadow-primary/25 transition-transform duration-300 group-hover:scale-105 group-hover:rotate-3">
              <Building2 className="w-4 h-4" />
              <div className="absolute inset-0 rounded-xl bg-gradient-to-t from-transparent to-white/20 pointer-events-none" />
            </div>
            <div>
              <p className="font-display font-bold text-foreground text-base leading-none tracking-tight">SmartBank</p>
              <p className="text-[9px] font-mono uppercase tracking-widest text-muted-foreground mt-0.5">CBDC · v1.0.0</p>
            </div>
          </Link>
          <button
            aria-label="Tutup menu"
            className="rounded-lg p-1.5 hover:bg-secondary md:hidden"
            onClick={() => setIsSidebarOpen(false)}
          >
            <X size={18} />
          </button>
        </div>

        {/* Active role section label */}
        <div className="px-5 pt-5 pb-2 shrink-0">
          <div className="flex items-center justify-between">
            <p className="text-[10px] font-mono uppercase tracking-widest text-muted-foreground">
              {role.replaceAll("_", " ")}
            </p>
            <span className="inline-flex items-center gap-1 text-[9px] font-mono uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
              <span className="relative flex size-1.5">
                <span className="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-75" />
                <span className="relative inline-flex size-1.5 rounded-full bg-emerald-500" />
              </span>
              Live
            </span>
          </div>
        </div>

        {/* Nav — Premium menu items */}
        <nav className="flex-1 overflow-y-auto px-3 pb-4" aria-label="Navigasi dashboard">
          <ul className="space-y-1">
            {menus[role].map((item, i) => {
              const isExactActive = pathname === item.href;
              return (
                <motion.li
                  key={item.href}
                  initial={{ opacity: 0, x: -8 }}
                  animate={{ opacity: 1, x: 0 }}
                  transition={{ delay: 0.05 + i * 0.04, duration: 0.3, ease: [0.16, 1, 0.3, 1] }}
                >
                  <Link
                    href={item.href}
                    onClick={() => setIsSidebarOpen(false)}
                    className={`group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 ${
                      isExactActive
                        ? "bg-gradient-to-r from-blue-500/15 via-blue-500/10 to-transparent text-blue-600 dark:text-blue-400 shadow-[inset_0_1px_0_rgba(59,130,246,0.2)]"
                        : "text-muted-foreground hover:bg-secondary/60 hover:text-foreground"
                    }`}
                  >
                    {/* Active indicator bar (left) */}
                    {isExactActive && (
                      <motion.span
                        layoutId="sidebar-active-indicator"
                        className="absolute -left-3 top-1/2 -translate-y-1/2 w-1 h-6 rounded-full bg-gradient-to-b from-blue-500 to-cyan-500"
                        transition={{ type: "spring", stiffness: 350, damping: 30 }}
                      />
                    )}

                    {/* Icon container with active glow */}
                    <span
                      className={`relative flex shrink-0 size-8 items-center justify-center rounded-lg transition-all duration-200 ${
                        isExactActive
                          ? "bg-gradient-to-br from-blue-500/20 to-cyan-500/10 text-blue-600 dark:text-blue-400 shadow-[0_0_12px_rgba(59,130,246,0.25)]"
                          : "group-hover:bg-secondary/80 group-hover:scale-105"
                      }`}
                    >
                      {item.icon}
                    </span>

                    <span className="flex-1 truncate">{item.name}</span>

                    {/* Badge or live dot */}
                    {item.badge && (
                      <span
                        className={`inline-flex items-center rounded-md border px-1.5 py-0.5 font-mono text-[9px] uppercase tracking-widest ${
                          badgeToneClasses[item.badgeTone ?? "primary"]
                        }`}
                      >
                        {item.badge}
                      </span>
                    )}
                    {item.live && (
                      <span className="relative flex size-1.5 shrink-0">
                        <span className="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-75" />
                        <span className="relative inline-flex size-1.5 rounded-full bg-emerald-500" />
                      </span>
                    )}
                    {!isExactActive && (
                      <ChevronRight className="size-3.5 text-muted-foreground/40 -translate-x-1 opacity-0 group-hover:translate-x-0 group-hover:opacity-100 transition-all" />
                    )}
                  </Link>
                </motion.li>
              );
            })}
          </ul>

          {/* Help section */}
          <div className="mt-6 pt-5 border-t border-border/40">
            <p className="px-3 mb-2 text-[10px] font-mono uppercase tracking-widest text-muted-foreground">
              Bantuan
            </p>
            <Link
              href="/guide"
              className="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-muted-foreground hover:bg-secondary/60 hover:text-foreground transition-colors"
            >
              <span className="flex size-8 items-center justify-center rounded-lg group-hover:bg-primary/10 group-hover:text-primary transition-colors">
                <BookOpen size={17} />
              </span>
              <span className="flex-1">Panduan Pengguna</span>
              <ChevronRight className="size-3.5 opacity-40 group-hover:opacity-100 group-hover:translate-x-0.5 transition-all" />
            </Link>
          </div>
        </nav>

        {/* Footer: User card + Logout */}
        <div className="border-t border-border/60 p-3 shrink-0 space-y-2">
          {/* User identity card */}
          <div className="flex items-center gap-3 px-2 py-2 rounded-xl bg-secondary/30 hover:bg-secondary/50 transition-colors">
            <div className="relative shrink-0">
              <div className="size-9 rounded-full bg-gradient-to-br from-primary to-blue-700 flex items-center justify-center text-primary-foreground font-display font-bold text-sm shadow-md shadow-primary/30">
                {(user.name || user.role).charAt(0).toUpperCase()}
              </div>
              <span className="absolute -bottom-0.5 -right-0.5 size-3 rounded-full bg-emerald-500 border-2 border-card" />
            </div>
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-semibold text-foreground">{user.name || user.email || user.phone || "Pengguna"}</p>
              <p className="truncate text-[10px] font-mono uppercase tracking-widest text-muted-foreground mt-0.5">
                {role.replaceAll("_", " ")}
              </p>
            </div>
          </div>
          <button
            onClick={handleLogout}
            className="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-destructive hover:bg-destructive/10 transition-colors"
          >
            <span className="flex size-8 items-center justify-center rounded-lg group-hover:bg-destructive/15 transition-colors">
              <LogOut size={17} />
            </span>
            <span className="flex-1 text-left">Keluar Aman</span>
          </button>
        </div>
      </aside>

      {/* Main content area */}
      <div className="relative flex h-dvh flex-1 flex-col overflow-hidden">
        <OnboardingTour />

        {/* Top bar */}
        <header className="flex h-16 shrink-0 items-center justify-between border-b border-border bg-background/85 backdrop-blur-xl px-4 md:px-6 z-10">
          <div className="flex items-center gap-3 min-w-0">
            <button
              aria-label="Buka menu"
              className="rounded-lg p-2 hover:bg-secondary md:hidden"
              onClick={() => setIsSidebarOpen(true)}
            >
              <Menu size={20} />
            </button>
            {/* Breadcrumb-style current page indicator */}
            <div className="hidden md:flex items-center gap-2 text-sm min-w-0">
              <Sparkles className="size-3.5 text-primary shrink-0" />
              <span className="font-mono text-[10px] uppercase tracking-widest text-muted-foreground shrink-0">
                {role.replaceAll("_", " ")}
              </span>
              <ChevronRight className="size-3.5 text-muted-foreground/40 shrink-0" />
              <span className="font-semibold text-foreground truncate">
                {menus[role].find((m) => m.href === pathname)?.name ?? "Halaman"}
              </span>
            </div>
          </div>

          <div className="ml-auto flex items-center gap-2 shrink-0">
            {/* Live indicator pill */}
            <div className="hidden sm:inline-flex items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 font-mono text-[10px] uppercase tracking-widest text-emerald-700 dark:text-emerald-400">
              <span className="relative flex size-1.5">
                <span className="absolute inset-0 rounded-full bg-emerald-500 animate-ping opacity-75" />
                <span className="relative inline-flex size-1.5 rounded-full bg-emerald-500" />
              </span>
              <span className="font-semibold">Live</span>
            </div>
            {/* Status pill */}
            <div className="hidden md:inline-flex items-center gap-2 rounded-full border border-primary/20 bg-primary/10 px-3 py-1 font-mono text-[10px] uppercase tracking-widest text-primary">
              <span className="size-1.5 rounded-full bg-primary" />
              {user.status}
            </div>
            {isAdmin && (
              <span className="hidden lg:inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-1 font-mono text-[10px] uppercase tracking-widest text-amber-700 dark:text-amber-400">
                <ShieldCheck className="size-3" />
                Admin
              </span>
            )}
            <NotificationBell />
            <ThemeToggle />
          </div>
        </header>

        <main className="flex-1 overflow-y-auto p-4 md:p-8">
          <div className="mx-auto max-w-7xl">{children}</div>
        </main>
      </div>
    </div>
  );
}
