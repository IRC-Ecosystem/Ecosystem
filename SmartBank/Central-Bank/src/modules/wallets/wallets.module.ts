import { Module } from '@nestjs/common';
import { WalletAccountService } from './wallet-account.service';
import { InternalUsersController, WalletsController, UsersController } from './wallets.controller';
import { ServiceTokenGuard } from '../../common/service-token.guard';
import { AuditModule } from '../audit/audit.module';

@Module({
  imports: [AuditModule],
  controllers: [WalletsController, UsersController, InternalUsersController],
  providers: [WalletAccountService, ServiceTokenGuard],
  exports: [WalletAccountService],
})
export class WalletsModule {}