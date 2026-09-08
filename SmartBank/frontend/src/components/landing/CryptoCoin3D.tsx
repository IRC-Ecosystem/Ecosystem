"use client";

import { useRef } from "react";
import { motion, useMotionValue, useSpring, useTransform } from "framer-motion";

/**
 * Glassmorphic 3D crypto coin — pure SVG + CSS 3D transforms.
 * Hover-only tilt. No RAF loop. No orbit particles. No infinite conic.
 */
export function CryptoCoin3D({
  size = 360,
  symbol = "₿",
  symbolLabel = "CBDC · IDR",
}: {
  size?: number;
  symbol?: string;
  symbolLabel?: string;
}) {
  const ref = useRef<HTMLDivElement>(null);
  const mx = useMotionValue(0);
  const my = useMotionValue(0);
  // Stiffness 300+ / damping 30+ → snappy, no overshoot
  const rx = useSpring(useTransform(my, [-0.5, 0.5], [18, -18]), { stiffness: 320, damping: 30 });
  const ry = useSpring(useTransform(mx, [-0.5, 0.5], [-22, 22]), { stiffness: 320, damping: 30 });

  const onMove = (e: React.MouseEvent) => {
    const r = ref.current?.getBoundingClientRect();
    if (!r) return;
    mx.set((e.clientX - r.left) / r.width - 0.5);
    my.set((e.clientY - r.top) / r.height - 0.5);
  };
  const onLeave = () => {
    mx.set(0);
    my.set(0);
  };

  return (
    <div
      ref={ref}
      onMouseMove={onMove}
      onMouseLeave={onLeave}
      className="relative [perspective:800px] mx-auto"
      style={{ width: size, height: size }}
    >
      <motion.div
        style={{ rotateX: rx, rotateY: ry }}
        className="relative w-full h-full [transform-style:preserve-3d]"
      >
        {/* ---- COIN FACE (front) ---- */}
        <div
          className="absolute inset-0 rounded-full [backface-visibility:hidden] shadow-[0_40px_80px_-20px_rgba(37,99,235,0.55),inset_0_-25px_50px_rgba(0,0,0,0.22),inset_0_3px_4px_rgba(255,255,255,0.45)]"
          style={{
            background:
              "radial-gradient(circle at 35% 25%, #60a5fa 0%, #2563eb 35%, #1e40af 70%, #0c1e3f 100%)",
          }}
        >
          {/* Inner concentric rings (minting detail) */}
          <div className="absolute inset-3 rounded-full border-2 border-white/25" />
          <div className="absolute inset-6 rounded-full border border-white/15" />
          <div className="absolute inset-8 rounded-full border border-dashed border-white/10" />

          {/* Top-left specular highlight */}
          <div className="absolute -top-10 -left-10 w-48 h-48 rounded-full bg-white/25 blur-3xl pointer-events-none" />
          {/* Bottom-right cyan glow */}
          <div className="absolute -bottom-12 -right-12 w-56 h-56 rounded-full bg-cyan-400/30 blur-3xl pointer-events-none" />

          {/* Embossed symbol with depth */}
          <div className="absolute inset-0 flex flex-col items-center justify-center [text-shadow:0_4px_12px_rgba(0,0,0,0.4),0_-1px_0_rgba(255,255,255,0.3)]">
            <div className="font-display font-bold text-white leading-none tracking-tighter" style={{ fontSize: size * 0.34 }}>
              {symbol}
            </div>
            <div className="text-white/85 font-mono uppercase tracking-[0.35em] mt-2" style={{ fontSize: size * 0.032 }}>
              {symbolLabel}
            </div>
          </div>

          {/* Edge bezel highlight */}
          <div className="absolute inset-0 rounded-full ring-1 ring-white/10 pointer-events-none" />
        </div>

        {/* ---- COIN EDGE (side bevel for thickness illusion) ---- */}
        <div
          className="absolute inset-0 rounded-full [transform:translateZ(-14px)]"
          style={{
            background: "linear-gradient(135deg, #1e3a8a 0%, #0c1e3f 100%)",
            boxShadow: "inset 0 0 0 1px rgba(255,255,255,0.06)",
          }}
          aria-hidden
        />
        {/* Mid-edge ridge */}
        <div
          className="absolute inset-0 rounded-full [transform:translateZ(-7px)] bg-gradient-to-b from-blue-700/60 via-blue-900/40 to-blue-900/70"
          aria-hidden
        />
      </motion.div>

      {/* Floor reflection (subtle, static) */}
      <div className="absolute -bottom-8 left-1/2 -translate-x-1/2 w-3/4 h-8 rounded-full bg-blue-500/15 blur-2xl pointer-events-none" />
    </div>
  );
}
