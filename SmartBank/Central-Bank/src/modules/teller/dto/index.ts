import { IsString, IsNotEmpty, Matches, IsOptional, MaxLength } from 'class-validator';

export class TellerActionDto {
  @IsString()
  @IsNotEmpty()
  @MaxLength(191)
  userId: string;

  @IsString()
  @IsNotEmpty()
  @Matches(/^\d+$/, { message: 'Amount must be a numeric string' })
  @MaxLength(30)
  amount: string;

  @IsOptional()
  @IsString()
  @MaxLength(128)
  reasonCode?: string;
}

export class KycActionDto {
  @IsString()
  @IsNotEmpty()
  @MaxLength(191)
  userId: string;

  @IsOptional()
  @IsString()
  @MaxLength(128)
  reasonCode?: string;
}

import { IsIn } from 'class-validator';

export class KycApprovalDto {
  @IsString()
  @IsNotEmpty()
  @MaxLength(36)
  userId!: string;

  @IsIn(['MERCHANT', 'CASHIER', 'SUPPLIER', 'LOGISTICS', 'ANALYTICS_VIEWER'])
  approvedRole!: string;
}

export class KycRejectionDto {
  @IsString()
  @IsNotEmpty()
  @MaxLength(36)
  userId!: string;

  @IsString()
  @IsNotEmpty()
  @MaxLength(128)
  reasonCode!: string;
}
