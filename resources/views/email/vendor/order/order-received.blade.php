<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Order Received</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.95;
        }
        .content {
            padding: 30px 20px;
        }
        .success-badge {
            background-color: #d1fae5;
            color: #065f46;
            padding: 12px 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
            font-size: 15px;
            border-left: 4px solid #10b981;
        }
        .order-summary {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .order-summary h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #111827;
            border-bottom: 2px solid #10b981;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6b7280;
            font-weight: 500;
        }
        .info-value {
            color: #111827;
            font-weight: 600;
            text-align: right;
        }
        .customer-info {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .customer-info h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #1e40af;
        }
        .customer-detail {
            padding: 8px 0;
            color: #1e3a8a;
        }
        .customer-detail strong {
            display: inline-block;
            width: 100px;
            color: #3b82f6;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        .items-table th {
            background-color: #10b981;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .total-amount {
            background-color: #dcfce7;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 25px;
        }
        .total-amount .label {
            color: #065f46;
            font-size: 14px;
            margin-bottom: 5px;
        }
        .total-amount .amount {
            color: #047857;
            font-size: 32px;
            font-weight: 700;
        }
        .action-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .action-box p {
            margin: 0;
            color: #78350f;
            font-size: 14px;
            line-height: 1.5;
        }
        .action-box strong {
            color: #92400e;
        }
        .button {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin: 10px 0;
            text-align: center;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #10b981;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🎉 New Order Received!</h1>
            <p>You have a new order from {{ $order->receiver_name }}</p>
        </div>

        <div class="content">
            <div class="success-badge">
                ✅ Order #{{ $order->tracking_id }} has been placed successfully
            </div>

            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="info-row">
                    <span class="info-label">Order ID:</span>
                    <span class="info-value">#{{ $order->tracking_id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Order Date:</span>
                    <span class="info-value">{{ $order->created_at->format('M d, Y - h:i A') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Status:</span>
                    <span class="info-value" style="color: #10b981;">{{ $order->status }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Items Count:</span>
                    <span class="info-value">{{ $order->lineItems->count() }} {{ $order->lineItems->count() === 1 ? 'item' : 'items' }}</span>
                </div>
            </div>

            <div class="customer-info">
                <h3>📦 Delivery Information</h3>
                <div class="customer-detail">
                    <strong>Name:</strong> {{ $order->receiver_name }}
                </div>
                <div class="customer-detail">
                    <strong>Email:</strong> {{ $order->receiver_email }}
                </div>
                <div class="customer-detail">
                    <strong>Phone:</strong> {{ $order->receiver_phone }}
                </div>
                <div class="customer-detail">
                    <strong>Address:</strong> {{ $order->receiver_delivery_address }}
                </div>
                @if($order->order_notes)
                <div class="customer-detail">
                    <strong>Notes:</strong> {{ $order->order_notes }}
                </div>
                @endif
                @if($order->is_gift)
                <div class="customer-detail">
                    <strong>🎁 Gift:</strong> Yes
                </div>
                @endif
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->lineItems as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td style="text-align: center;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">{{ $currency }} {{ number_format($item->unit_price_amount->getAmount()->toFloat(), 2) }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ $currency }} {{ number_format($item->total_price_amount->getAmount()->toFloat(), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="background-color: #f9fafb; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                    <span class="info-label">Subtotal:</span>
                    <span class="info-value">{{ $currency }} {{ number_format($grossTotal, 2) }}</span>
                </div>
                @if($couponDiscount > 0)
                <div style="display: flex; justify-content: space-between; padding: 8px 0; color: #10b981;">
                    <span class="info-label">Discount ({{ $order->coupon_code }}):</span>
                    <span class="info-value">-{{ $currency }} {{ number_format($couponDiscount, 2) }}</span>
                </div>
                @endif
                <div style="display: flex; justify-content: space-between; padding: 8px 0;">
                    <span class="info-label">Delivery Fee:</span>
                    <span class="info-value">{{ $currency }} {{ number_format($deliveryFee, 2) }}</span>
                </div>
            </div>

            <div class="total-amount">
                <div class="label">Total Order Amount</div>
                <div class="amount">{{ $currency }} {{ number_format($netTotal + $deliveryFee, 2) }}</div>
            </div>

            <div class="action-box">
                <p><strong>⚡ Action Required:</strong> Please prepare this order for dispatch. Update the order status once items are ready for delivery.</p>
            </div>

            <div style="text-align: center;">
                <a href="{{ config('app.vendor_dashboard_url') }}/orders/{{ $order->id }}" class="button">
                    View Order Details
                </a>
            </div>

            <p style="margin-top: 25px; color: #6b7280; font-size: 14px; text-align: center;">
                Need help? Contact our support team at <a href="mailto:{{ config('app.support_email') }}" style="color: #10b981; text-decoration: none;">{{ config('app.support_email') }}</a>
            </p>
        </div>

        <div class="footer">
            <p><strong>ShopittPlus</strong></p>
            <p>Your trusted marketplace platform</p>
            <p>&copy; {{ date('Y') }} ShopittPlus. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
