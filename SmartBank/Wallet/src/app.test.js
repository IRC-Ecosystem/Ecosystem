import assert from 'node:assert/strict';
import { test } from 'node:test';
import { health } from './health.js';

test('wallet health endpoint reports service readiness', async () => {
  let statusCode;
  let body;
  const response = {
    status(code) { statusCode = code; return this; },
    json(payload) { body = payload; return this; },
  };
  health({}, response);

  assert.equal(statusCode, 200);
  assert.deepEqual(body, { status: 'ok', service: 'smartbank-wallet' });
});
