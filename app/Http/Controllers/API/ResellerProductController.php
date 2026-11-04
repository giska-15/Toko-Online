<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResellerProductController extends Controller
{
    /**
     * Get all products for the authenticated reseller
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Check if user is reseller or admin
        if (!in_array($user->role, ['reseller', 'admin', 'owner'])) {
            return ResponseFormmater::error([
                'message' => 'Unauthorized. You must be a reseller to access this.'
            ], 'Unauthorized', 403);
        }

        $products = Product::where('user_id', $user->id)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseFormmater::success($products, 'Product list retrieved successfully');
    }

    /**
     * Create a new product
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!in_array($user->role, ['reseller', 'admin', 'owner'])) {
            return ResponseFormmater::error([
                'message' => 'Unauthorized. You must be a reseller to add products.'
            ], 'Unauthorized', 403);
        }

        $validator = Validator::make($request->all(), [
            'name_product' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'description' => 'nullable|string',
            'price_product' => 'required|integer|min:0',
            'price_strike' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return ResponseFormmater::error([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 'Validation Error', 422);
        }

        $productData = [
            'name_product' => $request->name_product,
            'category_id' => $request->category_id,
            'description' => $request->description ?? '',
            'price_product' => $request->price_product,
            'price_strike' => $request->price_strike,
            'user_id' => $user->id
        ];

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $extensions = $image->getClientOriginalExtension();
            $productImage = "product/".date('YmdHis').".".$extensions;
            $uploadPath = env('UPLOAD_PATH', 'uploads');
            $image->move($uploadPath, $productImage);
            $productData['image_product'] = $productImage;
        }

        $product = Product::create($productData);

        return ResponseFormmater::success($product, 'Product created successfully', 201);
    }

    /**
     * Get a specific product
     */
    public function show($id)
    {
        $user = Auth::user();
        
        $product = Product::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$product) {
            return ResponseFormmater::error([
                'message' => 'Product not found'
            ], 'Not Found', 404);
        }

        return ResponseFormmater::success($product, 'Product retrieved successfully');
    }

    /**
     * Update a product
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        
        $product = Product::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$product) {
            return ResponseFormmater::error([
                'message' => 'Product not found'
            ], 'Not Found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name_product' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|integer',
            'description' => 'nullable|string',
            'price_product' => 'sometimes|integer|min:0',
            'price_strike' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return ResponseFormmater::error([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 'Validation Error', 422);
        }

        $productData = $request->only([
            'name_product', 'category_id', 'description', 'price_product', 'price_strike'
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image_product) {
                Storage::disk('upload')->delete($product->image_product);
            }
            
            $image = $request->file('image');
            $extensions = $image->getClientOriginalExtension();
            $productImage = "product/".date('YmdHis').".".$extensions;
            $uploadPath = env('UPLOAD_PATH', 'uploads');
            $image->move($uploadPath, $productImage);
            $productData['image_product'] = $productImage;
        }

        $product->update($productData);

        return ResponseFormmater::success($product, 'Product updated successfully');
    }

    /**
     * Delete a product
     */
    public function destroy($id)
    {
        $user = Auth::user();
        
        $product = Product::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$product) {
            return ResponseFormmater::error([
                'message' => 'Product not found'
            ], 'Not Found', 404);
        }

        // Delete image if exists
        if ($product->image_product) {
            Storage::disk('upload')->delete($product->image_product);
        }

        $product->delete();

        return ResponseFormmater::success(null, 'Product deleted successfully');
    }

    /**
     * Get categories for product creation
     */
    public function categories()
    {
        $categories = Category::all();
        return ResponseFormmater::success($categories, 'Categories retrieved successfully');
    }
}
