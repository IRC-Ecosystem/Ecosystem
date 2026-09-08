import { Injectable } from "@nestjs/common";

/**
 * BFS (Breadth-First Search) — Penelusuran graf level-order.
 *
 * Use case SmartBank: tracing hubungan wallet dari riwayat ledger.
 * Wallet sebagai node, transaksi (tx) sebagai edge berarah.
 *
 * Kompleksitas: O(V + E)
 */
@Injectable()
export class BfsService {
  /**
   * BFS dari node start, mengembalikan daftar node yang dikunjungi
   * secara level-order (jarak dari start, ascending).
   */
  traverse(
    graph: Map<string, string[]>,
    start: string,
    maxDepth = 5,
  ): Array<{ walletId: string; depth: number; via: string | null }> {
    if (!graph.has(start)) return [];
    const visited = new Set<string>([start]);
    const result: Array<{ walletId: string; depth: number; via: string | null }> = [{ walletId: start, depth: 0, via: null }];
    const queue: Array<{ node: string; depth: number; via: string | null }> = [{ node: start, depth: 0, via: null }];

    while (queue.length > 0) {
      const current = queue.shift()!;
      if (current.depth >= maxDepth) continue;
      const neighbors = graph.get(current.node) ?? [];
      for (const next of neighbors) {
        if (visited.has(next)) continue;
        visited.add(next);
        result.push({ walletId: next, depth: current.depth + 1, via: current.node });
        queue.push({ node: next, depth: current.depth + 1, via: current.node });
      }
    }
    return result;
  }

  /**
   * Cari shortest path (jumlah hop minimum) dari start ke target.
   * Return null jika tidak ditemukan.
   */
  shortestPath(graph: Map<string, string[]>, start: string, target: string, maxDepth = 8): string[] | null {
    if (!graph.has(start) || start === target) return start === target ? [start] : null;
    const visited = new Set<string>([start]);
    const queue: Array<{ node: string; path: string[] }> = [{ node: start, path: [start] }];

    while (queue.length > 0) {
      const current = queue.shift()!;
      if (current.path.length > maxDepth) continue;
      const neighbors = graph.get(current.node) ?? [];
      for (const next of neighbors) {
        if (visited.has(next)) continue;
        const nextPath = [...current.path, next];
        if (next === target) return nextPath;
        visited.add(next);
        queue.push({ node: next, path: nextPath });
      }
    }
    return null;
  }
}

/**
 * DFS (Depth-First Search) — Penelusuran graf depth-first (rantai transaksi).
 *
 * Use case SmartBank: menelusuri rantai transaksi mendalam dari satu wallet
 * untuk audit forensik (misal A -> B -> C -> D).
 *
 * Kompleksitas: O(V + E)
 */
@Injectable()
export class DfsService {
  /**
   * DFS rekursif dari start, return semua path yang ditemukan.
   * Mendukung maxDepth untuk membatasi ledakan eksponensial.
   */
  findChains(
    graph: Map<string, string[]>,
    start: string,
    target: string,
    maxDepth = 6,
  ): string[][] {
    const results: string[][] = [];
    const path: string[] = [start];
    const visited = new Set<string>([start]);
    this.dfsHelper(graph, start, target, path, visited, results, maxDepth);
    return results;
  }

  private dfsHelper(
    graph: Map<string, string[]>,
    current: string,
    target: string,
    path: string[],
    visited: Set<string>,
    results: string[][],
    maxDepth: number,
  ): void {
    if (path.length > maxDepth) return;
    if (current === target && path.length > 1) {
      results.push([...path]);
      return;
    }
    const neighbors = graph.get(current) ?? [];
    for (const next of neighbors) {
      if (visited.has(next)) continue;
      visited.add(next);
      path.push(next);
      this.dfsHelper(graph, next, target, path, visited, results, maxDepth);
      path.pop();
      visited.delete(next);
    }
  }

  /**
   * DFS sederhana: return semua node yang reachable dari start (untuk visualisasi graf).
   */
  reachable(graph: Map<string, string[]>, start: string, maxDepth = 10): string[] {
    const visited = new Set<string>();
    const stack: Array<{ node: string; depth: number }> = [{ node: start, depth: 0 }];
    while (stack.length > 0) {
      const { node, depth } = stack.pop()!;
      if (visited.has(node) || depth > maxDepth) continue;
      visited.add(node);
      const neighbors = graph.get(node) ?? [];
      for (const next of neighbors) stack.push({ node: next, depth: depth + 1 });
    }
    return [...visited];
  }
}
