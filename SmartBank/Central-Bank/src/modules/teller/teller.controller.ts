import { Controller, Get, Post, Body, Req, Query, Param } from '@nestjs/common';
import { TellerService } from './teller.service';
import { TellerActionDto, KycActionDto, KycApprovalDto, KycRejectionDto } from './dto';
import { Roles } from '../../common/roles.decorator';
import { UserRole } from '@prisma/client';
import { RequestUser, CurrentUser } from '../../common/current-user.decorator';
import { Request } from 'express';
import { requestId, requireIdempotencyKey } from '../../common/request-utils';
import { MoneyService } from '../money/money.service';

@Controller('teller')
@Roles(UserRole.TELLER, UserRole.MANAGER)
export class TellerController {
  constructor(
    private readonly teller: TellerService,
    private readonly money: MoneyService,
  ) {}

  @Get('customer')
  async findCustomer(@Query('query') query: string) {
    return this.teller.findCustomer(query);
  }

  @Post('kyc/verify')
  async verifyKyc(@Body() dto: KycActionDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.teller.verifyKyc({
      userId: dto.userId,
      actorUserId: user.sub,
      requestId: requestId(req),
      reasonCode: dto.reasonCode,
    });
  }

  @Post('top-up')
  async topUp(@Body() dto: TellerActionDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.teller.topUp({
      userId: dto.userId,
      amount: this.money.parse(dto.amount),
      actorUserId: user.sub,
      requestId: requestId(req),
      idempotencyKey: requireIdempotencyKey(req),
      reasonCode: dto.reasonCode,
    });
  }

  @Post('withdraw')
  async withdraw(@Body() dto: TellerActionDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.teller.withdraw({
      userId: dto.userId,
      amount: this.money.parse(dto.amount),
      actorUserId: user.sub,
      requestId: requestId(req),
      idempotencyKey: requireIdempotencyKey(req),
      reasonCode: dto.reasonCode,
    });
  }

  @Post('kyc/approve')
  async approveKyc(@Body() dto: KycApprovalDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.teller.approveKyc({
      userId: dto.userId,
      approvedRole: dto.approvedRole,
      actorUserId: user.sub,
      requestId: requestId(req),
    });
  }

  @Post('kyc/reject')
  async rejectKyc(@Body() dto: KycRejectionDto, @Req() req: Request, @CurrentUser() user: RequestUser) {
    return this.teller.rejectKyc({
      userId: dto.userId,
      reasonCode: dto.reasonCode,
      actorUserId: user.sub,
      requestId: requestId(req),
    });
  }

  /**
   * GET /teller/loans/pending?max_amount=50000
   * List pinjaman kecil (≤ max_amount, default 50.000) yang BELUM di-rekomendasikan.
   * Teller screening → POST /teller/loans/:id/recommend
   * Loan > max_amount TIDAK muncul di sini (langsung antrean Manager).
   */
  @Get('loans/pending')
  async pendingLoans(@Query('max_amount') maxAmount?: string) {
    const parsed = maxAmount ? this.money.parse(maxAmount) : TellerService.SMALL_LOAN_THRESHOLD;
    return this.teller.listSmallPendingLoans(parsed);
  }

  /**
   * POST /teller/loans/:id/recommend
   * Teller screening: tandai loan sebagai "sudah direkomendasikan".
   * TIDAK langsung disburse. Manager final-approve via /api/v1/manager/loans/:id/approve.
   */
  @Post('loans/:id/recommend')
  async recommendLoan(
    @Param('id') loanId: string,
    @Body() body: { note?: string },
    @Req() req: Request,
    @CurrentUser() user: RequestUser,
  ) {
    return this.teller.recommendLoan({
      loanId,
      tellerId: user.sub,
      requestId: requestId(req),
      note: body?.note,
    });
  }
}
