<!DOCTYPE html>
<html lang="en">
  <body style="margin:0; padding:0; background:#F5F7FB; color:#1F2937;">
    <div style="display:none; font-size:1px; color:#F5F7FB; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
      Your order {{ $order->tracking_id }} has been dispatched and is on its way!
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F5F7FB;">
      <tr>
        <td align="center" style="padding:32px 16px;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.08);">
            <!-- Brand header -->
            <tr>
              <td align="center" style="background:#2C9139; padding:32px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:22px; font-weight:700; letter-spacing:.2px;">
                  Order Dispatched 🚚
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
                    Great news! Your order <strong style="color:#2C9139;">{{ $order->tracking_id }}</strong> has been dispatched and is now on its way to you! 🎉
                  </p>

                  <!-- Dispatch Status Banner -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:linear-gradient(135deg, #2C9139 0%, #25A244 100%); border-radius:10px; padding:20px; margin:16px 0 20px; text-align:center;">
                    <tr>
                      <td align="center">
                        <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:18px; font-weight:700; margin-bottom:6px;">
                          📦 Out for Delivery
                        </div>
                        <div style="font-family:Arial,Helvetica,sans-serif; color:#E8F5EA; font-size:14px;">
                          Your package is on its way to your delivery address
                        </div>
                      </td>
                    </tr>
                  </table>

                  <!-- Order Details -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F0F9F2; border:1px solid #D1E7D7; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#2C9139; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #D1E7D7;">
                        Order Summary
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA; width:50%;">Order ID</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right; word-break:break-all;">{{ $order->tracking_id }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Status</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">
                        <span style="background:#10b981; color:#ffffff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">{{ strtoupper($order->status) }}</span>
                      </td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Order Date</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $order->created_at->format('F j, Y') }}</td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Dispatched On</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#2C9139; font-weight:600; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $order->updated_at->format('F j, Y \a\t g:i A') }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Subtotal</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $currency }} {{ number_format($grossTotal, 2) }}</td>
                    </tr>

                    @if($couponDiscount > 0)
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">
                        Coupon Discount
                        @if($order->coupon_code)
                        <span style="font-size:12px; color:#2C9139;">({{ $order->coupon_code }})</span>
                        @endif
                      </td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#ef4444; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">-{{ $currency }} {{ number_format($couponDiscount, 2) }}</td>
                    </tr>
                    @endif

                    @if($deliveryFee > 0)
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Delivery Fee</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $currency }} {{ number_format($deliveryFee, 2) }}</td>
                    </tr>
                    @endif
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:16px; color:#111827; font-weight:700; padding:10px 0;">Total Amount</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:16px; color:#2C9139; font-weight:700; padding:10px 0; text-align:right;">{{ $currency }} {{ number_format($netTotal + $deliveryFee, 2) }}</td>
                    </tr>
                  </table>

                  <!-- Delivery Details -->
                  @if($order->receiver_delivery_address)
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#FEF3C7; border:1px solid #FDE68A; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; color:#92400E; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #FDE68A;">
                        📍 Delivery Address
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#78350F; padding-top:10px;">
                        <strong>{{ $order->receiver_name ?? $user->name }}</strong><br>
                        @if($order->receiver_phone)
                        📞 {{ $order->receiver_phone }}<br>
                        @endif
                        @if($order->receiver_email)
                        ✉️ {{ $order->receiver_email }}<br>
                        @endif
                        📦 {{ $order->receiver_delivery_address }}
                      </td>
                    </tr>
                  </table>
                  @endif

                  <!-- Order Items -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#111827; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #D1D5DB;">
                        📦 Items Being Delivered
                      </td>
                    </tr>
                    
                    @foreach($order->lineItems as $item)
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #F3F4F6;">
                        <strong>{{ $item->product->name }}</strong><br>
                        <span style="font-size:12px; color:#6B7280;">Qty: {{ $item->quantity }} × {{ $currency }} {{ number_format($item->price->getAmount()->toFloat(), 2) }}</span>
                      </td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #F3F4F6; text-align:right; vertical-align:top;">
                        {{ $currency }} {{ number_format($item->subtotal->getAmount()->toFloat(), 2) }}
                      </td>
                    </tr>
                    @endforeach
                  </table>

                  <!-- Vendor Info -->
                  @if($order->vendor)
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#EFF6FF; border:1px solid #DBEAFE; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; color:#1E40AF; font-weight:700; font-size:14px; padding-bottom:8px;">
                        🏪 Shipped By
                      </td>
                    </tr>
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1E3A8A;">
                        <strong>{{ $order->vendor->business_name ?? $order->vendor->user->name }}</strong>
                        @if($order->vendor->user->phone)
                        <br>📞 {{ $order->vendor->user->phone }}
                        @endif
                      </td>
                    </tr>
                  </table>
                  @endif

                  <!-- Delivery Tips -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F0FDFA; border:1px solid #99F6E4; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; color:#115E59; font-weight:700; font-size:14px; padding-bottom:8px;">
                        💡 Delivery Tips
                      </td>
                    </tr>
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#134E4A; line-height:1.6;">
                        • Please ensure someone is available to receive the package<br>
                        • Have your order ID ready for verification<br>
                        • Inspect items upon delivery before signing<br>
                        • Contact support immediately if there are any issues
                      </td>
                    </tr>
                  </table>

                  @if($order->order_notes)
                  <p style="margin:16px 0 8px; font-size:14px; color:#4B5563;">
                    <strong>Order Notes:</strong><br>
                    {{ $order->order_notes }}
                  </p>
                  @endif

                  <p style="margin:16px 0 8px; font-size:14px; color:#4B5563;">
                    You can track your order status anytime from your account dashboard. If you have any questions or concerns, please don't hesitate to contact our support team.
                  </p>
                  <p style="margin:0; font-size:14px;">Thank you for shopping with ShopittPlus!<br>The ShopittPlus Team</p>
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
