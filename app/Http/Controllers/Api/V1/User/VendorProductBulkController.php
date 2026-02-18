<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Helpers\ShopittPlus;
use App\Http\Controllers\Controller;
use App\Modules\Commerce\Models\Product;
use App\Modules\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VendorProductBulkController extends Controller
{
    /**
     * Import products in bulk (CSV upload)
     */
    public function import(Request $request): JsonResponse
    {
        $user = User::find(Auth::id());
        $vendorId = $user->vendor->id ?? null;
        if (!$vendorId) {
            return ShopittPlus::response(false, 'Not a vendor', 403);
        }
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);
        $file = $request->file('file');
        $path = $file->storeAs('imports', Str::random(16) . '.csv');
        // TODO: Parse CSV and create products (simplified for demo)
        Log::info('Bulk import file uploaded', ['vendor_id' => $vendorId, 'path' => $path]);
        return ShopittPlus::response(true, 'Bulk product import received. Processing...', 202);
    }

    /**
     * Export products in bulk (CSV download)
     */
    public function export(): JsonResponse
    {
        $user = User::find(Auth::id());
        $vendorId = $user->vendor->id ?? null;
        if (!$vendorId) {
            return ShopittPlus::response(false, 'Not a vendor', 403);
        }
        $products = Product::where('vendor_id', $vendorId)->get(['id', 'name', 'price', 'stock']);
        $csv = "id,name,price,stock\n";
        foreach ($products as $p) {
            $csv .= "{$p->id},{$p->name},{$p->price},{$p->stock}\n";
        }
        $filename = 'products_export_' . now()->format('Ymd_His') . '.csv';
        Storage::disk('local')->put('exports/' . $filename, $csv);
        $url = Storage::disk('local')->url('exports/' . $filename);
        return ShopittPlus::response(true, 'Bulk product export ready.', 200, ['download_url' => $url]);
    }
}
