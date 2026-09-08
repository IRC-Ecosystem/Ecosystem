import { Body, Controller, Get, Param, Post, Put, Query, Req } from '@nestjs/common';
import { Request } from 'express';
import { CurrentUser, RequestUser } from '../../common/current-user.decorator';
import { requireIdempotencyKey, requestHash, requestId } from '../../common/request-utils';
import { SettlementService } from '../settlement/settlement.service';
import { MoneyService } from '../money/money.service';
import { Roles } from '../../common/roles.decorator';
import { UserRole } from '@prisma/client';
import { BurnDto, FeeConfigurationDto, IssuanceDto, ReversalDto } from './dto';
import { MonetaryPolicyService } from './monetary-policy.service';

@Controller('central-bank')
@Roles(UserRole.CENTRAL_BANK_ADMIN)
export class CentralBankController {
  constructor(
    private readonly monetary: MonetaryPolicyService,
    private readonly settlement: SettlementService,
    private readonly money: MoneyService,
  ) {}

  @Get('supply')
  supply() {
    return this.monetary.assertSupplyInvariant();
  }

  @Get('ledger')
  ledger(
    @Query('account_id') accountId?: string,
    @Query('transaction_id') transactionId?: string,
    @Query('from') from?: string,
    @Query('to') to?: string,
  ) {
    return this.monetary.ledger({ accountId, transactionId, from, to });
  }

  @Get('wallets')
  wallets(@Query('account_type') accountType?: string, @Query('search') search?: string) {
    return this.monetary.listWallets({ accountType, search });
  }

  @Get('transactions')
  transactions(@Query('limit') limit?: string) {
    return this.monetary.listTransactions({ limit: limit ? Number(limit) : undefined });
  }

  @Post('reversals')
  reversal(@Body() dto: ReversalDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.settlement.reverseTransaction({
      originalTransactionId: dto.original_transaction_id,
      reasonCode: dto.reason_code,
      actorUserId: user.sub,
      requestId: requestId(req),
      idempotency: {
        key: requireIdempotencyKey(req),
        route: 'POST /api/v1/central-bank/reversals',
        actorId: user.sub,
        requestHash: requestHash(dto),
      },
    });
  }

  @Post('issuance')
  issuance(@Body() dto: IssuanceDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    const amount = this.money.parse(dto.amount);
    return this.settlement.issueCurrency({
      targetWalletId: dto.target_wallet_id,
      amount,
      reasonCode: dto.reason_code,
      note: dto.note,
      actorUserId: user.sub,
      requestId: requestId(req),
      idempotency: {
        key: requireIdempotencyKey(req),
        route: 'POST /api/v1/central-bank/issuance',
        actorId: user.sub,
        requestHash: requestHash(dto),
      },
    });
  }

  @Post('burn')
  burn(@Body() dto: BurnDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    const amount = this.money.parse(dto.amount);
    return this.settlement.burnCurrency({
      sourceWalletId: dto.source_wallet_id,
      amount,
      reasonCode: dto.reason_code,
      note: dto.note,
      actorUserId: user.sub,
      requestId: requestId(req),
      idempotency: {
        key: requireIdempotencyKey(req),
        route: 'POST /api/v1/central-bank/burn',
        actorId: user.sub,
        requestHash: requestHash(dto),
      },
    });
  }

  @Get('fees')
  fees() {
    return this.monetary.getFeeConfigurations();
  }

  @Get('fees/:type')
  fee(@Param('type') type: string) {
    return this.monetary.getFeeConfiguration(type);
  }

  @Put('fees')
  upsertFee(@Body() dto: FeeConfigurationDto, @CurrentUser() user: RequestUser) {
    return this.monetary.upsertFeeConfiguration({
      type: dto.type,
      mode: dto.mode,
      value: dto.value,
      minFee: dto.min_fee,
      maxFee: dto.max_fee,
      isActive: dto.is_active,
      updatedBy: user.sub,
    });
  }

  @Get('audit-logs')
  auditLogs(
    @Query('search') search?: string,
    @Query('service_name') serviceName?: string,
    @Query('limit') limit?: string,
  ) {
    return this.monetary.listAuditLogs({
      search,
      serviceName,
      limit: limit ? Number(limit) : undefined,
    });
  }
}
