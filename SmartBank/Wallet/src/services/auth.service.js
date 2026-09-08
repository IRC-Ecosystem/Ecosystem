import bcrypt from 'bcryptjs';
import { v4 as uuidv4 } from 'uuid';
import { db } from '../config/database.js';
import { centralBankService } from './centralBank.service.js';
import { tokenService } from './token.service.js';
import { CustomError } from '../utils/errors.js';
import { config } from '../config/config.js';
import jwt from 'jsonwebtoken';

const normalizeIndonesianPhone = (phone) => {
  if (!phone) return null;
  const digits = String(phone).replace(/\D/g, '');
  const normalized = digits.startsWith('62')
    ? `+${digits}`
    : digits.startsWith('0')
      ? `+62${digits.slice(1)}`
      : `+62${digits}`;

  if (!/^\+628\d{8,12}$/.test(normalized)) {
    throw new CustomError('BAD_REQUEST', 'Format nomor telepon Indonesia tidak valid', 400);
  }
  return normalized;
};

export const authService = {

  // 1. REGISTER NEW USER
  register: async (name, email, phone, password, pin, role = 'RETAIL_CUSTOMER') => {
    // Basic formatting
    const cleanEmail = email.toLowerCase().trim();
    const cleanPhone = normalizeIndonesianPhone(phone);

    // Check if email already registered locally
    const emailCheck = await db.query('SELECT id FROM users WHERE email = $1', [cleanEmail]);
    if (emailCheck.rowCount > 0) {
      throw new CustomError('BAD_REQUEST', 'Email sudah terdaftar di sistem', 400);
    }

    // Check if phone already registered locally (if phone provided)
    if (cleanPhone) {
      const phoneCheck = await db.query('SELECT id FROM users WHERE phone = $1', [cleanPhone]);
      if (phoneCheck.rowCount > 0) {
        throw new CustomError('BAD_REQUEST', 'Nomor telepon sudah terdaftar di sistem', 400);
      }
    }

    // Call Central Bank Core to open account & disburse initial stimulus
    let walletInfo;
    try {
      walletInfo = await centralBankService.createAccount(name, cleanEmail, cleanPhone, password, pin);
    } catch (err) {
      console.error('❌ Gagal membuat wallet di CentralBank Core:', err.message);
      if (err instanceof CustomError) throw err;
      throw new CustomError('INTERNAL_SERVER_ERROR', 'Registrasi tidak dapat diproses oleh Central Bank', 500);
    }

    const { user_id: cbUserId, wallet_id: walletId, initial_balance: cbInitialBalance } = walletInfo;
    const initialBalance = cbInitialBalance ? parseInt(cbInitialBalance, 10) : 50000;

    const pinHash = bcrypt.hashSync(pin.toString(), 10);
    const passwordHash = bcrypt.hashSync(password, 10);

    // Register always creates a WALLET_USER — role from form is ignored for public registration
    const dbRole = 'WALLET_USER';

    // Always INSERT (or UPDATE if exists) — ON DUPLICATE KEY handles re-registration
    await db.query(
      `INSERT INTO users (id, name, email, phone, password_hash, pin_hash, kyc_tier, status, role, updated_at)
       VALUES (?, ?, ?, ?, ?, ?, 'BASIC', 'ACTIVE', ?, CURRENT_TIMESTAMP(3))
       ON DUPLICATE KEY UPDATE
         name=VALUES(name), phone=VALUES(phone),
         password_hash=VALUES(password_hash), pin_hash=VALUES(pin_hash),
         updated_at=CURRENT_TIMESTAMP(3)`,
      [cbUserId, name, cleanEmail, cleanPhone, passwordHash, pinHash, dbRole]
    );

    // Sync the read-model cache (wallet_accounts_cache table)
    await db.query('DELETE FROM wallet_accounts_cache WHERE wallet_id = ?', [walletId]);
    await db.query(
      'INSERT INTO wallet_accounts_cache (wallet_id, user_id, available_balance, currency) VALUES (?, ?, ?, ?)',
      [walletId, cbUserId, initialBalance, 'CBDC_IDR']
    );

    return {
      userId: cbUserId,
      name,
      email: cleanEmail,
      walletId,
      initialBalance,
      role: dbRole
    };
  },

  // 2. LOGIN USER
  login: async (email, password) => {
    const cleanEmail = email.toLowerCase().trim();

    // Fetch user from MySQL
    const result = await db.query('SELECT * FROM users WHERE email = ?', [cleanEmail]);

    if (result.rowCount === 0) {
      // User mungkin terdaftar di Central Bank tetapi belum tersimpan lokal.
      try {
          console.log(`🔄 [AUTH] Fallback ke Central Bank untuk: ${cleanEmail}`);
          const cbResponse = await fetch(`${config.centralBank.url}/api/v1/auth/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: cleanEmail, password })
          });

          if (!cbResponse.ok) {
            throw new CustomError('UNAUTHORIZED', 'Email atau password yang Anda masukkan salah', 401);
          }

          const cbData = await cbResponse.json();
          // CB login response: { access_token, user_id, name, role, kyc_tier, wallet_id }
          const cbPayload = cbData.data || cbData;
          const cbToken = cbPayload.access_token;

          // Decode JWT as fallback
          let userId = cbPayload.user_id;
          let userRole = cbPayload.role || 'RETAIL_CUSTOMER';
          let kycTier = cbPayload.kyc_tier || 'BASIC';
          let userName = cbPayload.name;
          let walletId = cbPayload.wallet_id;
          let tokenVerified = false;

          // Fallback: verify CB JWT (M11: use jwt.verify, not jwt.decode).
          // CB dan Wallet berbagi JWT_SECRET yang sama — signature bisa diverifikasi.
          if (cbToken) {
            try {
              const decoded = jwt.verify(cbToken, config.jwt.secret, {
                issuer: config.jwt.issuer,
                audience: config.jwt.audience,
              });
              if (decoded) {
                tokenVerified = true;
                userId = decoded.sub || decoded.userId;
                userRole = decoded.role || userRole;
                if (!userName) userName = decoded.name;
                if (!walletId) walletId = decoded.walletId;
              }
            } catch (e) { /* ignore — fall through ke defaults */ }
          }

          if (!tokenVerified || !userId || !userName) {
            throw new CustomError('INVALID_UPSTREAM_RESPONSE', 'Respons autentikasi Central Bank tidak valid', 502);
          }
          // Fallback path: staff (TELLER/MANAGER/CENTRAL_BANK_ADMIN) tidak punya
          // customer wallet, lewati lookup. Retail tetap wajib punya wallet.
          const isStaffFallback = ['TELLER', 'MANAGER', 'CENTRAL_BANK_ADMIN'].includes(userRole);
          if (!isStaffFallback) {
            let cbWalletId = null;
            try {
              const cbWallet = await centralBankService.getWalletByUserId(userId);
              cbWalletId = cbWallet.wallet_id ?? cbWallet.walletId ?? null;
            } catch (e) { /* ignore; will be handled below */ }
            if (!cbWalletId) {
              throw new CustomError('NOT_REGISTERED', 'Wallet belum terdaftar di Central-Bank. Silakan hubungi Teller.', 403);
            }
            walletId = cbWalletId;
          } else {
            walletId = null;
          }
          if (userRole === 'WALLET_USER') userRole = 'RETAIL_CUSTOMER';

          console.log(`✅ [AUTH] Fallback berhasil: ${cleanEmail} (role: ${userRole}, wallet: ${walletId})`);

          const tokens = tokenService.generateTokens({ userId, name: userName, email: cleanEmail, phone: null, walletId, role: userRole });

          return {
            user: { id: userId, name: userName, email: cleanEmail, phone: null, kycTier, walletId, role: userRole, status: 'ACTIVE' },
            ...tokens
          };
      } catch (fallbackErr) {
        if (fallbackErr instanceof CustomError) throw fallbackErr;
        throw new CustomError('CENTRAL_BANK_UNAVAILABLE', 'Central Bank tidak dapat memverifikasi akun', 503);
      }
    }

    const user = result.rows[0];

    // Check status
    if (user.status !== 'ACTIVE') {
      throw new CustomError('UNAUTHORIZED', 'Akun Anda sedang dibekukan (SUSPENDED). Silakan hubungi Provider Admin.', 401);
    }

    // Verify Password
    const passwordMatch = bcrypt.compareSync(password, user.password_hash);
    if (!passwordMatch) {
      throw new CustomError('UNAUTHORIZED', 'Email atau password yang Anda masukkan salah', 401);
    }

    // Fetch associated Wallet ID from Central-Bank (SSOT), with read-cache fallback.
    // STAFF (TELLER/MANAGER/CENTRAL_BANK_ADMIN) tidak punya customer wallet —
    // mereka user internal untuk operasional, jadi walletId=null diperbolehkan.
    const isStaff = ['TELLER', 'MANAGER', 'CENTRAL_BANK_ADMIN'].includes(user.role);
    let walletId = null;
    if (!isStaff) {
      try {
        const cbWallet = await centralBankService.getWalletByUserId(user.id);
        walletId = cbWallet.wallet_id ?? cbWallet.walletId ?? null;
      } catch (e) {
        // Central-Bank unreachable or no wallet exists. Distinguish:
        //   - 4xx  => user is not registered in CB yet. Fall through to NOT_REGISTERED below.
        //   - 5xx / network => propagate as 503 from caller.
        if (e?.status && e.status >= 400 && e.status < 500) {
          throw new CustomError('NOT_REGISTERED', 'Wallet belum terdaftar di Central-Bank. Silakan hubungi Teller.', 403);
        }
        throw e;
      }
      if (!walletId) {
        throw new CustomError('NOT_REGISTERED', 'Wallet belum terdaftar di Central-Bank. Silakan hubungi Teller.', 403);
      }
    }

    // Generate JWT access & refresh tokens
    const tokens = tokenService.generateTokens({
      userId: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
      walletId: walletId,
      role: user.role
    });

    return {
      user: {
        id: user.id,
        name: user.name,
        email: user.email,
        phone: user.phone,
        kycTier: user.kyc_tier,
        walletId,
        role: user.role,
        status: user.status
      },
      ...tokens
    };
  }
};
