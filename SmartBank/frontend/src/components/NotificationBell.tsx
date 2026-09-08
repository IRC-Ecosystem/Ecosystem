"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { Bell } from "lucide-react";
import { unreadNotificationCount } from "@/lib/notifications";

export default function NotificationBell() {
  const [count, setCount] = useState(0);

  const refresh = useCallback(async () => {
    try { setCount(await unreadNotificationCount()); } catch { /* Inbox will display the full error. */ }
  }, []);

  useEffect(() => {
    const initial = window.setTimeout(() => void refresh(), 0);
    const interval = window.setInterval(() => void refresh(), 10_000);
    return () => {
      window.clearTimeout(initial);
      window.clearInterval(interval);
    };
  }, [refresh]);

  return (
    <Link href="/inbox" aria-label={`Inbox${count ? `, ${count} belum dibaca` : ""}`} className="relative inline-flex size-9 items-center justify-center rounded-xl border border-border bg-card text-muted-foreground transition hover:bg-secondary hover:text-foreground">
      <Bell className="size-4" />
      {count > 0 && (
        <span className="absolute -right-1.5 -top-1.5 min-w-5 rounded-full bg-rose-500 px-1.5 py-0.5 text-center font-mono text-[9px] font-bold text-white shadow-sm">
          {count > 99 ? "99+" : count}
        </span>
      )}
    </Link>
  );
}
