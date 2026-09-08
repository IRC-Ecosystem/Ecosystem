import http from 'node:http';
import { handler, retryDeliveries } from './server.js';

http.createServer(handler).listen(Number(process.env.PORT || 4005), '0.0.0.0');
setInterval(() => retryDeliveries().catch((error) => console.error(JSON.stringify({ event: 'delivery_retry_failed', error: error.message }))), 10000).unref();
