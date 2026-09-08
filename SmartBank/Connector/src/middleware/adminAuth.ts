import crypto from 'crypto';
import { NextFunction, Request, Response } from 'express';
import { env } from '../config/env';
import { AppError } from './errorHandler';

export function adminAuth(req: Request, _res: Response, next: NextFunction) {
  const remoteIp = (req.ip || '').replace(/^::ffff:/, '');
  const allowedIps = env.ADMIN_ALLOWED_IPS.split(',').map((value) => value.trim().replace(/^::ffff:/, '')).filter(Boolean);
  if (!allowedIps.some((value) => value === remoteIp || (value.endsWith('*') && remoteIp.startsWith(value.slice(0, -1))))) {
    return next(new AppError(403, 'ADMIN_IP_FORBIDDEN', 'Alamat IP tidak diizinkan untuk admin Connector'));
  }
  const presented = req.header('x-admin-api-key') ?? req.header('authorization')?.replace(/^Bearer\s+/i, '');
  const expected = env.ADMIN_API_KEY;
  if (!presented) return next(new AppError(401, 'ADMIN_UNAUTHORIZED', 'Admin API key wajib dikirim'));
  const left = Buffer.from(presented);
  const right = Buffer.from(expected);
  if (left.length !== right.length || !crypto.timingSafeEqual(left, right)) {
    return next(new AppError(401, 'ADMIN_UNAUTHORIZED', 'Admin API key tidak valid'));
  }
  return next();
}
