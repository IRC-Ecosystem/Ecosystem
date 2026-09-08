import { Prisma } from '@prisma/client';
import { prisma } from '../lib/prisma';
import crypto from 'crypto';
import { env } from '../config/env';


type AuditInput = {
  serviceId?: string;
  serviceName?: string;
  actorType: 'SISTER_APP' | 'ADMIN' | 'SYSTEM';
  actorId?: string;
  action: string;
  targetType?: string;
  targetId?: string;
  requestId?: string;
  ipAddress?: string;
  metadata?: Record<string, unknown>;
};

export class AuditService {
  static async record(input: AuditInput) {
    return prisma.$transaction(async (tx) => {
      const previous = await tx.auditLog.findFirst({ orderBy: [{ timestamp: 'desc' }, { id: 'desc' }] });
      const id = crypto.randomUUID();
      const timestamp = new Date();
      const data = {
        id,
        timestamp,
        service_id: input.serviceId,
        service_name: input.serviceName,
        actor_type: input.actorType,
        actor_id: input.actorId,
        action: input.action,
        target_type: input.targetType,
        target_id: input.targetId,
        request_id: input.requestId,
        ip_address: input.ipAddress,
        metadata: input.metadata as Prisma.InputJsonValue | undefined,
        previous_id: previous?.id,
      };
      const signature = this.sign(data, previous?.signature ?? null);
      return tx.auditLog.create({ data: { ...data, signature } });
    }, { isolationLevel: Prisma.TransactionIsolationLevel.Serializable });
  }

  static sign(entry: Record<string, unknown>, previousSignature: string | null) {
    const payload = {
      id: entry.id,
      timestamp: entry.timestamp instanceof Date ? entry.timestamp.toISOString() : entry.timestamp,
      service_id: entry.service_id ?? null,
      service_name: entry.service_name ?? null,
      actor_type: entry.actor_type,
      actor_id: entry.actor_id ?? null,
      action: entry.action,
      target_type: entry.target_type ?? null,
      target_id: entry.target_id ?? null,
      request_id: entry.request_id ?? null,
      ip_address: entry.ip_address ?? null,
      metadata: entry.metadata ?? null,
      previous_signature: previousSignature,
    };
    return crypto.createHmac('sha256', env.AUDIT_HMAC_SECRET).update(stableStringify(payload)).digest('hex');
  }

  static async verifyChain() {
    const entries = await prisma.auditLog.findMany({ orderBy: [{ timestamp: 'asc' }, { id: 'asc' }] });
    let previous: typeof entries[number] | null = null;
    for (const entry of entries) {
      const expected = this.sign(entry as unknown as Record<string, unknown>, previous?.signature ?? null);
      if (entry.previous_id !== (previous?.id ?? null) || entry.signature !== expected) {
        return { valid: false, checked: entries.indexOf(entry), broken_at: entry.id };
      }
      previous = entry;
    }
    return { valid: true, checked: entries.length, broken_at: null };
  }
}

function stableStringify(value: unknown): string {
  if (Array.isArray(value)) return `[${value.map(stableStringify).join(',')}]`;
  if (value && typeof value === 'object') {
    const object = value as Record<string, unknown>;
    return `{${Object.keys(object).sort().map((key) => `${JSON.stringify(key)}:${stableStringify(object[key])}`).join(',')}}`;
  }
  return JSON.stringify(value) ?? 'null';
}
