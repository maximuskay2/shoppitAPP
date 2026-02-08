<?php

namespace App\Modules\Transaction\Services\Admin;

use App\Modules\Commerce\Notifications\Driver\PayoutProcessedNotification;
use App\Modules\Commerce\Models\Settings;
use App\Modules\Transaction\Models\DriverEarning;
use App\Modules\Transaction\Models\DriverPayout;
use App\Modules\User\Models\User;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DriverPayoutService
{
    public function listPayouts(Request $request)
    {
        $query = DriverPayout::query()->with('driver');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('driver', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $allowedSortFields = ['created_at', 'paid_at', 'amount', 'status'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortOrder);

        $perPage = min((int) $request->input('per_page', 15), 100);
        $payouts = $query->paginate($perPage);

        $payouts->getCollection()->transform(function ($payout) {
            return $this->mapPayout($payout);
        });

        $data = [
            'payouts' => $payouts,
        ];

        if ($request->boolean('include_pending')) {
            $data['pending_balances'] = $this->pendingBalances();
        }

        return $data;
    }

    public function approvePayout(string $driverId, ?string $reference = null): DriverPayout
    {
        $driver = User::with('driver')->find($driverId);

        if (!$driver || !$driver->driver) {
            throw new InvalidArgumentException('Driver not found.');
        }

        return DB::transaction(function () use ($driver, $reference) {
            $pendingEarnings = DriverEarning::where('driver_id', $driver->id)
                ->where('status', 'PENDING')
                ->lockForUpdate()
                ->get();

            if ($pendingEarnings->isEmpty()) {
                throw new InvalidArgumentException('No pending earnings found for this driver.');
            }

            $total = $pendingEarnings->sum(function ($earning) {
                return $earning->net_amount->getAmount()->toFloat();
            });

            $payout = DriverPayout::where('driver_id', $driver->id)
                ->where('status', 'PENDING')
                ->latest()
                ->first();

            if ($payout) {
                $payout->update([
                    'amount' => $total,
                    'currency' => $pendingEarnings->first()->currency,
                    'status' => 'PAID',
                    'reference' => $reference,
                    'paid_at' => now(),
                ]);
            } else {
                $payout = DriverPayout::create([
                    'driver_id' => $driver->id,
                    'amount' => $total,
                    'currency' => $pendingEarnings->first()->currency,
                    'status' => 'PAID',
                    'reference' => $reference,
                    'paid_at' => now(),
                ]);
            }

            DriverEarning::whereIn('id', $pendingEarnings->pluck('id'))
                ->update([
                    'status' => 'PAID',
                    'payout_id' => $payout->id,
                ]);

            $driver->notify(new PayoutProcessedNotification($payout));

            return $payout->fresh(['driver', 'earnings']);
        });
    }

    public function exportPayouts(Request $request)
    {
        $query = DriverPayout::query()->with('driver');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('driver', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSortFields = ['created_at', 'paid_at', 'amount', 'status'];

        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortOrder)->get();
    }

    public function reconcile(): array
    {
        $paidPayouts = DriverPayout::where('status', 'PAID')->get();
        $pendingEarnings = DriverEarning::where('status', 'PENDING')->get();

        $paidTotal = $paidPayouts->sum(function ($payout) {
            return $payout->amount->getAmount()->toFloat();
        });

        $pendingTotal = $pendingEarnings->sum(function ($earning) {
            return $earning->net_amount->getAmount()->toFloat();
        });

        return [
            'paid_total' => $paidTotal,
            'pending_total' => $pendingTotal,
            'paid_count' => $paidPayouts->count(),
            'pending_count' => $pendingEarnings->count(),
            'last_paid_at' => DriverPayout::where('status', 'PAID')->max('paid_at'),
            'currency' => Settings::getValue('currency') ?? 'NGN',
        ];
    }

    private function pendingBalances(): array
    {
        $pending = DriverEarning::query()
            ->select('driver_id', DB::raw('SUM(net_amount) as pending_amount'))
            ->where('status', 'PENDING')
            ->groupBy('driver_id')
            ->get();

        return $pending->map(function ($row) {
            $driver = User::find($row->driver_id);
            $currency = Settings::getValue('currency') ?? 'NGN';
            $amount = Money::ofMinor((int) $row->pending_amount, $currency)->getAmount()->toFloat();

            return [
                'driver_id' => $row->driver_id,
                'pending_amount' => $amount,
                'driver' => $driver ? [
                    'name' => $driver->name,
                    'email' => $driver->email,
                    'phone' => $driver->phone,
                ] : null,
            ];
        })->toArray();
    }

    private function mapPayout(DriverPayout $payout): array
    {
        return [
            'id' => $payout->id,
            'driver_id' => $payout->driver_id,
            'amount' => $payout->amount->getAmount()->toFloat(),
            'currency' => $payout->amount->getCurrency()->getCurrencyCode(),
            'status' => $payout->status,
            'reference' => $payout->reference,
            'paid_at' => $payout->paid_at,
            'created_at' => $payout->created_at,
            'driver' => $payout->driver ? [
                'name' => $payout->driver->name,
                'email' => $payout->driver->email,
                'phone' => $payout->driver->phone,
            ] : null,
        ];
    }
}
