"use client";

import { ReactNode, useEffect } from "react";
import { Role, useAuthStore } from "@/store/auth";
import { useIsClient } from "@/lib/use-is-client";

export default function RolePage({ allowed, children }: { allowed: Role[]; children: ReactNode }) {
  const { user, hydrated, rehydrate } = useAuthStore();
  const isClient = useIsClient();

  useEffect(() => {
    rehydrate();
  }, [rehydrate]);

  if (!isClient || !hydrated) {
    return null;
  }
  if (!user) return null;
  if (!allowed.includes(user.role)) {
    return <div className="rounded-2xl border border-destructive/30 bg-destructive/10 p-8 text-center text-sm text-destructive">Role Anda tidak memiliki akses ke halaman ini.</div>;
  }
  return <>{children}</>;
}
