<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Subscription Expired - TransactX</title>
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
      border-radius: 8px;
      overflow: hidden;
      border: 1px solid #f3f4f6;
    }

    /* HEADER */
    .header {
      text-align: center;
      padding: 30px 20px 20px;
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
      font-size: 17px;
      font-weight: 600;
    }

    /* CONTENT */
    .content {
      padding: 30px 25px;
    }

    .greeting {
      font-size: 16px;
      font-weight: 600;
      color: #111827;
      margin-bottom: 10px;
    }

    .alert-icon {
      text-align: center;
      font-size: 40px;
      margin: 20px 0 10px;
    }

    .status-message {
      text-align: center;
      font-weight: 600;
      font-size: 16px;
      color: #AB0B4B;
      margin-bottom: 25px;
    }

    .plan {
      text-align: center;
      font-size: 15px;
      color: #374151;
      margin-bottom: 25px;
    }

    .plan strong {
      color: #AB0B4B;
      font-weight: 600;
    }

    /* MESSAGE BOXES */
    .warning-box {
      background-color: #fee2e2;
      border-left: 4px solid #ef4444;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 20px;
    }

    .warning-text {
      font-size: 14px;
      color: #991b1b;
      line-height: 1.6;
    }

    .info-box {
      background-color: #eff6ff;
      border-left: 4px solid #3b82f6;
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 20px;
    }

    .info-text {
      font-size: 14px;
      color: #1e40af;
      line-height: 1.6;
    }

    .cta-message {
      text-align: center;
      font-size: 14px;
      color: #374151;
      margin: 15px 0 25px;
    }

    /* BUTTONS */
    .cta-section {
      text-align: center;
    }

    .button {
      display: inline-block;
      background-color: #AB0B4B;
      color: #fff;
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 14px;
      margin: 5px;
    }
    .button:hover { background-color: #8a093e; }

    .button-secondary {
      display: inline-block;
      background-color: #6b7280;
      color: #fff;
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 14px;
      margin: 5px;
    }
    .button-secondary:hover { background-color: #4b5563; }

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
      .button, .button-secondary {
        display: block;
        width: 90%;
        margin: 8px auto;
      }
    }

    /* DARK MODE */
    @media (prefers-color-scheme: dark) {
      body { background-color: #0b0f19; color: #e5e7eb; }
      .email-container { background-color: #111827; border: 1px solid #1f2937; }
      .header { background-color: #111827; border-bottom: 1px solid #1f2937; }
      .header-text { color: #f472b6; }
      .content { background-color: #111827; }
      .greeting, .cta-message { color: #d1d5db; }
      .plan strong { color: #f472b6; }
      .warning-box { background-color: #7f1d1d; border-left-color: #f87171; }
      .warning-text { color: #fee2e2; }
      .info-box { background-color: #1e3a8a; border-left-color: #60a5fa; }
      .info-text { color: #dbeafe; }
      .button { background-color: #f472b6; }
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
      <p class="header-text">Subscription Expired ⚠️</p>
    </div>

    <!-- CONTENT -->
    <div class="content">
      <div class="greeting">Dear {{ $user->first_name ?? $user->email }},</div>

      <div class="alert-icon">⚠️</div>
      <div class="status-message">Your subscription plan has expired.</div>

      <div class="plan">
        Your plan: <strong>{{ ucfirst($model->name->value) }}</strong><br />
        Status: <strong>Expired</strong>
      </div>

      <div class="warning-box">
        <p class="warning-text">
          <strong>⏰ Action Required:</strong><br>
          Your {{ ucfirst($model->name->value) }} subscription has expired. To continue enjoying uninterrupted access to all premium features, please renew your subscription.
        </p>
      </div>

      <div class="info-box">
        <p class="info-text">
          <strong>🔄 Auto-Renewal Available:</strong><br>
          If you have auto-renewal enabled, your subscription will be automatically renewed. Otherwise, you can manually renew it to continue accessing all features without interruption.
        </p>
      </div>

      <div class="cta-message">
        Don’t miss out on the benefits of your subscription.<br />
        Renew now to keep enjoying all the premium features TransactX has to offer!
      </div>

      <div class="cta-section">
        <a href="https://www.mytransactx.com/dashboard/subscription/renew" class="button">Renew Subscription</a>
        <a href="https://www.mytransactx.com/dashboard/subscription" class="button-secondary">View Plans</a>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
      <p class="footer-text">
        This email was sent by TransactX. If you have any questions, please contact our support team.
      </p>

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
