<!DOCTYPE html>
<html lang="en">
  <body style="margin:0; padding:0; background:#F5F7FB; color:#1F2937;">
    <div style="display:none; font-size:1px; color:#F5F7FB; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
      Your ShopittPlus subscription grace period is ending soon. Your plan will revert to {{ ucfirst($plan->name) }} in 7 days.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F5F7FB;">
      <tr>
        <td align="center" style="padding:32px 16px;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.08);">
            <!-- Brand header -->
            <tr>
              <td align="center" style="background:#f59e0b; padding:32px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:.2px;">
                  Subscription Revert Warning ⚠️
                </div>
                <div style="font-family:Arial,Helvetica,sans-serif; color:#fef3c7; font-size:13px; margin-top:6px;">
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
                    Your subscription grace period is ending soon. Your plan will automatically revert to the <strong>{{ ucfirst($plan->name) }}</strong> plan after the grace period expires. 
                    Data or features exceeding the new plan limits may be permanently removed.
                  </p>

                  <!-- Countdown Section -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#FEF3C7; border:1px solid #FDE68A; border-radius:10px; padding:20px; margin:16px 0 20px; text-align:center;">
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; padding-bottom:8px;">
                        <div style="font-size:13px; text-transform:uppercase; letter-spacing:1px; font-weight:600; color:#92400e; margin-bottom:8px;">Time Remaining</div>
                        <div style="font-size:28px; font-weight:700; color:#f59e0b; margin-bottom:8px;">7 Days</div>
                        <div style="font-size:15px; color:#111827; font-weight:500;">Reverting to: {{ ucfirst($plan->name) }}</div>
                      </td>
                    </tr>
                  </table>

                  <!-- Warning Details -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#dc2626; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #FECACA;">
                        What Will Happen After Grace Period
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:12px 0; border-bottom:1px solid #FEE2E2;">
                        <span style="font-size:18px; margin-right:8px;">❌</span>
                        <strong style="color:#111827;">Data Loss:</strong> Any excess data will be deleted after reversion.
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:12px 0; border-bottom:1px solid #FEE2E2;">
                        <span style="font-size:18px; margin-right:8px;">🔒</span>
                        <strong style="color:#111827;">Limited Access:</strong> Some premium features will be unavailable.
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:12px 0; border-bottom:1px solid #FEE2E2;">
                        <span style="font-size:18px; margin-right:8px;">📦</span>
                        <strong style="color:#111827;">Reduced Storage:</strong> Storage capacity will match the reverted plan.
                      </td>
                    </tr>
                  </table>

                  <p style="margin:0 0 16px; font-size:14px; color:#4B5563; text-align:center;">
                    <strong style="color:#dc2626;">Action Required:</strong> Renew your subscription now to maintain your current plan and prevent data loss.
                  </p>
                  
                  <!-- CTA Button -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:20px 0;">
                    <tr>
                      <td align="center">
                        <a href="https://www.shopittplus.com/dashboard/subscription/renew" style="display:inline-block; background:#2C9139; color:#ffffff; text-decoration:none; padding:12px 28px; border-radius:6px; font-family:Arial,Helvetica,sans-serif; font-weight:600; font-size:14px;">Renew Subscription Now</a>
                      </td>
                    </tr>
                  </table>

                  <p style="margin:16px 0 0; font-size:14px;">If you have any questions or need assistance, please contact our support team.<br>The ShopittPlus Team</p>
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
