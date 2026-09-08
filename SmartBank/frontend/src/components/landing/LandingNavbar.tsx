"use client";

import { useEffect, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { Building2, Menu, X } from "lucide-react";
import Link from "next/link";
import { ThemeToggle } from "@/components/theme-toggle";

const sections = [
  { label: "Arsitektur", href: "#arsitektur" },
  { label: "Tentang", href: "#tentang" },
  { label: "Layanan", href: "#layanan" },
  { label: "Keamanan", href: "#keamanan" },
  { label: "Peran", href: "#peran" },
  { label: "Biaya", href: "#biaya" },
];

export default function LandingNavbar() {
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    let raf = 0;
    let queued = false;
    const update = () => {
      setScrolled(window.scrollY > 12);
      queued = false;
    };
    const onScroll = () => {
      if (queued) return;
      queued = true;
      raf = requestAnimationFrame(update);
    };
    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => {
      window.removeEventListener("scroll", onScroll);
      if (raf) cancelAnimationFrame(raf);
    };
  }, []);

  return (
    <motion.header
      initial={{ y: -20, opacity: 0 }}
      animate={{ y: 0, opacity: 1 }}
      transition={{ duration: 0.5, ease: [0.16, 1, 0.3, 1] }}
      className={`fixed top-0 inset-x-0 z-50 transition-all duration-500 ${
        scrolled
          ? "bg-background/75 backdrop-blur-2xl border-b border-border/60 shadow-[0_8px_32px_-12px_rgba(2,6,23,0.12)] dark:shadow-[0_8px_32px_-12px_rgba(0,0,0,0.6)]"
          : "bg-transparent"
      }`}
      style={{
        WebkitBackdropFilter: "blur(20px) saturate(180%)",
      }}
    >
      {/* Liquid glass inner refraction border */}
      <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/20 to-transparent pointer-events-none" />

      <nav className="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between gap-4">
        {/* Brand */}
        <Link href="/" className="flex items-center gap-2.5 shrink-0 group">
          <div className="relative size-9 rounded-xl bg-gradient-to-br from-primary to-blue-700 dark:from-blue-500 dark:to-blue-700 flex items-center justify-center text-primary-foreground shadow-lg shadow-primary/25 transition-transform duration-300 group-hover:scale-105 group-hover:rotate-3">
            <Building2 className="w-4 h-4" />
            <div className="absolute inset-0 rounded-xl bg-gradient-to-t from-transparent to-white/20 pointer-events-none" />
          </div>
          <span className="font-display font-semibold text-foreground text-base tracking-tight">
            SmartBank
          </span>
          <span className="hidden sm:inline-block text-[10px] font-mono uppercase tracking-widest text-muted-foreground border border-border/60 rounded px-1.5 py-0.5 backdrop-blur-sm">
            CBDC
          </span>
        </Link>

        {/* Desktop section links */}
        <ul className="hidden lg:flex items-center gap-1">
          {sections.map((s) => (
            <li key={s.href}>
              <a
                href={s.href}
                className="relative px-3 py-2 rounded-md text-sm font-medium text-muted-foreground hover:text-foreground transition-colors duration-200 group"
              >
                {s.label}
                <span className="absolute inset-x-3 bottom-1 h-px bg-primary scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left" />
              </a>
            </li>
          ))}
        </ul>

        {/* Right cluster */}
        <div className="flex items-center gap-2">
          <ThemeToggle />
          <Link
            href="/guide"
            className="hidden md:inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-muted-foreground hover:text-foreground transition-colors"
          >
            Panduan
          </Link>
          <Link
            href="/login"
            className="hidden sm:inline-flex items-center px-3 py-2 rounded-md text-sm font-medium text-foreground hover:bg-secondary/60 transition-colors"
          >
            Masuk
          </Link>
          <Link
            href="/register"
            className="group relative inline-flex items-center px-4 py-2 rounded-md text-sm font-semibold bg-primary text-primary-foreground transition-all duration-300 hover:scale-[1.03] active:scale-[0.97] shadow-md shadow-primary/30 hover:shadow-lg hover:shadow-primary/40"
          >
            <span className="relative z-10">Daftar</span>
            <div className="absolute inset-0 rounded-md bg-gradient-to-t from-transparent to-white/15 pointer-events-none" />
          </Link>
          <button
            type="button"
            aria-label="Buka menu"
            aria-expanded={open}
            className="lg:hidden inline-flex items-center justify-center size-9 rounded-md text-foreground hover:bg-secondary/60 transition-colors"
            onClick={() => setOpen((v) => !v)}
          >
            {open ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
          </button>
        </div>
      </nav>

      {/* Mobile dropdown */}
      <AnimatePresence>
        {open && (
          <motion.div
            initial={{ opacity: 0, y: -8 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -8 }}
            transition={{ duration: 0.2 }}
            className="lg:hidden border-t border-border/60 bg-background/85 backdrop-blur-2xl"
          >
            <ul className="max-w-7xl mx-auto px-6 py-3 flex flex-col gap-1">
              {sections.map((s) => (
                <li key={s.href}>
                  <a
                    href={s.href}
                    onClick={() => setOpen(false)}
                    className="block px-3 py-2 rounded-md text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-secondary/60 transition-colors"
                  >
                    {s.label}
                  </a>
                </li>
              ))}
              <li className="md:hidden">
                <Link
                  href="/guide"
                  onClick={() => setOpen(false)}
                  className="block px-3 py-2 rounded-md text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-secondary/60 transition-colors"
                >
                  Panduan
                </Link>
              </li>
            </ul>
          </motion.div>
        )}
      </AnimatePresence>
    </motion.header>
  );
}
