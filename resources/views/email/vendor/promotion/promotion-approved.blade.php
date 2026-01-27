<!DOCTYPE html>
<html lang="en">
    <body style="margin:0; padding:0; background:#F5F7FB; color:#1F2937;">
        <div style="display:none; font-size:1px; color:#F5F7FB; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
            Promotion {{ $promotion->title }} has been approved and is now active.
        </div>

        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F5F7FB;">
            <tr>
                <td align="center" style="padding:32px 16px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.08);">
                        <!-- Brand header -->
                        <tr>
                            <td align="center" style="background:linear-gradient(135deg, #2C9139 0%, #25A244 100%); padding:32px 24px;">
                                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:24px; font-weight:700; letter-spacing:.2px;">
                                    🎉 Promotion Approved!
                                </div>
                                <div style="font-family:Arial,Helvetica,sans-serif; color:#E8F5EA; font-size:13px; margin-top:6px;">
                                    ShopittPlus Vendor Portal
                                </div>
                            </td>
                        </tr>

                        <!-- Body -->
                        <tr>
                            <td style="padding:28px 24px;">
                                <div style="font-family:Arial,Helvetica,sans-serif; font-size:16px; line-height:1.6; color:#1F2937;">
                                    <p style="margin:0 0 10px;">Hi {{ $vendor->first_name ?? $vendor->name ?? 'there' }},</p>
                                    <p style="margin:0 0 16px;">
                                        Great news! Your promotion <strong style="color:#2C9139;">{{ $promotion->title }}</strong> has been reviewed and approved. It is now live for customers.
                                    </p>

                                    <!-- Promotion Banner -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:linear-gradient(135deg, #2C9139 0%, #25A244 100%); border-radius:10px; padding:20px; margin:16px 0 20px; text-align:center;">
                                        <tr>
                                            <td align="center">
                                                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:18px; font-weight:700; margin-bottom:6px;">
                                                    🚀 Promotion is Active
                                                </div>
                                                <div style="font-family:Arial,Helvetica,sans-serif; color:#E8F5EA; font-size:14px;">
                                                    Status: <span style="font-weight:700;">APPROVED</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Promotion Details -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F0F9F2; border:1px solid #D1E7D7; border-radius:10px; padding:16px; margin:16px 0 20px;">
                                        <tr>
                                            <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#2C9139; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #D1E7D7;">
                                                📋 Promotion Details
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA; width:50%;">Title</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right; word-break:break-all;">{{ $promotion->title }}</td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Description</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $promotion->description }}</td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Discount Type</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right; font-weight:600;">
                                                {{ $promotion->discount_type === 'percentage' ? 'Percentage' : 'Fixed Amount' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Discount Value</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right; font-weight:600;">
                                                @if($promotion->discount_type === 'percentage')
                                                    {{ $promotion->discount_value }}%
                                                @else
                                                    ₦{{ number_format($promotion->discount_value, 2) }}
                                                @endif
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0;">Active Dates</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; font-weight:600; padding:10px 0; text-align:right;">{{ \Carbon\Carbon::parse($promotion->start_date)->format('M j, Y') }} — {{ \Carbon\Carbon::parse($promotion->end_date)->format('M j, Y') }}</td>
                                        </tr>
                                    </table>

                                    <!-- Next Steps Box -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#DBEAFE; border:1px solid #BFDBFE; border-radius:10px; padding:16px; margin:16px 0 20px;">
                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; color:#1E40AF; font-weight:700; font-size:14px; padding-bottom:8px;">
                                                ✅ Next Steps
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#1E3A8A; line-height:1.6;">
                                                • Monitor performance on your vendor dashboard<br>
                                                • Keep your inventory updated for this promotion<br>
                                                • Share the promotion with your audience to drive conversions
                                            </td>
                                        </tr>
                                    </table>

                                    <p style="margin:16px 0 8px; font-size:14px; color:#4B5563;">
                                        Thank you for being a valued vendor on ShopittPlus. We’re excited to see your promotion perform!
                                    </p>
                                    <p style="margin:0; font-size:14px;">Best regards,<br>The ShopittPlus Team</p>
                                </div>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td align="center" style="background:#F3F4F6; padding:20px 16px;">
                                <div style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280; margin-bottom:8px;">
                                    This email was sent by ShopittPlus Vendor Portal. For support, please contact our vendor support team.
                                </div>
                                <div style="margin-bottom:12px;">
                                    <span style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#2C9139;">Date: {{ $currentDateTime }}</span>
                                    <span style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280; margin:0 8px;">•</span>
                                    <span style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#2C9139;">Promotion ID: {{ $promotion->id }}</span>
                                </div>
                                <div style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280;">
                                    <a href="https://vendor.shopittplus.com" style="color:#2C9139; text-decoration:none; margin:0 8px;">Vendor Portal</a>
                                    <a href="https://www.shopittplus.com/vendor/help" style="color:#2C9139; text-decoration:none; margin:0 8px;">Help Center</a>
                                    <a href="mailto:vendor-support@shopittplus.com" style="color:#2C9139; text-decoration:none; margin:0 8px;">Vendor Support</a>
                                </div>
                                <div style="font-family:Arial,Helvetica,sans-serif; font-size:11px; color:#9CA3AF; margin-top:8px;">
                                    &copy; {{ date('Y') }} ShopittPlus™. All Rights Reserved.
                                </div>
                            </td>
                        </tr>
                    </table>
                    <div style="height:24px; line-height:24px; font-size:24px;">&nbsp;</div>
                </td>
            </tr>
        </table>
    </body>
</html>
