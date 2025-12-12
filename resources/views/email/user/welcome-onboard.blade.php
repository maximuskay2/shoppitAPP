<!DOCTYPE html>
<html lang="en">
  <body style="margin:0; padding:0; background:#F5F7FB; color:#1F2937;">
    <div style="display:none; font-size:1px; color:#F5F7FB; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
      Welcome to ShopittPlus — your all-in-one marketplace for local businesses.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F5F7FB;">
      <tr>
        <td align="center" style="padding:32px 16px;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.08);">
            <!-- Brand header -->
            <tr>
              <td align="center" style="background:#2C9139; padding:32px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:.2px;">
                  Welcome to ShopittPlus
                </div>
                <div style="font-family:Arial,Helvetica,sans-serif; color:#E8F5EA; font-size:13px; margin-top:6px;">
                  Your All-in-One Marketplace for Local Businesses
                </div>
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style="padding:28px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif; font-size:16px; line-height:1.6; color:#1F2937;">
                  <p style="margin:0 0 10px;">Hi {{ $name ?? ($user->name ?? 'there') }},</p>
                  <p style="margin:0 0 16px;">
                    Great to have you onboard! Your ShopittPlus account is ready. Whether you're here to shop from nearby stores or sell your products to local buyers, we've got you covered.
                  </p>

                  <!-- Next steps -->
                  <div style="background:#F0F9F2; border:1px solid #D1E7D7; border-radius:10px; padding:16px; margin:10px 0 18px;">
                    <div style="font-family:Arial,Helvetica,sans-serif; color:#2C9139; font-weight:700; font-size:14px; margin-bottom:8px;">
                      Get started in minutes
                    </div>
                    <ul style="margin:0; padding-left:18px; font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; line-height:1.7;">
                      <li>Complete your profile to personalize your experience.</li>
                      <li>Browse trusted local vendors and discover great deals.</li>
                      <li>For sellers: Set up your vendor profile to start listing products.</li>
                      <li>Track your orders and deliveries in real-time.</li>
                    </ul>
                  </div>

                  <p style="margin:0 0 8px; font-size:14px; color:#4B5563;">
                    Didn't create this account? Please contact support immediately.
                  </p>
                  <p style="margin:0; font-size:14px;">Welcome aboard,<br>The ShopittPlus Team</p>
                </div>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td align="center" style="background:#F3F4F6; padding:14px 16px;">
                <div style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280;">
                  &copy; {{ date('Y') }} ShopittPlus. All rights reserved.
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