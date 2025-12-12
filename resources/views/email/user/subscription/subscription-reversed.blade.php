<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Subscription Reversed - TransactX</title>
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

    /* DETAILS */
    .details {
      margin: 25px 0;
      border-top: 1px solid #e5e7eb;
      border-bottom: 1px solid #e5e7eb;
      padding: 20px 0;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
    }

    .detail-label {
      font-weight: 600;
      color: #374151;
      font-size: 14px;
    }

    .detail-value {
      color: #111827;
      font-weight: 500;
      font-size: 14px;
      text-align: right;
    }

    .amount {
      color: #AB0B4B;
      font-weight: 700;
    }

    .highlight {
      color: #AB0B4B;
      font-weight: 600;
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
      .detail-row { flex-direction: column; align-items: flex-start; gap: 4px; }
      .detail-value { text-align: left; }
      .button { display: block; width: 90%; margin: 8px auto; }
    }

    /* DARK MODE */
    @media (prefers-color-scheme: dark) {
      body { background-color: #0b0f19; color: #e5e7eb; }
      .email-container { background-color: #111827; border: 1px solid #1f2937; }
      .header { background-color: #111827; border-bottom: 1px solid #1f2937; }
      .header-text, .highlight { color: #f472b6; }
      .detail-label { color: #d1d5db; }
      .detail-value { color: #f9fafb; }
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
      <p class="header-text">🔄 Subscription Reversed</p>
    </div>

    <!-- CONTENT -->
    <div class="content">
      <div class="greeting">Dear {{ $user->first_name ?? $user->email }},</div>

      <div class="icon">⚠️</div>

      <div class="status-message">Your subscription payment failed and funds were reversed.</div>

      <div class="message">
        The payment for your <span class="highlight">{{ ucfirst($plan) }}</span> plan could not be completed.  
        Your funds have been safely returned to your wallet balance.
      </div>

      <div class="details">
        <div class="detail-row">
          <span class="detail-label">Attempted Amount</span>
          <span class="detail-value amount">{{ $transaction->currency }} {{ number_format($transaction->amount->getAmount()->toFloat(), 2) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Billed Amount</span>
          <span class="detail-value amount">{{ $transaction->currency }} {{ number_format($billed_at, 2) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Transaction Fee</span>
          <span class="detail-value">{{ $transaction->currency }} {{ number_format($transaction->feeTransactions()->first()->amount->getAmount()->toFloat(), 2) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Wallet Balance</span>
          <span class="detail-value amount">{{ $wallet->currency }} {{ number_format($wallet->amount->getAmount()->toFloat(), 2) }}</span>
        </div>
      </div>

      <div class="cta-message">
        Please ensure you have sufficient funds and try again to renew your plan.
      </div>

      <div class="cta-section">
        <a href="https://www.mytransactx.com/dashboard/subscription" class="button">Retry Payment</a>
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
