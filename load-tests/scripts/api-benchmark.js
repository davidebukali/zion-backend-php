import http from 'k6/http';
import { check, sleep } from 'k6';
import { Trend, Rate, Counter } from 'k6/metrics';

// Custom metrics for Grafana tracking
const feedReadDuration = new Trend('zion_feed_read_duration', true);
const postCreateDuration = new Trend('zion_post_create_duration', true);
const authDuration = new Trend('zion_auth_duration', true);
const successfulPosts = new Counter('zion_successful_posts');
const errorRate = new Rate('zion_error_rate');

// Base URL: defaults to localhost (from host) or 'http://web' (if executed inside Docker)
const BASE_URL = __ENV.TARGET_URL || 'http://localhost';
const USER_EMAIL = __ENV.TEST_USER_EMAIL || 'test@example.com';
const USER_PASSWORD = __ENV.TEST_USER_PASSWORD || 'password';

export const options = {
  // Breakpoint & Staged Ramp-up Configuration
  stages: [
    { duration: '30s', target: 10 },   // Warm-up
    { duration: '1m',  target: 50 },   // Normal sustained load
    { duration: '2m',  target: 150 },  // High load (stress test)
    { duration: '1m',  target: 300 },  // Spike / Breakpoint exploration
    { duration: '30s', target: 0 },    // Cool-down & recovery
  ],
  thresholds: {
    'http_req_duration': ['p(95)<500', 'p(99)<1200'], // 95% of requests under 500ms
    'http_req_failed': ['rate<0.02'],                  // Less than 2% errors
    'zion_error_rate': ['rate<0.02'],
  },
};

// Global setup: Verify target connectivity and retrieve initial auth token
export function setup() {
  const loginPayload = JSON.stringify({
    email: USER_EMAIL,
    password: USER_PASSWORD,
  });

  const res = http.post(`${BASE_URL}/api/v1/login`, loginPayload, {
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    },
  });

  let token = null;
  if (res.status === 200) {
    try {
      const body = JSON.parse(res.body);
      token = body.token || (body.data && body.data.token);
    } catch (e) {
      console.warn('Failed to parse login response JSON');
    }
  }

  if (!token) {
    console.warn(`Warning: Login failed with status ${res.status}. Tests will continue with unauthenticated/public checks or fallback token.`);
  }

  return { defaultToken: token };
}

export default function (data) {
  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };

  if (data.defaultToken) {
    headers['Authorization'] = `Bearer ${data.defaultToken}`;
  }

  // 1. Health / Landing check
  {
    const landingRes = http.get(`${BASE_URL}/`);
    check(landingRes, {
      'Landing status 200': (r) => r.status === 200,
    });
  }

  sleep(0.5);

  // 2. Fetch posts feed (Database Read query under load)
  {
    const start = Date.now();
    const feedRes = http.get(`${BASE_URL}/api/v1/posts?per_page=15`, { headers });
    feedReadDuration.add(Date.now() - start);

    const isSuccess = check(feedRes, {
      'Posts feed status is 200': (r) => r.status === 200,
    });
    errorRate.add(!isSuccess);
  }

  sleep(0.5);

  // 3. Create post (DB write transaction + triggers Queue Jobs handled by Horizon)
  if (data.defaultToken) {
    const postPayload = JSON.stringify({
      content: `Load test automated post from VU ${__VU} iteration ${__ITER} at ${new Date().toISOString()}`,
    });

    const start = Date.now();
    const createRes = http.post(`${BASE_URL}/api/v1/posts`, postPayload, { headers });
    postCreateDuration.add(Date.now() - start);

    const isCreated = check(createRes, {
      'Post created status 201': (r) => r.status === 201,
    });

    if (isCreated) {
      successfulPosts.add(1);
      try {
        const body = JSON.parse(createRes.body);
        const postId = body.data ? body.data.id : null;
        if (postId) {
          // 4. Fetch specific created post
          const singleRes = http.get(`${BASE_URL}/api/v1/posts/${postId}`, { headers });
          check(singleRes, {
            'Single post fetched 200': (r) => r.status === 200,
          });
        }
      } catch (e) {
        // ignore JSON parse issue
      }
    } else {
      errorRate.add(1);
    }
  }

  // Realistic user pacing between actions (1-2s)
  sleep(Math.random() * 1.5 + 0.5);
}

