import { Module } from '@nestjs/common';
import { PrismaModule } from '../prisma/prisma.module';
import { PublicController } from './public.controller';
import { PublicStatsService } from './public.service';

@Module({
  imports: [PrismaModule],
  controllers: [PublicController],
  providers: [PublicStatsService],
  exports: [PublicStatsService],
})
export class PublicModule {}