# Zion Backend Load Testing & Real-Time Dashboards Guide

This setup enables end-to-end performance benchmarking and real-time observability across both the load client and server-side application.

---

## 1. Overview of Monitoring Dashboards

| Dashboard | URL | What it monitors |
|---|---|---|
| **Grafana (k6)** | [http://localhost:3000](http://localhost:3000) | Real-time RPS, p95/p99 response latency, active virtual users, error rates, and failure thresholds. |
| **k6 Native Web UI** | [http://localhost:5665](http://localhost:5665) | Zero-dependency live dashboard stream emitted directly by the k6 binary. |
| **Laravel Horizon** | [http://localhost/horizon](http://localhost/horizon) | Queue throughput, job wait times, memory per queue worker, and failed jobs (moderation & fanout). |
| **Laravel Pulse** | [http://localhost/pulse](http://localhost/pulse) | Application APM: slow database queries, slow endpoints, CPU/memory usage, and cache hit rates. |

---

## 2. Server-Side Monitoring Setup

### A. Laravel Horizon (Already Installed)
Horizon is pre-configured and runs in the `zion_horizon` container. Ensure your application and Horizon are running:
```bash
docker compose up -d app web db redis horizon
```
Access the Horizon dashboard directly at:
```
http://localhost/horizon
```

### B. Laravel Pulse (Application APM)
To enable real-time profiling of slow database queries and CPU/memory usage without affecting existing database data:

1. Install Laravel Pulse (inside the app container or host):
   ```bash
   composer require laravel/pulse
   ```
2. Publish configuration and run its migrations (**Never wipe or reset the database**):
   ```bash
   php artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"
   php artisan migrate
   ```
3. Run the system recorder in the background:
   ```bash
   php artisan pulse:check
   ```
4. Access the dashboard at:
   ```
   http://localhost/pulse
   ```

---

## 3. Starting the Load Testing Infrastructure

The load test monitoring stack consists of InfluxDB (storing metrics) and Grafana (visualizing metrics).

Start the monitoring containers:
```bash
docker compose -f load-tests/docker-compose.monitoring.yml up -d
```

- Open Grafana at [http://localhost:3000](http://localhost:3000) (Anonymous Admin login is enabled automatically).
- Navigate to **Dashboards > Load Testing > k6 Zion Load Testing Dashboard**.

---

## 4. Running the Load Test

### Option 1: Running with Native k6 (Recommended on macOS)

If you have `k6` installed (`brew install k6`), run the benchmark streaming simultaneously to Grafana and the k6 Web Dashboard:

```bash
K6_WEB_DASHBOARD=true K6_WEB_DASHBOARD_PORT=5665 \
k6 run --out influxdb=http://localhost:8086/k6 \
-e TARGET_URL=http://localhost \
-e TEST_USER_EMAIL=test@example.com \
-e TEST_USER_PASSWORD=password \
load-tests/scripts/api-benchmark.js
```

### Option 2: Running via Docker

Run the pre-configured k6 runner container inside the Docker network:

```bash
docker compose -f load-tests/docker-compose.monitoring.yml run --rm \
  -e TARGET_URL=http://web \
  -e TEST_USER_EMAIL=test@example.com \
  -e TEST_USER_PASSWORD=password \
  k6 run /scripts/api-benchmark.js
```

---

## 5. Traffic Stages & Breakpoint Discovery

The script executes 5 phased traffic stages to locate your application's concurrency breakpoint:

1. **Stage 1 (0:00 - 0:30)**: Warmup to 10 VUs.
2. **Stage 2 (0:30 - 1:30)**: Sustained baseline traffic at 50 VUs.
3. **Stage 3 (1:30 - 3:30)**: Stress load ramping to 150 VUs.
4. **Stage 4 (3:30 - 4:30)**: Spike test ramping to 300 VUs to identify failure thresholds.
5. **Stage 5 (4:30 - 5:00)**: Cool-down and recovery back to 0 VUs.

### Key Performance Thresholds:
- **P95 Latency**: Target < 500ms
- **P99 Latency**: Target < 1200ms
- **Error Rate**: Target < 2%

