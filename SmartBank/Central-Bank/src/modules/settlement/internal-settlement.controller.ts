import { Body, Controller, Post, Req, UnauthorizedException, UseGuards } from '@nestjs/common';
import { Request } from 'express';
import { Public } from '../../common/public.decorator';
import { ServiceTokenGuard } from '../../common/service-token.guard';
import { SettlementService } from './settlement.service';
import { InternalReverseDto, InternalSettleDto } from './dto/internal-settle.dto';
import { requestHash, requestId, requireIdempotencyKey } from '../../common/request-utils';

@Controller('internal/payment-requests')
export class InternalSettlementController {
  constructor(private readonly settlement: SettlementService) {}

  @Public()
  @UseGuards(ServiceTokenGuard)
  @Post('settle')
  async settleViaConnector(@Body() body: InternalSettleDto, @Req() req: Request) {
    const delegatedUserId = req.header('x-delegated-user-id');
    if (!delegatedUserId) {
      throw new UnauthorizedException('Header X-Delegated-User-Id wajib dikirim');
    }
    const idemKey = requireIdempotencyKey(req);

    return this.settlement.settleViaConnector({
      payerWalletId: body.payer_wallet_id,
      payeeWalletId: body.payee_wallet_id,
      grossAmount: body.gross_amount,
      pin: body.pin,
      sourceApp: body.source_app,
      description: body.description ?? '',
      externalRefId: body.external_ref_id,
      metadata: body.metadata,
      idempotency: { key: idemKey, route: req.path, actorId: delegatedUserId, requestHash: requestHash(body) },
      requestId: requestId(req),
      actorUserId: delegatedUserId,
    });
  }

  @Public()
  @UseGuards(ServiceTokenGuard)
  @Post('reverse')
  async reverseViaConnector(@Body() body: InternalReverseDto, @Req() req: Request) {
    const delegatedUserId = req.header('x-delegated-user-id');
    if (!delegatedUserId) {
      throw new UnauthorizedException('Header X-Delegated-User-Id wajib dikirim');
    }
    const idempotencyKey = requireIdempotencyKey(req);

    return this.settlement.reverseTransaction({
      originalTransactionId: body.original_transaction_id,
      reasonCode: body.reason_code,
      actorUserId: delegatedUserId,
      requestId: requestId(req),
      idempotency: {
        key: idempotencyKey,
        route: req.path,
        actorId: delegatedUserId,
        requestHash: requestHash(body),
      },
    });
  }
}
