import { Module } from '@nestjs/common';
import { AuditModule } from '../audit/audit.module';
import { FeesModule } from '../fees/fees.module';
import { IdempotencyModule } from '../idempotency/idempotency.module';
import { LedgerModule } from '../ledger/ledger.module';
import { MoneyModule } from '../money/money.module';
import { WalletsModule } from '../wallets/wallets.module';
import { SettlementService } from './settlement.service';
import { TransfersController } from './transfers.controller';
import { InternalSettlementController } from './internal-settlement.controller';
import { ServiceTokenGuard } from '../../common/service-token.guard';

@Module({
  imports: [AuditModule, FeesModule, IdempotencyModule, LedgerModule, MoneyModule, WalletsModule],
  controllers: [TransfersController, InternalSettlementController],
  providers: [SettlementService, ServiceTokenGuard],
  exports: [SettlementService],
})
export class SettlementModule {}
