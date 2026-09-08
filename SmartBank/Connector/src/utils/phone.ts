import { parsePhoneNumberFromString } from 'libphonenumber-js';
import { AppError } from '../middleware/errorHandler';

export function normalizePhone(input: string): string {
  const phone = parsePhoneNumberFromString(input, 'ID');

  if (!phone?.isValid()) {
    throw new AppError(400, 'INVALID_PHONE_FORMAT', 'Format nomor HP tidak valid');
  }

  return phone.number;
}

export function maskPhone(input: string): string {
  const normalized = normalizePhone(input);
  return `${normalized.slice(0, 3)}${'*'.repeat(Math.max(0, normalized.length - 7))}${normalized.slice(-4)}`;
}
