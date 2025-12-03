<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment Test Page</title>
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background-color: #218838;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .webhook-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
        .webhook-section h3 {
            margin-bottom: 15px;
        }
        .webhook-payload {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payment Test Page</h1>
        <p>Use this page to test Paystack payment integration.</p>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" value="test@example.com" required>
        </div>

        <div class="form-group">
            <label for="amount">Amount (in Naira):</label>
            <input type="number" id="amount" value="1000" min="100" required>
        </div>

        <div class="form-group">
            <label for="currency">Currency:</label>
            <select id="currency">
                <option value="NGN">NGN</option>
                <option value="USD">USD</option>
                <option value="GHS">GHS</option>
            </select>
        </div>

        <div class="form-group">
            <label for="reference">Reference (optional):</label>
            <input type="text" id="reference" placeholder="Auto-generated if empty">
        </div>

        <button onclick="initializePayment()">Initialize Payment</button>

        <div id="result" class="result" style="display: none;"></div>

        <div class="webhook-section">
            <h3>Webhook Testing</h3>
            <p>Recent webhook events will appear here:</p>
            <div id="webhook-events" class="webhook-payload">
                No webhook events received yet.
            </div>
            <button onclick="clearWebhookEvents()">Clear Events</button>
        </div>
    </div>

    <script>
        let paystackPublicKey = '{{ config("services.paystack.public_key") }}';

        function initializePayment() {
            const email = document.getElementById('email').value;
            const amount = document.getElementById('amount').value;
            const currency = document.getElementById('currency').value;
            const reference = document.getElementById('reference').value || 'TEST-' + Date.now();

            if (!email || !amount) {
                showResult('Please fill in all required fields', 'error');
                return;
            }

            const handler = PaystackPop.setup({
                key: paystackPublicKey,
                email: email,
                amount: amount * 100, // Convert to kobo
                currency: currency,
                ref: reference,
                callback: function(response) {
                    showResult('Payment successful! Reference: ' + response.reference, 'success');
                    console.log('Payment completed:', response);

                    verifyPayment(response.reference);
                },
                onClose: function() {
                    showResult('Payment window closed', 'error');
                }
            });

            handler.openIframe();
        }

        function verifyPayment(reference) {
            fetch('/api/verify-payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ reference: reference })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Payment verification:', data);
                showResult('Payment verified: ' + JSON.stringify(data), 'success');
            })
            .catch(error => {
                console.error('Verification error:', error);
                showResult('Payment verification failed', 'error');
            });
        }

        function showResult(message, type) {
            const resultDiv = document.getElementById('result');
            resultDiv.textContent = message;
            resultDiv.className = 'result ' + type;
            resultDiv.style.display = 'block';

            // Auto-hide after 10 seconds
            setTimeout(() => {
                resultDiv.style.display = 'none';
            }, 10000);
        }

        function clearWebhookEvents() {
            document.getElementById('webhook-events').textContent = 'No webhook events received yet.';
        }

        // Simulate webhook events for testing (in a real app, this would come from your webhook endpoint)
        function addWebhookEvent(event) {
            const webhookDiv = document.getElementById('webhook-events');
            const timestamp = new Date().toLocaleString();
            webhookDiv.textContent += `\n[${timestamp}] ${JSON.stringify(event, null, 2)}\n---`;
            webhookDiv.scrollTop = webhookDiv.scrollHeight;
        }

        // Example webhook events for testing
        setTimeout(() => {
            addWebhookEvent({
                event: 'charge.success',
                data: {
                    reference: 'TEST-' + Date.now(),
                    amount: 100000,
                    currency: 'NGN',
                    status: 'success'
                }
            });
        }, 5000);
    </script>
</body>
</html>