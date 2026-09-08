import { Module } from '@nestjs/common';
import { JwtModule } from '@nestjs/jwt';
import { ConfigModule, ConfigService } from '@nestjs/config';
import { AuditModule } from '../audit/audit.module';
import { IdempotencyModule } from '../idempotency/idempotency.module';
import { SettlementModule } from '../settlement/settlement.module';
import { AuthController } from './auth.controller';
import { AuthService } from './auth.service';

@Module({
  imports: [
    JwtModule.registerAsync({
      imports: [ConfigModule],
      inject: [ConfigService],
      useFactory: (config: ConfigService) => {
        const secret = config.get<string>('JWT_SECRET') ?? '';
        // C1: hard-fail bila JWT_SECRET hilang, terlalu pendek, atau nilai default/ter-leaked.
        const KNOWN_BAD = [
          'change-me-for-development',
          'supersecretkey_change_me_2026',
          'laragon_local_dev_secret_min_32_chars_abcdef123456',
          'change_me_jwt_secret_min_32_chars',
        ];
        if (!secret || secret.length < 32 || KNOWN_BAD.includes(secret)) {
          throw new Error('JWT_SECRET wajib di-set, >=32 karakter, dan bukan nilai default/ter-leaked.');
        }
        return {
          secret,
          signOptions: {
            expiresIn: '1h',
            issuer: config.get<string>('JWT_ISSUER') || 'smartbank',
            audience: config.get<string>('JWT_AUDIENCE') || 'smartbank-clients',
          },
          verifyOptions: {
            issuer: config.get<string>('JWT_ISSUER') || 'smartbank',
            audience: config.get<string>('JWT_AUDIENCE') || 'smartbank-clients',
          },
        };
      },
    }),
    AuditModule,
    IdempotencyModule,
    SettlementModule,
  ],
  controllers: [AuthController],
  providers: [AuthService],
  exports: [JwtModule, AuthService],
})
export class AuthModule {}
