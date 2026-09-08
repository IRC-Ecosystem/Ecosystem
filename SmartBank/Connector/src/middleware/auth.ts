import { Request, Response, NextFunction } from 'express';
import { AppError } from './errorHandler';
import { prisma } from '../lib/prisma';
import * as bcrypt from 'bcrypt';


export async function authMiddleware(req: Request, res: Response, next: NextFunction) {
  try {
    const authHeader = req.headers.authorization;
    if (!authHeader?.startsWith('Bearer ')) {
      throw new AppError(401, 'UNAUTHORIZED', 'Missing or invalid Authorization header');
    }

    const key = authHeader.split(' ')[1];
    if (key.length < 32) {
      throw new AppError(401, 'UNAUTHORIZED', 'Invalid API key format');
    }

    const prefix = key.substring(0, 8);
    const apiKeyRecord = await prisma.serviceApiKey.findUnique({
      where: { key_prefix: prefix },
      include: { service: true },
    });

    if (!apiKeyRecord || apiKeyRecord.status !== 'ACTIVE') {
      throw new AppError(401, 'UNAUTHORIZED', 'Invalid API key');
    }

    if (apiKeyRecord.expires_at && apiKeyRecord.expires_at < new Date()) {
      throw new AppError(401, 'UNAUTHORIZED', 'API key expired');
    }

    const match = await bcrypt.compare(key, apiKeyRecord.key_hash);
    if (!match) {
      throw new AppError(401, 'UNAUTHORIZED', 'Invalid API key');
    }

    if (apiKeyRecord.service.status !== 'ACTIVE') {
      throw new AppError(403, 'FORBIDDEN', 'Service is suspended');
    }

    // Attach to request
    (req as any).service = apiKeyRecord.service;
    
    // async update last used
    prisma.serviceApiKey.update({
      where: { id: apiKeyRecord.id },
      data: { last_used_at: new Date() }
    }).catch(console.error);

    next();
  } catch (error) {
    next(error);
  }
}
