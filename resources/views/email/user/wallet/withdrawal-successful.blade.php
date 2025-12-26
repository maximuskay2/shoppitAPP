<!DOCTYPE html>
<html lang="en">
  <body style="margin:0; padding:0; background:#F5F7FB; color:#1F2937;">
    <div style="display:none; font-size:1px; color:#F5F7FB; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
      Your withdrawal of {{ $currency }} {{ number_format($amount, 2) }} has been processed successfully.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F5F7FB;">
      <tr>
        <td align="center" style="padding:32px 16px;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.08);">
            <!-- Brand header -->
            <tr>
              <td align="center" style="background:linear-gradient(135deg, #2C9139 0%, #25A244 100%); padding:32px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:24px; font-weight:700; letter-spacing:.2px;">
                  Withdrawal Successful 💰
                </div>
                <div style="font-family:Arial,Helvetica,sans-serif; color:#E8F5EA; font-size:13px; margin-top:6px;">
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
                    Your withdrawal request has been processed successfully. The funds have been sent to your bank account.
                  </p>

                  <!-- Success Banner -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:linear-gradient(135deg, #2C9139 0%, #25A244 100%); border-radius:10px; padding:20px; margin:16px 0 20px; text-align:center;">
                    <tr>
                      <td align="center">
                        <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:18px; font-weight:700; margin-bottom:6px;">
                          ✅ Withdrawal Completed
                        </div>
                        <div style="font-family:Arial,Helvetica,sans-serif; color:#E8F5EA; font-size:20px; font-weight:700; margin-bottom:4px;">
                          {{ $currency }} {{ number_format($amount, 2) }}
                        </div>
                        <div style="font-family:Arial,Helvetica,sans-serif; color:#E8F5EA; font-size:14px;">
                          has been sent to your bank account
                        </div>
                      </td>
                    </tr>
                  </table>

                  <!-- Transaction Details -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F0F9F2; border:1px solid #D1E7D7; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#2C9139; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #D1E7D7;">
                        Transaction Details
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA; width:50%;">Transaction ID</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right; word-break:break-all;">{{ $transaction->reference }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Status</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">
                        <span style="background:#10b981; color:#ffffff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">{{ strtoupper($transaction->status) }}</span>
                      </td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Withdrawal Amount</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $currency }} {{ number_format($amount, 2) }}</td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Processing Fee</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $currency }} {{ number_format($feeAmount, 2) }}</td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; font-weight:700; padding:10px 0; border-bottom:1px solid #E8F5EA;">Total Debited</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#DC2626; font-weight:700; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $currency }} {{ number_format($totalDebited, 2) }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Date & Time</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $transaction->created_at->format('F j, Y \a\t g:i A') }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; font-weight:700; padding:10px 0;">Current Wallet Balance</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#2C9139; font-weight:700; padding:10px 0; text-align:right;">{{ $currency }} {{ number_format($walletBalance, 2) }}</td>
                    </tr>
                  </table>

                  <!-- Information Box -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#EFF6FF; border:1px solid #DBEAFE; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; color:#1E40AF; font-weight:700; font-size:14px; padding-bottom:8px;">
                        ℹ️ Important Information
                      </td>
                    </tr>
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#1E3A8A; line-height:1.6;">
                        • Funds should reflect in your bank account within 1-2 business days<br>
                        • Keep this reference number for your records<br>
                        • Contact support if you don't receive the funds within 3 business days<br>
                        • View all your transactions in your wallet history
                      </td>
                    </tr>
                  </table>

                  @if($transaction->narration)
                  <p style="margin:16px 0 8px; font-size:14px; color:#4B5563;">
                    <strong>Narration:</strong><br>
                    {{ $transaction->narration }}
                  </p>
                  @endif

                  <p style="margin:16px 0 8px; font-size:14px; color:#4B5563;">
                    Thank you for using ShopittPlus. If you have any questions about this withdrawal, please don't hesitate to contact our support team.
                  </p>
                  <p style="margin:0; font-size:14px;">Best regards,<br>The ShopittPlus Team</p>
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
