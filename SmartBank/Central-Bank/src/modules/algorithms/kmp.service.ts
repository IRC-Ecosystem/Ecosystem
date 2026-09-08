import { Injectable } from "@nestjs/common";

/**
 * KMP (Knuth-Morris-Pratt) — String matching dengan prefix function.
 *
 * Use case SmartBank: pencarian pola pada audit log (reason code, action),
 * reason code, nama nasabah, dan catatan transaksi.
 *
 * Kompleksitas: O(n + m) dimana n = panjang teks, m = panjang pattern
 */
@Injectable()
export class KmpService {
  /**
   * Bangun prefix function (failure function) untuk pattern.
   * P[i] = panjang prefix terpanjang yang juga suffix dari pattern[0..i]
   */
  buildPrefixTable(pattern: string): number[] {
    const m = pattern.length;
    const pi = new Array<number>(m).fill(0);
    let k = 0;
    for (let i = 1; i < m; i++) {
      while (k > 0 && pattern[k] !== pattern[i]) k = pi[k - 1];
      if (pattern[k] === pattern[i]) k++;
      pi[i] = k;
    }
    return pi;
  }

  /**
   * Cari semua kemunculan pattern dalam text (case-insensitive).
   * Return list of { index, match }.
   */
  search(text: string, pattern: string, caseInsensitive = true): Array<{ index: number; match: string }> {
    if (!pattern) return [];
    const T = caseInsensitive ? text.toLowerCase() : text;
    const P = caseInsensitive ? pattern.toLowerCase() : pattern;
    const pi = this.buildPrefixTable(P);
    const results: Array<{ index: number; match: string }> = [];
    let q = 0;
    for (let i = 0; i < T.length; i++) {
      while (q > 0 && P[q] !== T[i]) q = pi[q - 1];
      if (P[q] === T[i]) q++;
      if (q === P.length) {
        results.push({ index: i - P.length + 1, match: text.slice(i - P.length + 1, i + 1) });
        q = pi[q - 1];
      }
    }
    return results;
  }

  /**
   * Filter list of records: keep only those where any field contains pattern.
   */
  filter<T extends Record<string, unknown>>(records: T[], pattern: string, fields: (keyof T)[]): T[] {
    if (!pattern) return records;
    const lowered = pattern.toLowerCase();
    return records.filter((record) =>
      fields.some((f) => {
        const value = record[f];
        return typeof value === "string" && value.toLowerCase().includes(lowered);
      }),
    );
  }
}
