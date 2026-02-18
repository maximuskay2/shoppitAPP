<?php

namespace App\Http\Controllers\Api\V1\Admin\Notifications;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Helpers\ShopittPlus;
use Illuminate\Http\JsonResponse;

class NotificationAnalyticsController
{
    public function fcmMetrics(Request $request): JsonResponse
    {
        $total = Cache::get('metrics:notifications:fcm:total', 0);
        $failed = Cache::get('metrics:notifications:fcm:failed', 0);
        $deliveryRate = $total > 0 ? round((($total - $failed) / $total) * 100, 2) : 0;
        $failureRate = $total > 0 ? round(($failed / $total) * 100, 2) : 0;
        return ShopittPlus::response(true, 'FCM notification metrics fetched', 200, [
            'total_sent' => $total,
            'total_failed' => $failed,
            'delivery_rate' => $deliveryRate,
            'failure_rate' => $failureRate,
        ]);
    }
}
