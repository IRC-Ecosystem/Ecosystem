import assert from 'node:assert/strict';
import { test } from 'node:test';
import { closePool, transitions, computeHmacSignature, calculateNextAttemptSeconds } from './server.js';

test('shipment state machine blocks invalid terminal transitions', async () => {
  assert.deepEqual(transitions.created, ['assigned', 'cancelled']);
  assert.deepEqual(transitions.delivered, []);
  assert.equal(transitions.in_transit.includes('delivered'), true);
});

test('computeHmacSignature computes valid sha256 hex including event_id', () => {
  const eventId = 'evt-12345';
  const payload = JSON.stringify({ order_id: 1, shipment_status: 'created' });
  const secret = 'super-secret-key-that-is-at-least-32-chars-long';

  const sig1 = computeHmacSignature(eventId, payload, secret);
  const sig2 = computeHmacSignature(eventId, payload, secret);
  assert.equal(sig1, sig2);
  assert.equal(sig1.length, 64);

  // Different event_id must produce different signature
  const sigDiff = computeHmacSignature('evt-67890', payload, secret);
  assert.notEqual(sig1, sigDiff);
});

test('calculateNextAttemptSeconds calculates exponential backoff with bounded jitter', () => {
  const attempt0 = calculateNextAttemptSeconds(0);
  assert.ok(attempt0 >= 30 && attempt0 <= 40);

  const attempt3 = calculateNextAttemptSeconds(3);
  assert.ok(attempt3 >= 240 && attempt3 <= 250);

  const attempt10 = calculateNextAttemptSeconds(10);
  assert.ok(attempt10 >= 3600 && attempt10 <= 3610);
});

test('close database pool', async () => {
  await closePool();
});
