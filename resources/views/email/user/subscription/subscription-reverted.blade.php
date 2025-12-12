<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Subscription Reverted - TransactX</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      background-color: #f9fafb;
      color: #374151;
      line-height: 1.6;
    }

    .email-container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #ffffff;
      border: 1px solid #f3f4f6;
      border-radius: 10px;
      overflow: hidden;
    }

    /* HEADER */
    .header {
      text-align: center;
      padding: 35px 20px 25px;
      border-bottom: 1px solid #e5e7eb;
      background-color: #ffffff;
    }

    .logo {
            max-width: 180px;
            width: 180px;
            height: auto;
            display: inline-block;
            margin-bottom: 15px;
        }

    .header-text {
      color: #AB0B4B;
      font-size: 18px;
      font-weight: 600;
    }

    /* CONTENT */
    .content {
      padding: 35px 25px;
    }

    .greeting {
      font-size: 16px;
      font-weight: 600;
      color: #111827;
      margin-bottom: 15px;
    }

    .icon {
      text-align: center;
      font-size: 38px;
      margin: 15px 0;
    }

    .status-message {
      text-align: center;
      font-weight: 600;
      font-size: 16px;
      color: #AB0B4B;
      margin-bottom: 25px;
    }

    .message {
      text-align: center;
      font-size: 15px;
      color: #374151;
      margin-bottom: 25px;
    }

    /* REVERSION BOX */
    .reversion-box {
      text-align: center;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 25px;
      background-color: #fafafa;
      margin: 25px 0;
    }

    .reversion-label {
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 1px;
      font-weight: 600;
      color: #6b7280;
    }

    .plan-name {
      font-size: 24px;
      font-weight: 700;
      color: #111827;
      margin: 8px 0;
    }

    .reversion-status {
      font-size: 14px;
      font-weight: 600;
      color: #6b7280;
    }

    /* DETAILS */
    .list {
      margin: 25px 0;
      padding: 0 5px;
    }

    .list-item {
      display: flex;
      align-items: start;
      margin-bottom: 15px;
    }

    .list-icon {
      font-size: 18px;
      margin-right: 10px;
      color: #AB0B4B;
    }

    .list-text {
      font-size: 14px;
      color: #374151;
      line-height: 1.6;
    }

    /* INFO BOX */
    .info-box {
      background-color: #fef2f2;
      border-left: 4px solid #AB0B4B;
      padding: 18px 20px;
      margin: 25px 0;
      border-radius: 6px;
    }

    .info-text {
      font-size: 14px;
      color: #7f1d1d;
      font-weight: 500;
      margin: 0;
      line-height: 1.6;
    }

    /* CTA */
    .cta-message {
      text-align: center;
      font-size: 14px;
      color: #374151;
      margin: 25px 0 10px;
    }

    .cta-section {
      text-align: center;
      margin: 10px 0 20px;
    }

    .button {
      display: inline-block;
      background-color: #AB0B4B;
      color: #fff;
      text-decoration: none;
      padding: 12px 28px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 14px;
      margin: 5px;
    }

    .button:hover { background-color: #8A0A3D; }

    /* FOOTER */
    .footer {
      background-color: #f8fafc;
      padding: 25px;
      text-align: center;
      border-top: 1px solid #e5e7eb;
    }

    .footer-text {
      font-size: 13px;
      color: #6b7280;
      margin-bottom: 12px;
    }

    .footer-links a {
      color: #AB0B4B;
      text-decoration: none;
      margin: 0 8px;
      font-size: 13px;
    }

    .footer-links a:hover { text-decoration: underline; }

    .social-links {
      margin: 15px 0;
    }

    .social-links a {
      display: inline-block;
      margin: 0 6px;
      color: #6b7280;
      text-decoration: none;
      font-size: 13px;
    }

    .copyright {
      font-size: 11px;
      color: #9ca3af;
      margin-top: 15px;
    }

    /* RESPONSIVE */
    @media (max-width: 600px) {
      .content, .header, .footer { padding: 20px 15px; }
      .button { display: block; width: 90%; margin: 8px auto; }
    }

    /* DARK MODE */
    @media (prefers-color-scheme: dark) {
      body { background-color: #0b0f19; color: #e5e7eb; }
      .email-container { background-color: #111827; border: 1px solid #1f2937; }
      .header { background-color: #111827; border-bottom: 1px solid #1f2937; }
      .header-text, .plan-name { color: #f472b6; }
      .list-text { color: #e5e7eb; }
      .info-box { background-color: #2d1a1a; border-left: 4px solid #f472b6; }
      .info-text { color: #f9fafb; }
      .button { background-color: #f472b6; color: #111827; }
      .footer { background-color: #1f2937; border-top: 1px solid #374151; }
      .footer-text { color: #9ca3af; }
      .footer-links a { color: #f472b6; }
      .social-links a { color: #9ca3af; }
      .copyright { color: #6b7280; }
    }
  </style>
</head>
<body>
  <div class="email-container">
    <!-- HEADER -->
    <div class="header">
      <img src="https://www.mytransactx.com/transactsx.png" alt="TransactX" class="logo" />
      <p class="header-text">⬇️ Subscription Reverted</p>
    </div>

    <!-- CONTENT -->
    <div class="content">
      <div class="greeting">Dear {{ $user->first_name ?? $user->email }},</div>

      <div class="icon">📉</div>

      <div class="status-message">Your subscription has been reverted to a lower plan.</div>

      <div class="reversion-box">
        <div class="reversion-label">Reverted to Plan</div>
        <div class="plan-name">{{ ucfirst($model->name->value) }}</div>
        <div class="reversion-status">Status: Active</div>
      </div>

      <div class="message">
        Your subscription has been downgraded to the <span class="highlight">{{ ucfirst($model->name->value) }}</span> plan.  
        Any data exceeding this plan’s limits has been permanently deleted.
      </div>

      <div class="list">
        <div class="list-item">
          <div class="list-icon">❌</div>
          <div class="list-text"><strong>Data Removed:</strong> All excess data has been permanently deleted.</div>
        </div>
        <div class="list-item">
          <div class="list-icon">⬇️</div>
          <div class="list-text"><strong>Features Limited:</strong> Premium features are now restricted to your plan level.</div>
        </div>
        <div class="list-item">
          <div class="list-icon">📦</div>
          <div class="list-text"><strong>Storage Reduced:</strong> Storage capacity now matches your current plan.</div>
        </div>
      </div>

      <div class="info-box">
        <p class="info-text">
          <strong>💡 Want to Upgrade?</strong><br>
          You can upgrade anytime to regain full access and additional storage.  
          Upgrading instantly reactivates all premium features.
        </p>
      </div>

      <div class="cta-message">
        Explore available plans and upgrade to unlock your full benefits again.
      </div>

      <div class="cta-section">
        <a href="https://www.mytransactx.com/dashboard/subscription" class="button">View Subscription Plans</a>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
      <p class="footer-text">Thank you for choosing TransactX! 🚀</p>
      <div class="footer-links">
        <a href="https://www.mytransactx.com">Website</a>
        <a href="https://www.mytransactx.com/privacy-policy">Privacy Policy</a>
        <a href="https://www.mytransactx.com/terms">Terms of Service</a>
        <a href="mailto:support@mytransactx.com">Support</a>
      </div>
      <div class="social-links">
        <a href="https://x.com/mytransactx">X (Twitter)</a>
        <a href="https://www.instagram.com/mytransactx/">Instagram</a>
      </div>
      <div class="copyright">
        © {{ date('Y') }} TransactX™. All Rights Reserved.<br />
        TransactX is a financial technology company registered in Nigeria.
      </div>
    </div>
  </div>
</body>
</html>
