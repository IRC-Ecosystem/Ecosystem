import { Body, Controller, Get, Post, Query } from "@nestjs/common";
import { BfsService, DfsService } from "./bfs-dfs.service";
import { KmpService } from "./kmp.service";
import { GreedyService, AuditCandidate } from "./greedy.service";

/**
 * Demo endpoints untuk algoritma SmartBank.
 * Hanya untuk debugging / pengujian — production pakai services
 * langsung dari module lain (audit/central-bank).
 */
@Controller("algorithms")
export class AlgorithmsController {
  constructor(
    private readonly bfs: BfsService,
    private readonly dfs: DfsService,
    private readonly kmp: KmpService,
    private readonly greedy: GreedyService,
  ) {}

  // --- BFS/DFS ---

  @Post("graph/trace")
  traceGraph(
    @Body()
    body: {
      graph: Record<string, string[]>;
      start: string;
      maxDepth?: number;
    },
  ) {
    const graph = new Map(Object.entries(body.graph));
    return this.bfs.traverse(graph, body.start, body.maxDepth ?? 5);
  }

  @Post("graph/shortest-path")
  shortestPath(
    @Body()
    body: {
      graph: Record<string, string[]>;
      start: string;
      target: string;
      maxDepth?: number;
    },
  ) {
    const graph = new Map(Object.entries(body.graph));
    return this.bfs.shortestPath(graph, body.start, body.target, body.maxDepth ?? 8);
  }

  @Post("graph/chains")
  findChains(
    @Body()
    body: {
      graph: Record<string, string[]>;
      start: string;
      target: string;
      maxDepth?: number;
    },
  ) {
    const graph = new Map(Object.entries(body.graph));
    return this.dfs.findChains(graph, body.start, body.target, body.maxDepth ?? 6);
  }

  // --- KMP ---

  @Post("kmp/search")
  kmpSearch(@Body() body: { text: string; pattern: string; caseInsensitive?: boolean }) {
    return this.kmp.search(body.text, body.pattern, body.caseInsensitive ?? true);
  }

  @Post("kmp/prefix")
  kmpPrefix(@Body() body: { pattern: string }) {
    return this.kmp.buildPrefixTable(body.pattern);
  }

  // --- Greedy ---

  @Post("greedy/prioritize")
  prioritize(@Body() body: { candidates: AuditCandidate[] }) {
    return this.greedy.prioritize(body.candidates);
  }

  @Post("greedy/select-top-k")
  selectTopK(@Body() body: { candidates: AuditCandidate[]; k: number }) {
    return this.greedy.selectTopK(body.candidates, body.k);
  }

  @Get("info")
  info() {
    return {
      algorithms: [
        { name: "BFS", complexity: "O(V + E)", use_case: "Tracing hubungan wallet" },
        { name: "DFS", complexity: "O(V + E)", use_case: "Chains (A→B→C→D)" },
        { name: "KMP", complexity: "O(n + m)", use_case: "String matching untuk search audit" },
        { name: "Greedy", complexity: "O(n log n)", use_case: "Skor risiko untuk review prioritas" },
      ],
      reference: "Analisis Algoritma yang Cocok untuk SmartBank — Modul Praktikum 2026",
    };
  }
}
