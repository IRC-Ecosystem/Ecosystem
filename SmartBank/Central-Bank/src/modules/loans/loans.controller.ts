import { Body, Controller, Get, Param, Post, Req } from '@nestjs/common';
import { Request } from 'express';
import { CurrentUser, RequestUser } from '../../common/current-user.decorator';
import { requireIdempotencyKey, requestHash, requestId } from '../../common/request-utils';
import { MoneyService } from '../money/money.service';
import { SettlementService } from '../settlement/settlement.service';
import { WalletAccountService } from '../wallets/wallet-account.service';
import { ApplyLoanDto, RepayLoanDto } from './dto';
import { Roles } from '../../common/roles.decorator';
import { UserRole } from '@prisma/client';
import { LoanService } from './loan.service';

@Controller('loans')
@Roles(UserRole.WALLET_USER)
export class LoansController {
  constructor(
    private readonly settlement: SettlementService,
    private readonly wallets: WalletAccountService,
    private readonly money: MoneyService,
    private readonly loans: LoanService,
  ) {}

  /**
   * GET /loans/me — list active loans (PENDING/DISBURSED/PARTIAL_PAID) untuk user saat ini.
   * Frontend pakai ini untuk render card list sehingga Nasabah tidak perlu input loanId manual.
   */
  @Get('me')
  async myLoans(@CurrentUser() user: RequestUser) {
    const wallet = await this.wallets.getPrimaryWallet(user.sub);
    return this.loans.listLoansForWallet(wallet.id);
  }

  /**
   * GET /loans/me/limit — cap 100.000, outstanding saat ini, remaining.
   * Frontend pakai ini untuk progress bar di card "Limit Pinjaman".
   */
  @Get('me/limit')
  async myLimit(@CurrentUser() user: RequestUser) {
    const wallet = await this.wallets.getPrimaryWallet(user.sub);
    return this.loans.getLoanLimitInfo(wallet.id);
  }

  @Post('apply')
  async apply(@Body() dto: ApplyLoanDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    const wallet = await this.wallets.getPrimaryWallet(user.sub);
    return this.loans.applyLoan({
      borrowerWalletId: wallet.id,
      amount: this.money.parse(dto.amount),
      idempotency: {
        key: requireIdempotencyKey(req),
        route: 'POST /api/v1/loans/apply',
        actorId: user.sub,
        requestHash: requestHash({ amount: dto.amount }),
      },
    });
  }

  @Post(':id/repay')
  repay(@Param('id') id: string, @Body() dto: RepayLoanDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.settlement.settleLoanRepayment({
      loanId: id,
      amount: this.money.parse(dto.amount),
      actorUserId: user.sub,
      requestId: requestId(req),
      idempotency: {
        key: requireIdempotencyKey(req),
        route: `POST /api/v1/loans/${id}/repay`,
        actorId: user.sub,
        requestHash: requestHash({ id, ...dto }),
      },
    });
  }
}
