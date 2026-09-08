const { normalizePhone, maskPhone } = require('../dist/utils/phone');

describe('phone utilities', () => {
  test('normalizes Indonesian local numbers to E.164', () => {
    expect(normalizePhone('0812-3456-7890')).toBe('+6281234567890');
  });

  test('rejects invalid phone numbers', () => {
    expect(() => normalizePhone('123')).toThrow();
  });

  test('masks all but the final four digits', () => {
    const masked = maskPhone('+6281234567890');
    expect(masked.endsWith('7890')).toBe(true);
    expect(masked).not.toContain('8123456');
  });
});
