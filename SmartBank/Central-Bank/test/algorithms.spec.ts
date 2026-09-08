import { BfsService, DfsService } from "../src/modules/algorithms/bfs-dfs.service";
import { KmpService } from "../src/modules/algorithms/kmp.service";
import { GreedyService, AuditCandidate } from "../src/modules/algorithms/greedy.service";

describe("BFS — transaction tracing", () => {
  const bfs = new BfsService();

  const sampleGraph = new Map<string, string[]>([
    ["A", ["B", "C"]],
    ["B", ["D", "E"]],
    ["C", ["F"]],
    ["D", []],
    ["E", ["F"]],
    ["F", []],
  ]);

  it("traverses level-order from start", () => {
    const result = bfs.traverse(sampleGraph, "A", 5);
    const depths = result.map((r) => r.depth);
    // depth-first level order: 0 (A), 1 (B,C), 2 (D,E,F)
    expect(depths).toEqual([0, 1, 1, 2, 2, 2]);
    expect(result[0].walletId).toBe("A");
  });

  it("respects maxDepth limit", () => {
    const result = bfs.traverse(sampleGraph, "A", 1);
    expect(result.every((r) => r.depth <= 1)).toBe(true);
  });

  it("returns empty for missing start", () => {
    expect(bfs.traverse(sampleGraph, "Z", 3)).toEqual([]);
  });

  it("finds shortest path", () => {
    const path = bfs.shortestPath(sampleGraph, "A", "F");
    // A -> C -> F (length 2) atau A -> B -> E -> F (length 3)
    expect(path).not.toBeNull();
    expect(path![0]).toBe("A");
    expect(path![path!.length - 1]).toBe("F");
    expect(path!.length).toBe(3); // A -> C -> F (shortest)
  });

  it("returns null for unreachable target", () => {
    const isolated = new Map<string, string[]>([["A", ["B"]], ["B", []]]);
    expect(bfs.shortestPath(isolated, "A", "X")).toBeNull();
  });
});

describe("DFS — transaction chains", () => {
  const dfs = new DfsService();

  const graph = new Map<string, string[]>([
    ["A", ["B", "C"]],
    ["B", ["D"]],
    ["C", ["D", "E"]],
    ["D", ["F"]],
    ["E", ["F"]],
    ["F", []],
  ]);

  it("finds all chains A → F", () => {
    const chains = dfs.findChains(graph, "A", "F", 5);
    expect(chains.length).toBeGreaterThanOrEqual(2);
    chains.forEach((c) => {
      expect(c[0]).toBe("A");
      expect(c[c.length - 1]).toBe("F");
    });
  });

  it("respects maxDepth", () => {
    const chains = dfs.findChains(graph, "A", "F", 3);
    chains.forEach((c) => {
      expect(c.length).toBeLessThanOrEqual(4); // depth + 1
    });
  });

  it("returns reachable nodes from start", () => {
    const reachable = dfs.reachable(graph, "A", 5);
    expect(reachable.sort()).toEqual(["A", "B", "C", "D", "E", "F"]);
  });
});

describe("KMP — string matching", () => {
  const kmp = new KmpService();

  it("builds correct prefix table", () => {
    // pattern "ABABC": pi = [0, 0, 1, 2, 0]
    expect(kmp.buildPrefixTable("ABABC")).toEqual([0, 0, 1, 2, 0]);
    // pattern "AAAAA": pi = [0, 1, 2, 3, 4]
    expect(kmp.buildPrefixTable("AAAAA")).toEqual([0, 1, 2, 3, 4]);
  });

  it("finds all occurrences case-insensitive", () => {
    const text = "ADMIN_CORRECTION: failed pin 3x then ADMIN_CORRECTION retry";
    const matches = kmp.search(text, "admin_correction", true);
    expect(matches.length).toBe(2);
    matches.forEach((m) => {
      expect(text.toLowerCase().slice(m.index, m.index + "admin_correction".length)).toBe("admin_correction");
    });
  });

  it("returns empty for no match", () => {
    expect(kmp.search("hello world", "xyz")).toEqual([]);
  });

  it("filters records by pattern in specified fields", () => {
    const records = [
      { id: "1", action: "TRANSFER_SETTLED", reason: "OK" },
      { id: "2", action: "REVERSAL", reason: "MANUAL_CORRECTION" },
      { id: "3", action: "BURN", reason: "KYC_REVIEW" },
    ];
    const filtered = kmp.filter(records, "manual", ["action", "reason"]);
    expect(filtered.length).toBe(1);
    expect(filtered[0].id).toBe("2");
  });

  it("handles empty pattern gracefully", () => {
    expect(kmp.search("any text", "")).toEqual([]);
  });
});

describe("Greedy — audit priority scoring", () => {
  const greedy = new GreedyService();
  const now = Date.now();

  const candidates: AuditCandidate[] = [
    {
      id: "tx-low",
      amount: 1_000n, // 0.01% amount score
      kycTier: "VERIFIED", // 0
      createdAt: now - 30 * 24 * 60 * 60 * 1000, // 30 hari lalu, 0
      failedPinCount: 0, // 0
      isNewWallet: false,
      burstTransfer: false, // 0
    },
    {
      id: "tx-high",
      amount: 50_000_000n, // 30 (capped)
      kycTier: "BASIC", // +25
      createdAt: now - 1 * 60 * 60 * 1000, // 1 jam lalu, +15
      failedPinCount: 5, // +15 (capped)
      isNewWallet: true,
      burstTransfer: true, // +15
    },
    {
      id: "tx-mid",
      amount: 5_000_000n, // 15
      kycTier: "BASIC", // +25
      createdAt: now - 5 * 24 * 60 * 60 * 1000, // 5 hari, 0
      failedPinCount: 2, // +6
      isNewWallet: false,
      burstTransfer: false, // 0
    },
  ];

  it("scores high-risk transaction above others", () => {
    const prioritized = greedy.prioritize(candidates);
    expect(prioritized[0].candidate.id).toBe("tx-high");
    expect(prioritized[0].score).toBeGreaterThan(prioritized[1].score);
    expect(prioritized[1].score).toBeGreaterThan(prioritized[2].score);
  });

  it("caps individual score at 100", () => {
    const prioritized = greedy.prioritize(candidates);
    prioritized.forEach((p) => {
      expect(p.score).toBeLessThanOrEqual(100);
      expect(p.score).toBeGreaterThanOrEqual(0);
    });
  });

  it("top-K returns K highest", () => {
    const top2 = greedy.selectTopK(candidates, 2);
    expect(top2.length).toBe(2);
    expect(top2[0].candidate.id).toBe("tx-high");
    expect(top2[1].candidate.id).toBe("tx-mid");
  });

  it("produces human-readable reason", () => {
    const prioritized = greedy.prioritize(candidates);
    const high = prioritized.find((p) => p.candidate.id === "tx-high")!;
    expect(high.reason).toContain("KYC BASIC");
    expect(high.reason).toContain("PIN gagal");
  });

  it("greedy choice is local-optimal not global-optimal", () => {
    // Demonstrasi: greedy pilih by score tertinggi per item,
    // belum tentu kombinasi terbaik. Ini by-design sesuai modul.
    const items = [
      { id: "A", score: 50 },
      { id: "B", score: 49 },
      { id: "C", score: 48 },
    ];
    const selected = greedy.selectTopK(
      items.map((i) => ({ ...i, amount: 0n, kycTier: "BASIC" as const, createdAt: 0, failedPinCount: 0, isNewWallet: false, burstTransfer: false })),
      1,
    );
    expect(selected[0].candidate.id).toBe("A");
  });
});
