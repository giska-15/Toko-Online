<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormmater;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductSize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductManagementController extends Controller
{
    /**
     * Display a listing of all products (for management)
     */
    public function index(Request $request)
    {
        $products = Product::with(['category', 'variants', 'sizes'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseFormmater::success(
            $products,
            'Products retrieved successfully'
        );
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        // Only developer, owner, admin can create products
        if (!in_array($user->role, ['developer', 'owner', 'admin'])) {
            return ResponseFormmater::error(
                null,
                'Unauthorized. Only developer, owner, or admin can create products.',
                403
            );
        }

        $validated = $request->validate([
            'name_product' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price_product' => 'required|numeric|min:0',
            'harga_reseller' => 'required|numeric|min:0',
            'harga_owner' => 'required|numeric|min:0',
            'description_product' => 'nullable|string',
            'photo_product' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock_product' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $product = new Product();
            $product->name_product = $validated['name_product'];
            $product->category_id = $validated['category_id'];
            $product->price_product = $validated['price_product'];
            $product->harga_reseller = $validated['harga_reseller'];
            $product->harga_owner = $validated['harga_owner'];
            $product->description_product = $validated['description_product'] ?? null;
            $product->stock_product = $validated['stock_product'];
            
            if ($request->hasFile('photo_product')) {
                $photo = $request->file('photo_product');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $photo->storeAs('public/products', $photoName);
                $product->photo_product = $photoName;
            }
            
            $product->save();

            // Handle variants
            if ($request->has('variants')) {
                $variants = json_decode($request->variants, true);
                foreach ($variants as $variantData) {
                    $variant = new ProductVariant();
                    $variant->product_id = $product->id;
                    $variant->name_variant = $variantData['name_variant'];
                    $variant->stock_variant = $variantData['stock_variant'] ?? 0;
                    $variant->photo_variant = $variantData['photo_variant'] ?? null;
                    $variant->save();
                }
            }

            // Handle sizes
            if ($request->has('sizes')) {
                $sizes = json_decode($request->sizes, true);
                foreach ($sizes as $sizeData) {
                    $size = new ProductSize();
                    $size->product_id = $product->id;
                    $size->name_size = $sizeData['name_size'];
                    $size->stock_size = $sizeData['stock_size'] ?? 0;
                    $size->save();
                }
            }

            DB::commit();

            return ResponseFormmater::success(
                $product->load(['category', 'variants', 'sizes']),
                'Product created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormmater::error(
                null,
                'Failed to create product: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        $user = auth()->user();
        
        // Only developer, owner, admin can view management details
        if (!in_array($user->role, ['developer', 'owner', 'admin'])) {
            return ResponseFormmater::error(
                null,
                'Unauthorized',
                403
            );
        }

        $product = Product::with(['category', 'variants', 'sizes'])->find($id);

        if (!$product) {
            return ResponseFormmater::error(
                null,
                'Product not found',
                404
            );
        }

        return ResponseFormmater::success(
            $product,
            'Product retrieved successfully'
        );
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        
        // Only developer, owner, admin can update products
        if (!in_array($user->role, ['developer', 'owner', 'admin'])) {
            return ResponseFormmater::error(
                null,
                'Unauthorized. Only developer, owner, or admin can update products.',
                403
            );
        }

        $product = Product::find($id);

        if (!$product) {
            return ResponseFormmater::error(
                null,
                'Product not found',
                404
            );
        }

        $validated = $request->validate([
            'name_product' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'price_product' => 'sometimes|numeric|min:0',
            'harga_reseller' => 'sometimes|numeric|min:0',
            'harga_owner' => 'sometimes|numeric|min:0',
            'description_product' => 'nullable|string',
            'photo_product' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock_product' => 'sometimes|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            if (isset($validated['name_product'])) {
                $product->name_product = $validated['name_product'];
            }
            if (isset($validated['category_id'])) {
                $product->category_id = $validated['category_id'];
            }
            if (isset($validated['price_product'])) {
                $product->price_product = $validated['price_product'];
            }
            if (isset($validated['harga_reseller'])) {
                $product->harga_reseller = $validated['harga_reseller'];
            }
            if (isset($validated['harga_owner'])) {
                $product->harga_owner = $validated['harga_owner'];
            }
            if (array_key_exists('description_product', $validated)) {
                $product->description_product = $validated['description_product'];
            }
            if (isset($validated['stock_product'])) {
                $product->stock_product = $validated['stock_product'];
            }
            
            if ($request->hasFile('photo_product')) {
                // Delete old photo
                if ($product->photo_product) {
                    Storage::delete('public/products/' . $product->photo_product);
                }
                
                $photo = $request->file('photo_product');
                $photoName = time() . '_' . $photo->getClientOriginalName();
                $photo->storeAs('public/products', $photoName);
                $product->photo_product = $photoName;
            }
            
            $product->save();

            // Update variants if provided
            if ($request->has('variants')) {
                // Delete old variants
                $product->variants()->delete();
                
                $variants = json_decode($request->variants, true);
                foreach ($variants as $variantData) {
                    $variant = new ProductVariant();
                    $variant->product_id = $product->id;
                    $variant->name_variant = $variantData['name_variant'];
                    $variant->stock_variant = $variantData['stock_variant'] ?? 0;
                    $variant->photo_variant = $variantData['photo_variant'] ?? null;
                    $variant->save();
                }
            }

            // Update sizes if provided
            if ($request->has('sizes')) {
                // Delete old sizes
                $product->sizes()->delete();
                
                $sizes = json_decode($request->sizes, true);
                foreach ($sizes as $sizeData) {
                    $size = new ProductSize();
                    $size->product_id = $product->id;
                    $size->name_size = $sizeData['name_size'];
                    $size->stock_size = $sizeData['stock_size'] ?? 0;
                    $size->save();
                }
            }

            DB::commit();

            return ResponseFormmater::success(
                $product->load(['category', 'variants', 'sizes']),
                'Product updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormmater::error(
                null,
                'Failed to update product: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {
        $user = auth()->user();
        
        // Only developer and owner can delete products
        if (!in_array($user->role, ['developer', 'owner'])) {
            return ResponseFormmater::error(
                null,
                'Unauthorized. Only developer or owner can delete products.',
                403
            );
        }

        $product = Product::find($id);

        if (!$product) {
            return ResponseFormmater::error(
                null,
                'Product not found',
                404
            );
        }

        DB::beginTransaction();
        try {
            // Delete variants and sizes first
            $product->variants()->delete();
            $product->sizes()->delete();
            
            // Delete photo
            if ($product->photo_product) {
                Storage::delete('public/products/' . $product->photo_product);
            }
            
            $product->delete();

            DB::commit();

            return ResponseFormmater::success(
                null,
                'Product deleted successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormmater::error(
                null,
                'Failed to delete product: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Get all categories
     */
    public function categories()
    {
        $categories = \App\Models\Category::all();
        
        return ResponseFormmater::success(
            $categories,
            'Categories retrieved successfully'
        );
    }
}
