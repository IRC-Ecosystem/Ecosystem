const { createRateLimiter } = require('../dist/middleware/rateLimit');

function invoke(middleware) {
  return new Promise((resolve) => {
    const headers = {};
    const req = { ip: '127.0.0.1', service: { id: 'svc-test' } };
    const res = { setHeader: (key, value) => { headers[key] = value; } };
    middleware(req, res, (error) => resolve({ error, headers }));
  });
}

describe('service rate limiter', () => {
  test('allows requests within the limit and rejects the next one', async () => {
    const middleware = createRateLimiter({ windowMs: 60_000, limit: 2 });
    expect((await invoke(middleware)).error).toBeUndefined();
    expect((await invoke(middleware)).error).toBeUndefined();
    const third = await invoke(middleware);
    expect(third.error?.code).toBe('RATE_LIMITED');
    expect(third.headers['RateLimit-Remaining']).toBe('0');
  });
});
