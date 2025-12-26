<!DOCTYPE html>
<html lang="en">
  <body style="margin:0; padding:0; background:#F5F7FB; color:#1F2937;">
    <div style="display:none; font-size:1px; color:#F5F7FB; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
      New order {{ $order->tracking_id }} received from {{ $order->receiver_name }} for {{ $currency }} {{ number_format($netTotal + $deliveryFee, 2) }}.
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F5F7FB;">
      <tr>
        <td align="center" style="padding:32px 16px;">
          <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px; width:100%; background:#FFFFFF; border-radius:12px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.08);">
            <!-- Brand header -->
            <tr>
              <td align="center" style="background:linear-gradient(135deg, #2C9139 0%, #25A244 100%); padding:32px 24px;">
                <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:24px; font-weight:700; letter-spacing:.2px;">
                  🎉 New Order Received!
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
                    Great news! You have received a new order <strong style="color:#2C9139;">{{ $order->tracking_id }}</strong> from {{ $order->is_gift ? $order->receiver_name : $order->user->name }}. 
                    Please prepare this order for dispatch.
                  </p>

                  <!-- New Order Banner -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:linear-gradient(135deg, #2C9139 0%, #25A244 100%); border-radius:10px; padding:20px; margin:16px 0 20px; text-align:center;">
                    <tr>
                      <td align="center">
                        <div style="font-family:Arial,Helvetica,sans-serif; color:#ffffff; font-size:18px; font-weight:700; margin-bottom:6px;">
                          📦 Order Total
                        </div>
                        <div style="font-family:Arial,Helvetica,sans-serif; color:#E8F5EA; font-size:28px; font-weight:700; margin-bottom:4px;">
                          {{ $currency }} {{ number_format($netTotal + $deliveryFee, 2) }}
                        </div>
                        <div style="font-family:Arial,Helvetica,sans-serif; color:#E8F5EA; font-size:14px;">
                          {{ $order->lineItems->count() }} {{ $order->lineItems->count() === 1 ? 'item' : 'items' }} • Status: {{ strtoupper($order->status) }}
                        </div>
                      </td>
                    </tr>
                  </table>

                  <!-- Order Details -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F0F9F2; border:1px solid #D1E7D7; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#2C9139; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #D1E7D7;">
                        📋 Order Information
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA; width:50%;">Order ID</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right; word-break:break-all;">{{ $order->tracking_id }}</td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Order Date</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">{{ $order->created_at->format('F j, Y \a\t g:i A') }}</td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Payment Status</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right;">
                        <span style="background:#10b981; color:#ffffff; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600;">{{ strtoupper($order->status) }}</span>
                      </td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0; border-bottom:1px solid #E8F5EA;">Payment Reference</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #E8F5EA; text-align:right; word-break:break-all;">{{ $order->payment_reference }}</td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#374151; padding:10px 0;">Items Count</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; font-weight:600; padding:10px 0; text-align:right;">{{ $order->lineItems->count() }} {{ $order->lineItems->count() === 1 ? 'item' : 'items' }}</td>
                    </tr>
                  </table>

                  <!-- Customer Information -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#EFF6FF; border:1px solid #BFDBFE; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#1E40AF; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #BFDBFE;">
                        👤 Customer & Delivery Details
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1E3A8A; padding:10px 0; border-bottom:1px solid #DBEAFE; width:40%;">Customer Name</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #DBEAFE; text-align:right;">{{ $order->is_gift ? $order->receiver_name : $order->user->name }}</td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1E3A8A; padding:10px 0; border-bottom:1px solid #DBEAFE;">Email</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#111827; padding:10px 0; border-bottom:1px solid #DBEAFE; text-align:right; word-break:break-all;">{{ $order->is_gift ? $order->receiver_email : $order->user->email }}</td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1E3A8A; padding:10px 0; border-bottom:1px solid #DBEAFE;">Phone</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#111827; padding:10px 0; border-bottom:1px solid #DBEAFE; text-align:right;">{{ $order->is_gift ? $order->receiver_phone : $order->user->phone }}</td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1E3A8A; padding:10px 0; @if($order->order_notes || $order->is_gift) border-bottom:1px solid #DBEAFE; @endif" colspan="2">
                        <strong>Delivery Address:</strong><br>
                        <span style="color:#111827;">{{ $order->is_gift ? $order->receiver_delivery_address : $order->user->address . ", " . $order->user->city . ", " . $order->user->state }}</span>
                      </td>
                    </tr>

                    @if($order->order_notes)
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1E3A8A; padding:10px 0; @if($order->is_gift) border-bottom:1px solid #DBEAFE; @endif" colspan="2">
                        <strong>Order Notes:</strong><br>
                        <span style="color:#111827;">{{ $order->order_notes }}</span>
                      </td>
                    </tr>
                    @endif

                    @if($order->is_gift)
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#1E3A8A; padding:10px 0;" colspan="2">
                        <strong>🎁 This is a Gift Order</strong>
                      </td>
                    </tr>
                    @endif
                  </table>

                  <!-- Order Items -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#F9FAFB; border:1px solid #E5E7EB; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#111827; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #D1D5DB;">
                        🛍️ Order Items
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

                  <!-- Price Breakdown -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#FEF3C7; border:1px solid #FDE68A; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td colspan="2" style="font-family:Arial,Helvetica,sans-serif; color:#92400E; font-weight:700; font-size:14px; padding-bottom:10px; border-bottom:1px solid #FDE68A;">
                        💰 Price Breakdown
                      </td>
                    </tr>
                    
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#78350F; padding:10px 0; border-bottom:1px solid #FEF3C7; width:50%;">Subtotal</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#451A03; padding:10px 0; border-bottom:1px solid #FEF3C7; text-align:right;">{{ $currency }} {{ number_format($grossTotal, 2) }}</td>
                    </tr>

                    @if($couponDiscount > 0)
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#78350F; padding:10px 0; border-bottom:1px solid #FEF3C7;">
                        Discount
                        @if($order->coupon_code)
                        <span style="font-size:12px;">({{ $order->coupon_code }})</span>
                        @endif
                      </td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#ef4444; padding:10px 0; border-bottom:1px solid #FEF3C7; text-align:right;">-{{ $currency }} {{ number_format($couponDiscount, 2) }}</td>
                    </tr>
                    @endif

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#78350F; padding:10px 0; border-bottom:1px solid #FEF3C7;">Delivery Fee</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#451A03; padding:10px 0; border-bottom:1px solid #FEF3C7; text-align:right;">{{ $currency }} {{ number_format($deliveryFee, 2) }}</td>
                    </tr>

                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:16px; color:#111827; font-weight:700; padding:10px 0;">Total Amount</td>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:16px; color:#2C9139; font-weight:700; padding:10px 0; text-align:right;">{{ $currency }} {{ number_format($netTotal + $deliveryFee, 2) }}</td>
                    </tr>
                  </table>

                  <!-- Action Required Box -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#FEF2F2; border:1px solid #FECACA; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; color:#991B1B; font-weight:700; font-size:14px; padding-bottom:8px;">
                        ⚡ Action Required
                      </td>
                    </tr>
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#7F1D1D; line-height:1.6;">
                        • Prepare this order for dispatch<br>
                        • Update the order status once items are ready<br>
                        • Ensure all items are properly packaged<br>
                        • Contact customer if you need clarification on the order
                      </td>
                    </tr>
                  </table>

                  <!-- Information Box -->
                  <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#DBEAFE; border:1px solid #BFDBFE; border-radius:10px; padding:16px; margin:16px 0 20px;">
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; color:#1E40AF; font-weight:700; font-size:14px; padding-bottom:8px;">
                        ℹ️ Important Information
                      </td>
                    </tr>
                    <tr>
                      <td style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#1E3A8A; line-height:1.6;">
                        • Payment has been confirmed and secured<br>
                        • Your settlement will be processed after order completion<br>
                        • Keep customers updated on their order status<br>
                        • Provide tracking information once items are dispatched
                      </td>
                    </tr>
                  </table>

                  <p style="margin:16px 0 8px; font-size:14px; color:#4B5563;">
                    Thank you for being a valued vendor on ShopittPlus. Keep delivering excellent service to your customers!
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
                  <a href="https://vendor.shopittplus.com" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#2C9139; text-decoration:none; margin:0 8px;">Vendor Portal</a>
                  <a href="https://www.shopittplus.com/vendor/help" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#2C9139; text-decoration:none; margin:0 8px;">Help Center</a>
                  <a href="mailto:vendor-support@shopittplus.com" style="font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#2C9139; text-decoration:none; margin:0 8px;">Vendor Support</a>
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
