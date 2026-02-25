# Production Logging Guide (Railway)

How to view logs and debug issues in your Laravel API and admin apps on Railway.

---

## 1. Railway Dashboard – Live Logs

**Laravel API service:**
1. Open [Railway Dashboard](https://railway.app/dashboard)
2. Select your project → **Laravel API** service
3. Go to **Deployments** → select the active deployment
4. Open the **Logs** tab (or **View Logs**)

You’ll see stdout/stderr from your app. Laravel writes to `storage/logs/` by default, but Railway **does not persist those files**. Logs in files won’t show up here unless you change the channel (see Section 4).

**Admin service (if separate):**
If the admin is its own service, use the same flow for that service.

---

## 2. Railway CLI – Stream Logs in Terminal

```bash
# Install Railway CLI: npm i -g @railway/cli

# Log in and link to your project
railway login
railway link

# Stream logs for the linked service
railway logs

# Stream logs for a specific service (if multiple)
railway logs --service laravel-api

# Follow logs (like tail -f)
railway logs --follow
```

---

## 3. Make Laravel Logs Visible in Railway

Railway captures **stdout/stderr** from the process. Laravel’s default `single` and `daily` channels write to `storage/logs/laravel.log`, which Railway does not show.

**Use `stderr` in production** so logs go to stderr and appear in Railway:

In Railway → Laravel API → **Variables**, add or set:

```env
LOG_CHANNEL=stderr
LOG_LEVEL=debug
```

After deploy, Laravel’s `Log::info()`, `Log::error()`, etc. will show up in Railway logs.

**Recommended for production:**
- `LOG_LEVEL=error` or `LOG_LEVEL=warning` – less noise
- `LOG_LEVEL=debug` – for active debugging, then switch back

---

## 4. Current Logging in the Codebase

- **API exceptions:** `bootstrap/app.php` logs all API 500s with message and stack trace.
- **Request context:** `RequestContextLogger` adds request_id, path, method, etc. to log context.
- **OTP/mail:** Registration and OTP paths use `Log::warning()` and `Log::error()`.
- **Channels:** Stack uses `single`, `slack`, `daily`. Slack only runs if `LOG_SLACK_WEBHOOK_URL` is set.

When you switch to `LOG_CHANNEL=stderr`, all of these go to stderr and show in Railway logs.

---

## 5. Optional: External Log Aggregation

For longer retention and search, use an external service.

### Papertrail (free tier)

1. Create a [Papertrail](https://www.papertrail.com/) account.
2. Add a system, copy host and port.
3. In Railway variables:
   ```env
   PAPERTRAIL_URL=logs.papertrailapp.com
   PAPERTRAIL_PORT=12345
   LOG_CHANNEL=papertrail
   ```

### Sentry (errors only)

1. Create a [Sentry](https://sentry.io/) project.
2. Install: `composer require sentry/sentry-laravel`
3. Configure and add `SENTRY_LARAVEL_DSN` in Railway.

### Slack (critical only)

Set `LOG_SLACK_WEBHOOK_URL` in Railway. The `slack` channel is already in your stack, so `critical`-level logs are sent to Slack.

---

## 6. Quick Debugging Checklist

| Issue | Step |
|-------|------|
| 500 on API | 1. Set `LOG_CHANNEL=stderr`, `LOG_LEVEL=debug` 2. Trigger the request 3. Check Railway logs for stack trace |
| Registration fails | Check logs for mail/SMTP errors, OTP send errors |
| Admin settings 404/500 | Check logs for route/controller errors |
| No logs at all | Verify `LOG_CHANNEL=stderr` and redeploy |

---

## 7. Environment Variables Summary

```env
# Send Laravel logs to Railway stdout/stderr
LOG_CHANNEL=stderr
LOG_LEVEL=debug

# Optional: Slack alerts for critical logs
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/...
```

---

## 8. Viewing Logs After Enabling stderr

1. Set `LOG_CHANNEL=stderr` in Railway variables.
2. Redeploy.
3. Go to Railway → service → Deployments → Logs.
4. Trigger the failing request.
5. Look for lines like: `[2025-02-07 20:00:00] production.ERROR: API exception: ...`


MAIL_MAILER=resend
RESEND_KEY=re_xxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="Shoppit"