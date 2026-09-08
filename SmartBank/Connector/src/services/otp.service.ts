import { prisma } from '../lib/prisma';
import { CentralBankClient } from '../integrations/centralbank.client';
import { AppError } from '../middleware/errorHandler';
import { env } from '../config/env';
import * as bcrypt from 'bcrypt';
import crypto from 'crypto';


function generateCode(): string {
  return crypto.randomInt(100000, 1000000).toString();
}

export class OtpService {
  static async requestOtp(phone: string, serviceId: string, serviceName: string) {
    // limit check: 3 per hour per phone
    const anHourAgo = new Date(Date.now() - 3600000);
    const recent = await prisma.otpRequest.count({
      where: { phone, created_at: { gt: anHourAgo } },
    });
    if (recent >= 3) {
      throw new AppError(429, 'RATE_LIMITED', 'Max 3 OTP requests per phone per hour');
    }

    const user = await CentralBankClient.lookupPhone(phone);
    if (!user) {
      throw new AppError(404, 'USER_NOT_FOUND', 'Nomor HP belum terdaftar di SmartBank. Silakan daftar dulu di aplikasi SmartBank Wallet.');
    }

    const code = generateCode();
    const hash = await bcrypt.hash(code, 10);
    const reqId = `otpreq_${crypto.randomBytes(16).toString('hex')}`;
    const expiresAt = new Date(Date.now() + 300000); // 5 mins

    await prisma.otpRequest.create({
      data: {
        request_id: reqId,
        service_id: serviceId,
        phone,
        purpose: 'WALLET_LINK',
        code_hash: hash,
        expires_at: expiresAt,
      }
    });

    await CentralBankClient.createNotification({
      user_id: user.user_id,
      type: 'OTP_LINKING_REQUESTED',
      source_app: serviceName,
      source_ref: reqId,
      title: 'Permintaan OTP',
      body: `Kode OTP untuk menghubungkan ${serviceName} adalah ${code}. Berlaku 5 menit.`,
      payload: { otp_code: code, request_id: reqId, expires_at: expiresAt.toISOString() }
    });

    return {
      request_id: reqId,
      expires_at: expiresAt,
      ttl_seconds: 300,
      attempts_allowed: 3,
      phone_masked: user.phone_masked,
      hint: "Buka aplikasi SmartBank untuk melihat kode OTP di inbox"
    };
  }

  static async verifyOtp(reqId: string, code: string, serviceId: string) {
    const record = await prisma.otpRequest.findUnique({ where: { request_id: reqId } });
    if (!record || record.service_id !== serviceId) {
      throw new AppError(404, 'NOT_FOUND', 'OTP request not found');
    }

    if (record.status === 'BLOCKED') throw new AppError(403, 'OTP_BLOCKED', 'OTP diblokir');
    if (record.status === 'CONSUMED') throw new AppError(410, 'OTP_EXPIRED', 'OTP sudah digunakan');
    if (record.expires_at < new Date()) {
      await prisma.otpRequest.update({ where: { id: record.id }, data: { status: 'EXPIRED' } });
      throw new AppError(410, 'OTP_EXPIRED', 'OTP sudah kedaluwarsa');
    }

    const match = await bcrypt.compare(code, record.code_hash);
    if (!match) {
      const attempts = record.attempts + 1;
      const status = attempts >= record.max_attempts ? 'BLOCKED' : 'PENDING';
      await prisma.otpRequest.update({ where: { id: record.id }, data: { attempts, status } });
      if (status === 'BLOCKED') throw new AppError(403, 'OTP_BLOCKED', 'OTP diblokir karena terlalu banyak percobaan');
      throw new AppError(401, 'OTP_INVALID', 'Kode OTP salah');
    }

    await prisma.otpRequest.update({ where: { id: record.id }, data: { status: 'CONSUMED', consumed_at: new Date() } });
    const user = await CentralBankClient.lookupPhone(record.phone);

    // Generate a short-lived signed token for linkage.
    const jwt = require('jsonwebtoken');
    const token = jwt.sign({ 
      phone: record.phone, 
      smartbank_user_id: user.user_id,
      smartbank_wallet_id: user.wallet_id,
      kyc_tier: user.kyc_tier,
      service_id: serviceId
    }, env.JWT_SECRET, { expiresIn: '10m' });

    return {
      verified: true,
      phone: record.phone,
      smartbank_user_id: user.user_id,
      verification_token: token
    };
  }
}
