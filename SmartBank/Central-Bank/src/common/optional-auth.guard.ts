import { CanActivate, ExecutionContext, Injectable } from '@nestjs/common';
import { Reflector } from '@nestjs/core';
import { JwtService } from '@nestjs/jwt';
import { Request } from 'express';
import { IS_PUBLIC_KEY } from './public.decorator';
import { AppError } from './app-error';
import { ErrorCode } from './error-codes';

@Injectable()
export class OptionalAuthGuard implements CanActivate {
  constructor(
    private readonly reflector: Reflector,
    private readonly jwt: JwtService,
  ) {}

  canActivate(context: ExecutionContext): boolean {
    const isPublic = this.reflector.getAllAndOverride<boolean>(IS_PUBLIC_KEY, [
      context.getHandler(),
      context.getClass(),
    ]);
    const req = context.switchToHttp().getRequest<Request & { user?: unknown }>();
    const header = req.header('authorization');
    if (!header) {
      if (isPublic) return true;
      throw new AppError(ErrorCode.UNAUTHORIZED, 'Authorization token wajib dikirim');
    }
    const [, token] = header.split(' ');
    // Jika token tidak berformat JWT (tidak ada 2 segment dipisah '.'),
    // biarkan ServiceTokenGuard/controller guard lain yang memvalidasi.
    // OptionalAuthGuard hanya memvalidasi token JWT user.
    if (token && token.split('.').length !== 3) {
      if (isPublic) return true;
      // Bukan JWT dan bukan public → teruskan; guard berikutnya akan menolak
      // kalau tidak diizinkan.
      return true;
    }
    try {
      req.user = this.jwt.verify(token);
      return true;
    } catch {
      throw new AppError(ErrorCode.UNAUTHORIZED, 'Token tidak valid');
    }
  }
}