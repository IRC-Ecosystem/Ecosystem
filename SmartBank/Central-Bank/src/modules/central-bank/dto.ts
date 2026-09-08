import { IsNotEmpty, IsOptional, IsString, MaxLength, Matches } from 'class-validator';

export class ReversalDto {
  @IsNotEmpty()
  @IsString()
  @MaxLength(191)
  original_transaction_id!: string;

  @IsNotEmpty()
  @IsString()
  @MaxLength(128)
  reason_code!: string;

  @IsOptional()
  @IsString()
  @MaxLength(500)
  note?: string;
}

export class IssuanceDto {
  @IsNotEmpty()
  @IsString()
  @MaxLength(36)
  target_wallet_id!: string;

  @IsNotEmpty()
  @IsString()
  @MaxLength(64)
  amount!: string;

  @IsNotEmpty()
  @IsString()
  @MaxLength(128)
  reason_code!: string;

  @IsOptional()
  @IsString()
  @MaxLength(500)
  note?: string;
}

export class BurnDto {
  @IsNotEmpty()
  @IsString()
  @MaxLength(36)
  source_wallet_id!: string;

  @IsNotEmpty()
  @IsString()
  @MaxLength(64)
  amount!: string;

  @IsNotEmpty()
  @IsString()
  @MaxLength(128)
  reason_code!: string;

  @IsOptional()
  @IsString()
  @MaxLength(500)
  note?: string;
}

export class FeeConfigurationDto {
  @IsNotEmpty()
  @IsString()
  @MaxLength(64)
  type!: string;

  @IsNotEmpty()
  @IsString()
  @MaxLength(16)
  @Matches(/^(FLAT|PERCENT)$/, { message: 'mode must be FLAT or PERCENT' })
  mode!: string;

  @IsNotEmpty()
  @IsString()
  @MaxLength(64)
  @Matches(/^\d+$/, { message: 'value must be a non-negative integer string' })
  value!: string;

  @IsOptional()
  @IsString()
  @MaxLength(64)
  @Matches(/^\d+$/, { message: 'min_fee must be a non-negative integer string' })
  min_fee?: string;

  @IsOptional()
  @IsString()
  @MaxLength(64)
  @Matches(/^\d+$/, { message: 'max_fee must be a non-negative integer string' })
  max_fee?: string;

  @IsOptional()
  is_active?: boolean;
}
