import { Module } from "@nestjs/common";
import { BfsService, DfsService } from "./bfs-dfs.service";
import { KmpService } from "./kmp.service";
import { GreedyService } from "./greedy.service";
import { AlgorithmsController } from "./algorithms.controller";

/**
 * Algorithms module — BFS/DFS + KMP + Greedy.
 *
 * Sesuai Analisis Algoritma yang Cocok untuk SmartBank (Modul Praktikum 2026):
 *  - BFS/DFS untuk tracing transaksi (graf wallet)
 *  - KMP untuk string matching pada pencarian user/audit/reason code
 *  - Greedy untuk prioritas review KYC/audit/loan
 *
 * Services dipakai langsung oleh module lain (central-bank, audit, dll.)
 * + endpoint HTTP untuk demo / debugging via Postman.
 */
@Module({
  controllers: [AlgorithmsController],
  providers: [BfsService, DfsService, KmpService, GreedyService],
  exports: [BfsService, DfsService, KmpService, GreedyService],
})
export class AlgorithmsModule {}
