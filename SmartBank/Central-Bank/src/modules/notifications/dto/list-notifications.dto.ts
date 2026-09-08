import { IsEnum, IsOptional } from 'class-validator';
import { NotificationType } from '@prisma/client';

export class ListNotificationsDto {
  @IsOptional()
  @IsEnum(NotificationType)
  type?: NotificationType;

  @IsOptional()
  unread_only?: string;

  @IsOptional()
  page?: string;

  @IsOptional()
  limit?: string;
}
