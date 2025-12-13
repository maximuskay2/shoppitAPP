<!DOCTYPE html>
<html lang="en">
  <body style="margin:0; padding:0; background:#F5F7FB; color:#1F2937;">
    <div style="display:none; font-size:1px; color:#F5F7FB; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
      Welcome back! Your ShopittPlus {{ ucfirst($plan->name) }} plan subscription has been resumed.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F5F7FB;">
      <tr>
        <td align="center" style="padding:32px 16px;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.08);">
            <!-- Brand header -->
            <tr>
              <td align="center" style="background:#2C9139; padding:32px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:.2px;">
                  Subscription Resumed 🎉
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
                    <strong>Welcome back!</strong> Your <strong>{{ ucfirst($plan->name) }}</strong> plan subscription has been successfully resumed. 
                    You now have full access to all premium features again. 🚀
                  </p>

                  <!-- Subscription Details -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F0F9F2; border:1px solid #D1E7D7; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#2C9139; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #D1E7D7;">
                        Subscription Details
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA; width:50%;">Plan Name</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; font-weight:500; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ ucfirst($plan->name) }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Resumed On</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ now()->format('F j, Y \a\t g:i A') }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Next Billing Date</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; font-weight:500; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $subscription->ends_at->format('F j, Y \a\t g:i A') }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Status</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">
                        <span style="background:#10b981; color:#ffffff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">Active</span>
                      </td>
                    </tr>
                  </table>

                  <p style="margin:0 0 16px; font-size:14px; color:#4B5563;">
                    You can manage your subscription settings, update payment methods, or view billing history anytime from your dashboard.
                  </p>
                  
                  <!-- CTA Button -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:20px 0;">
                    <tr>
                      <td align="center">
                        <a href="https://www.shopittplus.com/dashboard/subscription" style="display:inline-block; background:#2C9139; color:#ffffff; text-decoration:none; padding:12px 28px; border-radius:6px; font-family:Arial,Helvetica,sans-serif; font-weight:600; font-size:14px;">Manage Subscription</a>
                      </td>
                    </tr>
                  </table>

                  <p style="margin:16px 0 0; font-size:14px;">Thank you for being a valued member of ShopittPlus!<br>The ShopittPlus Team</p>
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