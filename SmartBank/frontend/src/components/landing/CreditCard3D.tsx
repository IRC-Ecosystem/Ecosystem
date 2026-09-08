"use client";

import { useRef, useEffect } from "react";
import { motion, useMotionValue, useSpring, useTransform } from "framer-motion";
import { Wifi } from "lucide-react";

export type CreditCard3DProps = {
  width?: number;
  variant?: "blue" | "indigo" | "midnight";
  validThru?: string;
  holderName?: string;
  last4?: string;
  staticTilt?: boolean;
};

export function CreditCard3D({
  width = 360,
  variant = "blue",
  validThru = "12/28",
  holderName = "A. WIJAYA K.",
  last4 = "8472",
  staticTilt = false,
}: CreditCard3DProps) {
  const ref = useRef<HTMLDivElement>(null);
  const mx = useMotionValue(0);
  const my = useMotionValue(0);
  const rx = useSpring(useTransform(my, [-0.5, 0.5], [10, -10]), { stiffness: 300, damping: 30 });
  const ry = useSpring(useTransform(mx, [-0.5, 0.5], [-12, 12]), { stiffness: 300, damping: 30 });

  const onMove = (e: React.MouseEvent) => {
    if (staticTilt) return;
    const r = ref.current?.getBoundingClientRect();
    if (!r) return;
    mx.set((e.clientX - r.left) / r.width - 0.5);
    my.set((e.clientY - r.top) / r.height - 0.5);
  };
  const onLeave = () => {
    mx.set(0);
    my.set(0);
  };

  const aspect = 1.586;
  const height = Math.round(width / aspect);

  const gradients: Record<string, { from: string; via: string; to: string; glow: string }> = {
    blue: {
      from: "from-blue-600",
      via: "via-blue-500",
      to: "to-cyan-600",
      glow: "shadow-blue-500/40",
    },
    indigo: {
      from: "from-indigo-700",
      via: "via-indigo-600",
      to: "to-blue-700",
      glow: "shadow-indigo-500/40",
    },
    midnight: {
      from: "from-slate-900",
      via: "via-slate-800",
      to: "to-blue-900",
      glow: "shadow-slate-700/50",
    },
  };
  const g = gradients[variant];

  return (
    <div
      ref={ref}
      onMouseMove={onMove}
      onMouseLeave={onLeave}
      className="relative [perspective:800px] will-change-transform"
      style={{ width, height }}
    >
      <motion.div
        style={{ rotateX: staticTilt ? 6 : rx, rotateY: staticTilt ? -8 : ry }}
        className="relative w-full h-full [transform-style:preserve-3d] will-change-transform"
      >
        <div
          className={`absolute inset-0 rounded-2xl bg-gradient-to-br ${g.from} ${g.via} ${g.to} [transform:translateZ(-5px)]`}
          aria-hidden
        />

        <div
          className={`absolute inset-0 rounded-2xl bg-gradient-to-br ${g.from} ${g.via} ${g.to} dark:from-blue-700 dark:via-blue-800 dark:to-indigo-900 ${g.glow} shadow-[0_20px_60px_-15px_rgba(37,99,235,0.3)] [backface-visibility:hidden] overflow-hidden`}
        >
          <div className="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent" />

          <div className="absolute top-5 inset-x-5 flex items-start justify-between z-10">
            <p className="font-display text-base font-bold text-white tracking-tight">SmartBank</p>
            <Wifi className="size-4 text-white/80 rotate-90" strokeWidth={2.5} />
          </div>

          <div className="absolute top-[52px] left-5 z-10">
            <div className="w-11 h-8 rounded-md bg-gradient-to-br from-amber-300/90 to-amber-500/90 border border-amber-700/30 shadow-inner relative overflow-hidden">
              <div className="absolute inset-1 grid grid-cols-3 gap-px">
                {Array.from({ length: 9 }).map((_, i) => (
                  <div key={i} className="bg-amber-700/35 rounded-sm" />
                ))}
              </div>
            </div>
          </div>

          <div className="absolute inset-x-5 bottom-[58px] z-10">
            <div className="font-mono text-base sm:text-lg font-medium text-white tracking-[0.22em] flex items-center justify-between">
              <span className="opacity-50">****</span>
              <span className="opacity-50">****</span>
              <span className="opacity-50">****</span>
              <span>{last4}</span>
            </div>
          </div>

          <div className="absolute inset-x-5 bottom-4 z-10 flex items-end justify-between">
            <div className="min-w-0 flex-1">
              <p className="text-[7px] text-white/60 uppercase tracking-[0.2em] font-mono">Pemegang</p>
              <p className="font-mono text-[10px] text-white mt-0.5 truncate">{holderName}</p>
            </div>
            <div className="text-right mr-3">
              <p className="text-[7px] text-white/60 uppercase tracking-[0.2em] font-mono">Valid</p>
              <p className="font-mono text-[10px] text-white mt-0.5">{validThru}</p>
            </div>
            <div className="font-display text-lg font-bold italic text-white/95 tracking-tight">VISA</div>
          </div>
        </div>
      </motion.div>
    </div>
  );
}
