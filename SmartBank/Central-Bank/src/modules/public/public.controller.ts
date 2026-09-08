import { Controller, Get } from '@nestjs/common';
import { Public } from '../../common/public.decorator';
import { PublicStatsService, PublicStats } from './public.service';

@Controller('public')
export class PublicController {
  constructor(private readonly stats: PublicStatsService) {}

  @Public()
  @Get('stats')
  async stats_(): Promise<{ success: true; data: PublicStats }> {
    const data = await this.stats.getStats();
    return { success: true, data };
  }
}