import { Body, Controller, Get, Param, Patch, Post, Query, UseGuards } from '@nestjs/common';
import { CurrentUser, RequestUser } from '../../common/current-user.decorator';
import { Public } from '../../common/public.decorator';
import { ServiceTokenGuard } from '../../common/service-token.guard';
import { CreateNotificationDto } from './dto/create-notification.dto';
import { ListNotificationsDto } from './dto/list-notifications.dto';
import { NotificationService } from './notification.service';

@Controller()
export class NotificationController {
  constructor(private readonly notifications: NotificationService) {}

  @Public()
  @UseGuards(ServiceTokenGuard)
  @Post('internal/notifications')
  createInternal(@Body() body: CreateNotificationDto) {
    return this.notifications.create(body);
  }

  @Get('users/me/notifications')
  list(@CurrentUser() user: RequestUser, @Query() query: ListNotificationsDto) {
    return this.notifications.listForUser(user.sub, query);
  }

  @Get('users/me/notifications/unread-count')
  unreadCount(@CurrentUser() user: RequestUser) {
    return this.notifications.unreadCount(user.sub);
  }

  @Patch('users/me/notifications/:id/read')
  markRead(@CurrentUser() user: RequestUser, @Param('id') id: string) {
    return this.notifications.markRead(user.sub, id);
  }

  @Post('users/me/notifications/read-all')
  readAll(@CurrentUser() user: RequestUser) {
    return this.notifications.readAll(user.sub);
  }
}
