import { Module } from '@nestjs/common';
import { SettlementModule } from '../settlement/settlement.module';
import { MoneyModule } from '../money/money.module';
import { CentralBankController } from './central-bank.controller';
import { MonetaryPolicyService } from './monetary-policy.service';

@Module({
  imports: [SettlementModule, MoneyModule],
  controllers: [CentralBankController],
  providers: [MonetaryPolicyService],
  exports: [MonetaryPolicyService],
})
export class CentralBankModule {}
