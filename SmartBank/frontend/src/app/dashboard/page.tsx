"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuthStore } from "@/store/auth";
import { useIsClient } from "@/lib/use-is-client";
import RetailDashboard from "@/components/dashboards/RetailDashboard";

export default function DashboardPage() {
  const { user, hydrated, rehydrate } = useAuthStore();
  const isClient = useIsClient();
  const router = useRouter();

  useEffect(() => {
    rehydrate();
  }, [rehydrate]);

  useEffect(() => {
    if (!isClient || !hydrated) return;
    if (user?.role === "TELLER") router.replace("/teller/nasabah");
    if (user?.role === "MANAGER") router.replace("/manager/pinjaman");
    if (user?.role === "ADMIN" || user?.role === "CENTRAL_BANK_ADMIN") router.replace("/admin");
  }, [isClient, hydrated, router, user?.role]);

  // Render placeholder identik di SSR & client sampai store rehydrate.
  if (!isClient || !hydrated || !user) {
    return <p className="p-8 text-sm text-muted-foreground">Memuat dashboard…</p>;
  }

  if (user.role === "WALLET_USER" || user.role === "RETAIL_CUSTOMER" || user.role === "RETAIL") {
    return <RetailDashboard mode="overview" />;
  }

  return <p className="text-sm text-muted-foreground">Mengalihkan ke dashboard role...</p>;
}
