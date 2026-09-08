"use client";

import * as React from "react";

// Custom theme provider TANPA next-themes untuk menghindari React 19
// "Encountered a script tag while rendering" error di Next 16 + Turbopack.
//
// next-themes v0.4.x inject <script> inline di React tree untuk mencegah
// FOUC dark mode. React 19 throw console error karena script di dalam
// component tree tidak dieksekusi.
//
// Implementasi ini: simple React state + localStorage persistence +
// media query listener. Tidak ada inline script sama sekali.
//
// Catatan: ada sedikit FOUC (light → dark flash) pada first paint
// karena kita tidak bisa inject script ke <head>. Untuk mitigation,
// tambahkan script di <head> via next/script di layout (opsional).

type Theme = "light" | "dark" | "system";
type ResolvedTheme = "light" | "dark";

interface ThemeContextValue {
  theme: Theme;
  resolvedTheme: ResolvedTheme;
  setTheme: (next: Theme) => void;
}

const ThemeContext = React.createContext<ThemeContextValue | null>(null);

const STORAGE_KEY = "smartbank-theme";

function isTheme(value: string | null): value is Theme {
  return value === "light" || value === "dark" || value === "system";
}

function readInitialTheme(): Theme {
  // Lazy initializer di useState: jalan di server (return "light" karena
  // window undefined) dan di client first render (return localStorage value
  // atau "light"). Tidak perlu useEffect untuk initial load.
  if (typeof window === "undefined") return "light";
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (isTheme(raw)) return raw;
  } catch {
    // localStorage unavailable — fallback
  }
  return "light";
}

function readSystemTheme(): ResolvedTheme {
  if (typeof window === "undefined") return "light";
  return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

function applyThemeClass(resolved: ResolvedTheme): void {
  if (typeof document === "undefined") return;
  const root = document.documentElement;
  root.classList.remove("light", "dark");
  root.classList.add(resolved);
  root.style.colorScheme = resolved;
}

function persistTheme(value: Theme): void {
  if (typeof window === "undefined") return;
  try {
    localStorage.setItem(STORAGE_KEY, value);
  } catch {
    // silent
  }
}

function useSystemTheme(): ResolvedTheme {
  // Pakai useSyncExternalStore untuk subscribe ke prefers-color-scheme
  // changes — proper React 19 pattern, tidak trigger cascading-render.
  const subscribe = React.useCallback((onChange: () => void) => {
    if (typeof window === "undefined") return () => {};
    const mq = window.matchMedia("(prefers-color-scheme: dark)");
    const handler = () => onChange();
    mq.addEventListener("change", handler);
    return () => mq.removeEventListener("change", handler);
  }, []);

  return React.useSyncExternalStore(subscribe, readSystemTheme, () => "light");
}

export function ThemeProvider({ children }: { children: React.ReactNode }) {
  const [theme, setThemeState] = React.useState<Theme>(readInitialTheme);
  const systemTheme = useSystemTheme();
  const resolvedTheme: ResolvedTheme = theme === "system" ? systemTheme : theme;

  // Apply class ke <html> setiap resolvedTheme berubah
  React.useEffect(() => {
    applyThemeClass(resolvedTheme);
  }, [resolvedTheme]);

  const setTheme = React.useCallback((next: Theme) => {
    setThemeState(next);
    persistTheme(next);
  }, []);

  const value = React.useMemo<ThemeContextValue>(
    () => ({ theme, resolvedTheme, setTheme }),
    [theme, resolvedTheme, setTheme],
  );

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
  const ctx = React.useContext(ThemeContext);
  if (!ctx) {
    // Fallback saat ThemeProvider belum mount (mis. SSR) — return default
    return { theme: "light", resolvedTheme: "light", setTheme: () => {} };
  }
  return ctx;
}
