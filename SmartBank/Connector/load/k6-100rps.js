import http from 'k6/http';
import { check } from 'k6';

export const options = {
  scenarios: {
    connector_100_rps: {
      executor: 'constant-arrival-rate',
      rate: 100,
      timeUnit: '1s',
      duration: __ENV.DURATION || '60s',
      preAllocatedVUs: 50,
      maxVUs: 250,
    },
  },
  thresholds: { http_req_failed: ['rate<0.01'], http_req_duration: ['p(95)<200'] },
};

export default function () {
  const response = http.get(`${__ENV.CONNECTOR_URL || 'http://localhost:5000'}/health`);
  check(response, { 'health is 200': (result) => result.status === 200 });
}
