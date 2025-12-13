<!DOCTYPE html>
<html lang="en">
  <body style="margin:0; padding:0; background:#F5F7FB; color:#1F2937;">
    <div style="display:none; font-size:1px; color:#F5F7FB; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
      Your ShopittPlus subscription payment failed. Action required.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F5F7FB;">
      <tr>
        <td align="center" style="padding:32px 16px;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.08);">
            <!-- Brand header -->
            <tr>
              <td align="center" style="background:#ef4444; padding:32px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:.2px;">
                  Subscription Payment Failed ⚠️
                </div>
                <div style="font-family:Arial,Helvetica,sans-serif; color:#fee2e2; font-size:13px; margin-top:6px;">
                  ShopittPlus - Your All-in-One Marketplace
                </div>
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style="padding:28px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif; font-size:16px; line-height:1.6; color:#1F2937;">
                  <p style="margin:0 0 10px;">Hi {{ $user->first_name ?? $user->name ?? 'there' }},</p>
                  <p style="margin:0 0 16px;">
                    Unfortunately, we were unable to process the payment for your <strong>{{ ucfirst($plan) }}</strong> subscription. 
                    The attempted charge of <strong style="color:#ef4444;">{{ $record->currency }} {{ number_format($record->amount->getAmount()->toFloat(), 2) }}</strong> 
                    was declined.
                  </p>

                  <!-- Warning Box -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#fef2f2; border:1px solid #ef4444; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#991b1b; line-height:1.6;">
                        <strong>⚠️ Action Required</strong><br>
                        Your subscription has been marked as <strong>expired</strong> and auto-renewal has been disabled. Please update your payment method to restore your subscription and regain access to premium features.
                      </td>
                    </tr>
                  </table>

                  <!-- Payment Details -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#fef2f2; border:1px solid #fecaca; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#ef4444; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #fecaca;">
                        Failed Payment Details
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #fee2e2; width:50%;">Plan Name</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; font-weight:500; padding:10px 0; border-bottom:1px solid #fee2e2; text-align:right;">{{ ucfirst($plan) }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #fee2e2;">Failed Amount</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#ef4444; font-weight:700; padding:10px 0; border-bottom:1px solid #fee2e2; text-align:right;">{{ $record->currency }} {{ number_format($record->amount->getAmount()->toFloat(), 2) }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #fee2e2;">Failed Date</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #fee2e2; text-align:right;">{{ now()->format('F j, Y \a\t g:i A') }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #fee2e2;">Record ID</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #fee2e2; text-align:right; word-break:break-all;">{{ $record->id }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0;">Status</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; padding:10px 0; text-align:right;">
                        <span style="background:#ef4444; color:#ffffff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">Failed</span>
                      </td>
                    </tr>
                  </table>

                  <!-- Info Box -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1e40af; line-height:1.6;">
                        <strong>💡 What Happens Next?</strong><br>
                        • Your subscription is now <strong>expired</strong><br>
                        • Auto-renewal has been <strong>disabled</strong><br>
                        • Premium features are no longer accessible<br>
                        • You can restore access by updating your payment method and renewing your subscription
                      </td>
                    </tr>
                  </table>

                  <!-- Action Buttons -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:20px 0;">
                    <tr>
                      <td align="center">
                        <a href="https://www.shopittplus.com/subscription/payment-method" style="display:inline-block; background:#2C9139; color:#ffffff; font-family:Arial,Helvetica,sans-serif; font-size:14px; font-weight:600; text-decoration:none; padding:12px 32px; border-radius:8px; margin:0 8px 10px;">
                          Update Payment Method
                        </a>
                      </td>
                    </tr>
                    <tr>
                      <td align="center">
                        <a href="https://www.shopittplus.com/subscription" style="display:inline-block; background:#6B7280; color:#ffffff; font-family:Arial,Helvetica,sans-serif; font-size:14px; font-weight:600; text-decoration:none; padding:12px 32px; border-radius:8px; margin:0 8px;">
                          View Subscription
                        </a>
                      </td>
                    </tr>
                  </table>

                  <p style="margin:0 0 8px; font-size:14px; color:#4B5563;">
                    Common reasons for payment failure include insufficient funds, expired cards, or incorrect billing information. 
                    If you continue to experience issues, please contact your bank or our support team.
                  </p>
                  <p style="margin:0; font-size:14px;">We're here to help!<br>The ShopittPlus Team</p>
                </div>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td align="center" style="background:#F3F4F6; padding:20px 16px;">
                <div style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280; margin-bottom:8px;">
                  This email was sent by ShopittPlus. If you have any questions, please contact our support team.
                </div>
                
                <div style="margin-bottom:12px;">
                  <a href="https://www.shopittplus.com" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#2C9139; text-decoration:none; margin:0 8px;">Website</a>
                  <a href="https://www.shopittplus.com/privacy-policy" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#2C9139; text-decoration:none; margin:0 8px;">Privacy Policy</a>
                  <a href="https://www.shopittplus.com/terms" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#2C9139; text-decoration:none; margin:0 8px;">Terms of Service</a>
                  <a href="mailto:support@shopittplus.com" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#2C9139; text-decoration:none; margin:0 8px;">Support</a>
                </div>
                
                <div style="margin-bottom:8px;">
                  <a href="https://x.com/shopittplus" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280; text-decoration:none; margin:0 6px;">X (Twitter)</a>
                  <a href="https://www.instagram.com/shopittplus" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280; text-decoration:none; margin:0 6px;">Instagram</a>
                </div>
                
                <div style="font-family:Arial,Helvetica,sans-serif; font-size:11px; color:#9CA3AF;">
                  &copy; {{ date('Y') }} ShopittPlus™. All Rights Reserved.<br />
                  ShopittPlus is a marketplace platform connecting local businesses with customers.
                </div>
              </td>
            </tr>
          </table>

          <!-- Spacer -->
          <div style="height:24px; line-height:24px; font-size:24px;">&nbsp;</div>
        </td>
      </tr>
    </table>
  </body>
</html>
