import { CanActivate, ExecutionContext, Injectable, UnauthorizedException } from '@nestjs/common';

/**
 * Guard sederhana untuk endpoint service-to-service.
 * H3: hanya terima SERVICE_TOKEN (tidak lagi fallback ke JWT_SECRET).
 * Kunci signing JWT tidak boleh dikirim via header HTTP antar service.
 *
 * Dipakai oleh Wallet → Central-Bank untuk lookup wallet berdasarkan userId
 * saat flow login. Tidak menggantikan otentikasi user; caller harus tahu userId.
 */
@Injectable()
export class ServiceTokenGuard implements CanActivate {
  canActivate(context: ExecutionContext): boolean {
    const req = context.switchToHttp().getRequest<{ headers: Record<string, string | string[] | undefined> }>();
    const auth = req.headers['authorization'];
    const headerToken = Array.isArray(auth) ? auth[0] : auth;
    const presented = headerToken && headerToken.startsWith('Bearer ')
      ? headerToken.substring(7).trim()
      : null;
    const expected = process.env.SERVICE_TOKEN;
    if (!expected || !presented || presented !== expected) {
      throw new UnauthorizedException('Service token tidak valid');
    }
    return true;
  }
}