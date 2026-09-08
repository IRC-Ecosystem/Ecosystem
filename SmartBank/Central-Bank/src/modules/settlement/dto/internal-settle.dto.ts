import { IsNotEmpty, IsObject, IsOptional, IsString, MaxLength, Matches } from 'class-validator';

export class InternalSettleDto {
  @IsString()
  @IsNotEmpty()
  @MaxLength(36)
  payer_wallet_id!: string;

  @IsString()
  @IsNotEmpty()
  @MaxLength(36)
  payee_wallet_id!: string;

  @IsString()
  @IsNotEmpty()
  @Matches(/^\d+$/)
  gross_amount!: string;

  @IsString()
  @IsNotEmpty()
  @MaxLength(6)
  @Matches(/^\d{6}$/)
  pin!: string;

  @IsString()
  @IsNotEmpty()
  @MaxLength(64)
  source_app!: string;

  @IsOptional()
  @IsString()
  @MaxLength(512)
  description?: string;

  @IsOptional()
  @IsString()
  @MaxLength(191)
  external_ref_id?: string;

  @IsOptional()
  @IsObject()
  metadata?: Record<string, unknown>;
}

export class InternalReverseDto {
  @IsString()
  @IsNotEmpty()
  @MaxLength(191)
  original_transaction_id!: string;

  @IsString()
  @IsNotEmpty()
  @MaxLength(128)
  reason_code!: string;
}
