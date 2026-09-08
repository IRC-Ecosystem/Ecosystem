import { Injectable, NotFoundException } from '@nestjs/common';
import { Prisma } from '@prisma/client';
import { randomUUID } from 'crypto';
import { PrismaService } from '../prisma/prisma.service';
import { CreateNotificationDto } from './dto/create-notification.dto';
import { ListNotificationsDto } from './dto/list-notifications.dto';

function serialize(notification: any) {
  return {
    id: notification.id,
    user_id: notification.userId,
    type: notification.type,
    channel: notification.channel,
    source_app: notification.sourceApp,
    source_ref: notification.sourceRef,
    title: notification.title,
    body: notification.body,
    payload: notification.payload,
    read_at: notification.readAt,
    created_at: notification.createdAt,
  };
}

@Injectable()
export class NotificationService {
  constructor(private readonly prisma: PrismaService) {}

  async create(input: CreateNotificationDto) {
    const notification = await this.prisma.notification.create({
      data: {
        id: randomUUID(),
        userId: input.user_id,
        type: input.type,
        sourceApp: input.source_app,
        sourceRef: input.source_ref,
        title: input.title,
        body: input.body,
        payload: input.payload as Prisma.InputJsonValue | undefined,
      },
    });
    return serialize(notification);
  }

  async listForUser(userId: string, query: ListNotificationsDto) {
    const page = parseInt(query.page ?? '1', 10);
    const limit = parseInt(query.limit ?? '20', 10);
    const where: Prisma.NotificationWhereInput = {
      userId,
      ...(query.type ? { type: query.type } : {}),
      ...(query.unread_only === 'true' ? { readAt: null } : {}),
    };
    const [items, total] = await this.prisma.$transaction([
      this.prisma.notification.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        skip: (page - 1) * limit,
        take: limit,
      }),
      this.prisma.notification.count({ where }),
    ]);
    return { items: items.map(serialize), total, page, limit };
  }

  async unreadCount(userId: string) {
    const count = await this.prisma.notification.count({ where: { userId, readAt: null } });
    return { count };
  }

  async markRead(userId: string, id: string) {
    const existing = await this.prisma.notification.findFirst({ where: { id, userId } });
    if (!existing) throw new NotFoundException('Notifikasi tidak ditemukan');
    const notification = await this.prisma.notification.update({
      where: { id },
      data: { readAt: existing.readAt ?? new Date() },
    });
    return serialize(notification);
  }

  async readAll(userId: string) {
    const result = await this.prisma.notification.updateMany({
      where: { userId, readAt: null },
      data: { readAt: new Date() },
    });
    return { updated: result.count };
  }
}
