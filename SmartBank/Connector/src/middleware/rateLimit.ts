import { NextFunction, Request, Response } from 'express';
import { AppError } from './errorHandler';

type RateLimitOptions = {
  windowMs: number;
  limit: number;
  key?: (req: Request) => string;
};

export function createRateLimiter(options: RateLimitOptions) {
  const counters = new Map<string, { count: number; resetAt: number }>();
  return (req: Request, res: Response, next: NextFunction) => {
    const now = Date.now();
    const serviceId = (req as any).service?.id ?? req.ip;
    const key = options.key?.(req) ?? String(serviceId);
    const current = counters.get(key);
    const entry = !current || current.resetAt <= now
      ? { count: 1, resetAt: now + options.windowMs }
      : { count: current.count + 1, resetAt: current.resetAt };
    counters.set(key, entry);

    res.setHeader('RateLimit-Limit', String(options.limit));
    res.setHeader('RateLimit-Remaining', String(Math.max(0, options.limit - entry.count)));
    res.setHeader('RateLimit-Reset', String(Math.ceil(entry.resetAt / 1000)));

    if (entry.count > options.limit) {
      return next(new AppError(429, 'RATE_LIMITED', 'Terlalu banyak permintaan. Silakan coba lagi nanti.'));
    }

    if (counters.size > 10_000) {
      for (const [counterKey, value] of counters) if (value.resetAt <= now) counters.delete(counterKey);
    }
    return next();
  };
}
