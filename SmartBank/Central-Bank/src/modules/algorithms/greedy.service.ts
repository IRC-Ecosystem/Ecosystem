import { Injectable } from "@nestjs/common";

export type AuditCandidate = {
  id: string;
  amount: bigint;
  /** "BASIC" | "VERIFIED" */
  kycTier: "BASIC" | "VERIFIED";
  /** timestamp (ms epoch) — newer = higher risk of being a new pattern */
  createdAt: number;
  /** Berapa kali PIN dimasukkan salah dalam 1 jam terakhir (heuristik) */
  failedPinCount: number;
  /** Apakah wallet baru dibuat (< 24 jam) */
  isNewWallet: boolean;
  /** Apakah ada transaksi berulang ke wallet berbeda dalam 1 jam */
  burstTransfer: boolean;
};

/**
 * Greedy — Pilih elemen terbaik pada setiap langkah.
 *
 * Use case SmartBank: scoring & ranking audit/KYC/loan untuk diprioritaskan
 * oleh Teller / Manager / Admin. Decision final tetap pada manusia.
 *
 * Kompleksitas: O(n log n) untuk sort, O(n) untuk linear selection
 */
@Injectable()
export class GreedyService {
  /**
   * Hitung skor risiko 0-100 untuk satu kandidat.
   * Bobot: nominal + KYC BASIC + aktivitas mencurigakan.
   */
  score(c: AuditCandidate): number {
    let s = 0;
    // Nominal besar: +0..+30 (cap 10jt) — konversi ke number dulu
    const cap = 10_000_000;
    const amountNum = Math.min(cap, Number(c.amount));
    const amountScore = (amountNum / cap) * 30;
    s += amountScore;
    // KYC BASIC: +25
    if (c.kycTier === "BASIC") s += 25;
    // Wallet baru (< 24 jam): +15
    const ageMs = Date.now() - c.createdAt;
    if (ageMs < 24 * 60 * 60 * 1000) s += 15;
    // Gagal PIN: +3 per attempt, cap +15
    s += Math.min(15, c.failedPinCount * 3);
    // Burst transfer: +15
    if (c.burstTransfer) s += 15;
    return Math.min(100, Math.max(0, s));
  }

  /**
   * Sort kandidat descending by skor (greedy: selalu ambil skor tertinggi lebih dulu).
   * Return sorted list + skor per item.
   */
  prioritize(candidates: AuditCandidate[]): Array<{ candidate: AuditCandidate; score: number; reason: string }> {
    return candidates
      .map((c) => ({ candidate: c, score: this.score(c), reason: this.reason(c) }))
      .sort((a, b) => b.score - a.score);
  }

  /**
   * Greedy single-step: ambil top-K kandidat untuk direview.
   * Cocok untuk antrian review Teller/Manager.
   */
  selectTopK(candidates: AuditCandidate[], k: number): Array<{ candidate: AuditCandidate; score: number; reason: string }> {
    return this.prioritize(candidates).slice(0, k);
  }

  /** Bangun string alasan manusia-baca untuk transparansi. */
  private reason(c: AuditCandidate): string {
    const reasons: string[] = [];
    if (c.kycTier === "BASIC") reasons.push("KYC BASIC");
    if (c.failedPinCount > 0) reasons.push(`${c.failedPinCount}x PIN gagal`);
    if (c.burstTransfer) reasons.push("burst transfer");
    if (Date.now() - c.createdAt < 24 * 60 * 60 * 1000) reasons.push("wallet baru");
    if (reasons.length === 0) reasons.push("nominal besar");
    return reasons.join(" · ");
  }
}
