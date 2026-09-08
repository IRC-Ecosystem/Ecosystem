import { Module } from '@nestjs/common';
import { PrismaModule } from '../prisma/prisma.module';
import { ServiceTokenGuard } from '../../common/service-token.guard';
import { NotificationController } from './notification.controller';
import { NotificationService } from './notification.service';

@Module({
  imports: [PrismaModule],
  controllers: [NotificationController],
  providers: [NotificationService, ServiceTokenGuard],
  exports: [NotificationService],
})
export class NotificationsModule {}
