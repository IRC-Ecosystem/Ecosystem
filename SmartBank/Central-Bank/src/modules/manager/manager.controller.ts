import { Body, Controller, Get, Post, Query, Req } from '@nestjs/common';
import { ManagerService } from './manager.service';
import { ManagerUserActionDto, ManagerLoanActionDto } from './dto';
import { Roles } from '../../common/roles.decorator';
import { UserRole } from '@prisma/client';
import { RequestUser, CurrentUser } from '../../common/current-user.decorator';
import { Request } from 'express';
import { requestId, requireIdempotencyKey } from '../../common/request-utils';
import { MoneyService } from '../money/money.service';

@Controller('manager')
@Roles(UserRole.MANAGER)
export class ManagerController {
  constructor(
    private readonly manager: ManagerService,
    private readonly money: MoneyService,
  ) {}

  /**
   * GET /manager/loan-pool — saldo LOAN_POOL_ACCOUNT.
   * Manager wajib lihat ini sebelum approve, agar tidak approve loan yang
   * saldonya tidak tersedia di pool (settleLoanApproval akan reject anyway).
   */
  @Get('loan-pool')
  async loanPool() {
    return this.manager.getLoanPoolBalance();
  }

  /**
   * GET /manager/loans/pending
   * Query params (semua opsional, default = tampilkan semua PENDING):
   *   - min_amount: hanya tampilkan loan dengan principal >= min_amount
   *   - recommended: 'true' (sudah di-recommend Teller) | 'false' (belum)
   */
  @Get('loans/pending')
  async pendingLoans(
    @Query('min_amount') minAmount?: string,
    @Query('recommended') recommended?: string,
  ) {
    const filters: { minAmount?: bigint; recommendedOnly?: boolean } = {};
    if (minAmount) filters.minAmount = this.money.parse(minAmount);
    if (recommended === 'true') filters.recommendedOnly = true;
    else if (recommended === 'false') filters.recommendedOnly = false;
    return this.manager.listPendingLoans(filters);
  }

  @Post('users/suspend')
  async suspendUser(@Body() dto: ManagerUserActionDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.manager.suspendUser({
      userId: dto.userId,
      actorUserId: user.sub,
      requestId: requestId(req),
      reasonCode: dto.reasonCode,
    });
  }

  @Post('users/activate')
  async activateUser(@Body() dto: ManagerUserActionDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.manager.activateUser({
      userId: dto.userId,
      actorUserId: user.sub,
      requestId: requestId(req),
      reasonCode: dto.reasonCode,
    });
  }

  @Post('loans/approve')
  async approveLoan(@Body() dto: ManagerLoanActionDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.manager.approveLoan({
      loanId: dto.loanId,
      actorUserId: user.sub,
      requestId: requestId(req),
      idempotencyKey: requireIdempotencyKey(req),
      reasonCode: dto.reasonCode,
    });
  }

  @Post('loans/reject')
  async rejectLoan(@Body() dto: ManagerLoanActionDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.manager.rejectLoan({
      loanId: dto.loanId,
      actorUserId: user.sub,
      requestId: requestId(req),
      idempotencyKey: requireIdempotencyKey(req),
      reasonCode: dto.reasonCode,
    });
  }
}
