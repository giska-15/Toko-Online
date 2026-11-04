<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormmater;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ResellerPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResellerPriceController extends Controller
{
    /**
     * Display reseller's custom prices for all products
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Get all products with reseller's custom prices
        $products = Product::with(['category', 'variants', 'sizes'])
            ->with(['resellerPrices' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->get();

        return ResponseFormmater::success(
            $products,
            'Reseller prices retrieved successfully'
        );
    }

    /**
     * Set custom price for a product
     */
    public function setPrice(Request $request, $productId)
    {
        $user = auth()->user();
        
        // Only reseller can set custom prices
        if ($user->role !== 'reseller') {
            return ResponseFormmater::error(
                null,
                'Only resellers can set custom prices.',
                403
            );
        }

        $product = Product::find($productId);

        if (!$product) {
            return ResponseFormmater::error(
                null,
                'Product not found',
                404
            );
        }

        $validated = $request->validate([
            'harga_jual' => 'required|numeric|min:0',
        ]);

        // Ensure harga_jual is not lower than harga_reseller (product base price for reseller)
        if ($validated['harga_jual'] < $product->harga_reseller) {
            return ResponseFormmater::error(
                null,
                'Selling price cannot be lower than base reseller price (' . number_format($product->harga_reseller, 0, ',', '.') . ')',
                400
            );
        }

        DB::beginTransaction();
        try {
            // Update or create reseller price
            ResellerPrice::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $productId,
                ],
                [
                    'harga_jual' => $validated['harga_jual'],
                ]
            );

            DB::commit();

            return ResponseFormmater::success(
                null,
                'Reseller price set successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormmater::error(
                null,
                'Failed to set reseller price: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get reseller's custom price for a specific product
     */
    public function getPrice($productId)
    {
        $user = auth()->user();
        
        $product = Product::with(['category', 'variants', 'sizes'])
            ->with(['resellerPrices' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->find($productId);

        if (!$product) {
            return ResponseFormmater::error(
                null,
                'Product not found',
                404
            );
        }

        return ResponseFormmater::success(
            $product,
            'Product price retrieved successfully'
        );
    }

    /**
     * Delete custom price (revert to default reseller price)
     */
    public function deletePrice($productId)
    {
        $user = auth()->user();
        
        // Only reseller can delete custom prices
        if ($user->role !== 'reseller') {
            return ResponseFormmater::error(
                null,
                'Only resellers can manage custom prices.',
                403
            );
        }

        $resellerPrice = ResellerPrice::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if (!$resellerPrice) {
            return ResponseFormmater::error(
                null,
                'Custom price not found',
                404
            );
        }

        $resellerPrice->delete();

        return ResponseFormmater::success(
            null,
            'Custom price deleted successfully'
        );
    }
}
