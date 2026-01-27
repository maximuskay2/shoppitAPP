<!DOCTYPE html>
<html lang="en">
    <body style="margin:0; padding:0; background:#F5F7FB; color:#1F2937;">
        <div style="display:none; font-size:1px; color:#F5F7FB; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
            Promotion {{ $promotion->title }} was not approved. Review the feedback and resubmit.
        </div>

        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F5F7FB;">
            <tr>
                <td align="center" style="padding:32px 16px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.08);">
                        <!-- Brand header -->
                        <tr>
                            <td align="center" style="background:linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); padding:32px 24px;">
                                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:.2px;">
                                    ❗ Promotion Not Approved
                                </div>
                                <div style="font-family:Arial,Helvetica,sans-serif; color:#FEE2E2; font-size:13px; margin-top:6px;">
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
                                        Thanks for submitting your promotion <strong style="color:#b91c1c;">{{ $promotion->title }}</strong>. After review, it wasn’t approved this time.
                                    </p>

                                    <!-- Status Banner -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); border-radius:10px; padding:20px; margin:16px 0 20px; text-align:center;">
                                        <tr>
                                            <td align="center">
                                                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:18px; font-weight:700; margin-bottom:6px;">
                                                    Status: REJECTED
                                                </div>
                                                <div style="font-family:Arial,Helvetica,sans-serif; color:#FEE2E2; font-size:14px;">
                                                    Please review the notes below and resubmit.
                                                </div>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Promotion Details -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:16px; margin:16px 0 20px;">
                                        <tr>
                                            <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#991B1B; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #FECACA;">
                                                📋 Promotion Details
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #FDECEC; width:50%;">Title</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #FDECEC; text-align:right; word-break:break-all;">{{ $promotion->title }}</td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #FDECEC;">Description</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #FDECEC; text-align:right;">{{ $promotion->description }}</td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #FDECEC;">Discount Type</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #FDECEC; text-align:right; font-weight:600;">
                                                {{ $promotion->discount_type === 'percentage' ? 'Percentage' : 'Fixed Amount' }}
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #FDECEC;">Discount Value</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #FDECEC; text-align:right; font-weight:600;">
                                                @if($promotion->discount_type === 'percentage')
                                                    {{ $promotion->discount_value }}%
                                                @else
                                                    ₦{{ number_format($promotion->discount_value, 2) }}
                                                @endif
                                            </td>
                                        </tr>

                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0;">Requested Dates</td>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; font-weight:600; padding:10px 0; text-align:right;">{{ \Carbon\Carbon::parse($promotion->start_date)->format('M j, Y') }} — {{ \Carbon\Carbon::parse($promotion->end_date)->format('M j, Y') }}</td>
                                        </tr>
                                    </table>

                                    <!-- Reason Box -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#FEF3C7; border:1px solid #FDE68A; border-radius:10px; padding:16px; margin:16px 0 20px;">
                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; color:#92400E; font-weight:700; font-size:14px; padding-bottom:8px;">
                                                ℹ️ Reason Provided
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#78350F; line-height:1.6;">
                                                {{ $promotion->reason ?? 'Please check your email or contact our support team for more details.' }}
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Next Steps Box -->
                                    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:10px; padding:16px; margin:16px 0 20px;">
                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; color:#1E40AF; font-weight:700; font-size:14px; padding-bottom:8px;">
                                                ✅ Next Steps to Resubmit
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#1E3A8A; line-height:1.6;">
                                                • Address the feedback above<br>
                                                • Ensure discount details are correct and clear<br>
                                                • Re-submit the promotion for another review
                                            </td>
                                        </tr>
                                    </table>

                                    <p style="margin:16px 0 8px; font-size:14px; color:#4B5563;">
                                        We’re here to help you get this promotion live. Reach out if you need guidance.
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
                                    <span style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#b91c1c;">Date: {{ $currentDateTime }}</span>
                                    <span style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280; margin:0 8px;">•</span>
                                    <span style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#b91c1c;">Promotion ID: {{ $promotion->id }}</span>
                                </div>
                                <div style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#6B7280;">
                                    <a href="https://vendor.shopittplus.com" style="color:#b91c1c; text-decoration:none; margin:0 8px;">Vendor Portal</a>
                                    <a href="https://www.shopittplus.com/vendor/help" style="color:#b91c1c; text-decoration:none; margin:0 8px;">Help Center</a>
                                    <a href="mailto:vendor-support@shopittplus.com" style="color:#b91c1c; text-decoration:none; margin:0 8px;">Vendor Support</a>
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
