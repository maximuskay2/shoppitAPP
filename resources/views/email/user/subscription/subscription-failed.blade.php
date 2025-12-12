<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Subscription Failed - TransactX</title>
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
    }

    .logo {
      max-width: 180px;
      width: 180px;
      height: auto;
      display: inline-block;
      margin-bottom: 15px;
    }

    .header-text {
      color: #dc2626;
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
      margin-bottom: 15px;
    }

    .message {
      text-align: center;
      font-size: 15px;
      color: #374151;
      margin-bottom: 25px;
      line-height: 1.7;
    }

    .message strong { color: #111827; }

    .amount {
      color: #dc2626;
      font-weight: 700;
    }

    /* DETAILS TABLE */
    .subscription-details {
      background-color: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 8px;
      padding: 20px;
      margin: 20px 0;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid #fecaca;
    }

    .detail-row:last-child { border-bottom: none; padding-bottom: 0; }
    
    .detail-row:first-child { padding-top: 0; }

    .detail-label {
      font-weight: 600;
      color: #374151;
      font-size: 13px;
      flex: 0 0 auto;
      margin-right: 15px;
    }

    .detail-value {
      color: #111827;
      font-weight: 500;
      font-size: 13px;
      text-align: right;
      flex: 1 1 auto;
    }

    .failed-badge {
      background-color: #dc2626;
      color: #ffffff;
      padding: 4px 12px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: 600;
    }

    .highlight { color: #dc2626; font-weight: 600; }

    .message1 {
      text-align: center;
      font-size: 14px;
      color: #374151;
      margin-top: 20px;
    }

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
    }

    /* DARK MODE */
    @media (prefers-color-scheme: dark) {
      body { background-color: #0b0f19; color: #e5e7eb; }
      .email-container { background-color: #111827; border: 1px solid #1f2937; }
      .header { background-color: #111827; border-bottom: 1px solid #1f2937; }
      .header-text { color: #ef4444; }
      .content { background-color: #111827; }
      .greeting, .message1 { color: #d1d5db; }
      .subscription-details { background-color: #2d1a1a; border-color: #991b1b; }
      .detail-label { color: #d1d5db; }
      .detail-value { color: #f9fafb; }
      .failed-badge { background-color: #ef4444; }
      .amount { color: #ef4444; }
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
      <p class="header-text">Subscription Failed ❌</p>
    </div>

    <!-- CONTENT -->
    <div class="content">
      <div class="greeting">Dear {{ $user->first_name ?? $user->name }},</div>

      <div class="message">
        Your <strong>{{ ucfirst($plan) }}</strong> plan subscription of 
        <strong class="amount">{{ $transaction->currency }} {{ number_format($transaction->amount->getAmount()->toFloat(), 2) }}</strong> 
        billed at <strong class="amount">{{ $transaction->currency }} {{ number_format($billed_at, 2) }}</strong> 
        has failed ❌
      </div>

      <div class="subscription-details">
        <div class="detail-row">
          <span class="detail-label">Plan Name</span>
          <span class="detail-value">{{ ucfirst($plan) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Subscription Amount</span>
          <span class="detail-value amount">{{ $transaction->currency }} {{ number_format($transaction->amount->getAmount()->toFloat(), 2) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Billed Amount</span>
          <span class="detail-value amount">{{ $transaction->currency }} {{ number_format($billed_at, 2) }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Date & Time</span>
          <span class="detail-value">{{ $transaction->created_at->format('F j, Y \a\t g:i A') }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Transaction ID</span>
          <span class="detail-value">{{ $transaction->reference }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Status</span>
          <span class="detail-value"><span class="failed-badge">{{ $transaction->status }}</span></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Current Balance</span>
          <span class="detail-value amount">{{ $wallet->currency }} {{ number_format($wallet->amount->getAmount()->toFloat(), 2) }}</span>
        </div>
      </div>

      <div class="message1">
        Please contact support if you need assistance with <span class="highlight">TransactX</span>! 🚀
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
        © 2025 TransactX™. All Rights Reserved.<br />
        TransactX is a financial technology company registered in Nigeria.
      </div>
    </div>
  </div>
</body>
</html>