/**
 * Account Number Generator.
 *
 * Format: 10 digit numerik dengan Luhn (mod-10) checksum di digit terakhir.
 * - 9 digit pertama: random dari crypto random source
 * - digit ke-10: checksum agar typo bisa terdeteksi saat input
 *
 * Contoh: 1234567894 (digit terakhir = checksum)
 *
 * Wallet ID (UUID) tetap jadi primary key internal untuk ledger & settlement.
 * Account Number adalah public-facing identifier untuk display & input transfer.
 */

import { randomInt } from 'crypto';

/**
 * Compute Luhn checksum digit for the given digits.
 * @param digits string of digits (without checksum)
 * @returns single checksum digit (0-9)
 */
export function luhnChecksum(digits: string): number {
  let sum = 0;
  // Process from rightmost, double every second digit
  for (let i = digits.length - 1, pos = 0; i >= 0; i--, pos++) {
    let d = parseInt(digits[i], 10);
    if (pos % 2 === 0) {
      d *= 2;
      if (d > 9) d -= 9;
    }
    sum += d;
  }
  return (10 - (sum % 10)) % 10;
}

/**
 * Generate a new 10-digit account number with valid Luhn checksum.
 * First digit cannot be 0 (avoids leading-zero confusion).
 */
export function generateAccountNumber(): string {
  const nine = String(randomInt(100000000, 1000000000)); // 9 digits, leading non-zero
  const check = luhnChecksum(nine);
  return nine + String(check);
}

/**
 * Validate an account number string:
 * - Must be exactly 10 digits
 * - Must have a valid Luhn checksum
 * Returns true if valid, false otherwise.
 */
export function isValidAccountNumber(value: string): boolean {
  if (!/^\d{10}$/.test(value)) return false;
  const body = value.slice(0, 9);
  const expected = luhnChecksum(body);
  return parseInt(value[9], 10) === expected;
}

/**
 * Format account number for human display: "1234-5678-90"
 * Strips any non-digit chars from input first.
 */
export function formatAccountNumber(value: string): string {
  const digits = value.replace(/\D/g, '');
  if (digits.length !== 10) return value;
  return `${digits.slice(0, 4)}-${digits.slice(4, 8)}-${digits.slice(8, 10)}`;
}
