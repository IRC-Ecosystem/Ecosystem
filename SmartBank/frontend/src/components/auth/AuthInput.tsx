"use client";

import { useState } from "react";
import { Eye, EyeOff } from "lucide-react";
import type { LucideIcon } from "lucide-react";
import { motion } from "framer-motion";

type AuthInputProps = Omit<React.InputHTMLAttributes<HTMLInputElement>, "type"> & {
  label: string;
  icon: LucideIcon;
  type?: "text" | "email" | "password" | "tel";
  error?: string;
  hint?: string;
};

export function AuthInput({
  label,
  icon: Icon,
  type = "text",
  error,
  hint,
  className = "",
  ...rest
}: AuthInputProps) {
  const [show, setShow] = useState(false);
  const isPassword = type === "password";
  const inputType = isPassword ? (show ? "text" : "password") : type;

  return (
    <motion.div
      className="space-y-1.5"
      animate={error ? { x: [0, -4, 4, -4, 4, 0] } : { x: 0 }}
      transition={{ duration: 0.4, ease: "easeInOut" }}
    >
      <div className="flex items-center justify-between gap-2">
        <label className="text-xs font-medium text-foreground">{label}</label>
        {hint && <span className="text-[10px] text-muted-foreground font-mono">{hint}</span>}
      </div>
      <div className="relative group">
        {/* Inner refraction line */}
        <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/50 dark:via-white/10 to-transparent rounded-t-lg pointer-events-none z-10" />

        <Icon className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground group-focus-within:text-primary transition-colors pointer-events-none z-10" />
        <input
          type={inputType}
          className={`w-full bg-secondary/40 border ${
            error ? "border-destructive/60" : "border-border"
          } focus:border-primary rounded-lg py-2.5 pl-10 ${
            isPassword ? "pr-11" : "pr-4"
          } outline-none transition-all duration-300 ease-[cubic-bezier(0.16,1,0.3,1)] text-sm font-mono text-foreground placeholder:text-muted-foreground/50 focus:bg-secondary/70 focus:shadow-[0_0_0_3px_rgba(37,99,235,0.12)] ${
            error ? "focus:border-destructive focus:shadow-[0_0_0_3px_rgba(239,68,68,0.12)]" : ""
          } ${className}`}
          {...rest}
        />
        {isPassword && (
          <button
            type="button"
            onClick={() => setShow((v) => !v)}
            className="absolute right-1.5 top-1/2 -translate-y-1/2 p-1.5 text-muted-foreground hover:text-foreground transition-colors rounded z-10"
            tabIndex={-1}
            aria-label={show ? "Sembunyikan password" : "Tampilkan password"}
          >
            {show ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
          </button>
        )}
      </div>
      {error && (
        <p className="text-[11px] text-destructive flex items-center gap-1.5">
          <span className="w-1 h-1 rounded-full bg-destructive shrink-0" />
          {error}
        </p>
      )}
    </motion.div>
  );
}
