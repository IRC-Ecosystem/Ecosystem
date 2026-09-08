"use client";

import * as React from "react";

/**
 * Returns true setelah client mount, false di SSR & first paint.
 *
 * Pakai useSyncExternalStore (bukan useState + useEffect) agar
 * tidak memicu react-hooks/set-state-in-effect ESLint rule dan
 * untuk re-render yang deterministik di server vs client.
 */
export function useIsClient(): boolean {
  return React.useSyncExternalStore(
    () => () => {},
    () => true,
    () => false,
  );
}
