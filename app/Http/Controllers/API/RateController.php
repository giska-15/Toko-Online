<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormmater;
use App\Http\Controllers\Controller;
use App\Models\Rate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RateController extends Controller
{

    public function storeRate (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'product_id' => 'sometimes|exists:products,id',
            'variant_rate' => 'required|string|max:255',
            'size_rate' => 'required|string|max:255',
            'category_rate' => 'required|string|max:255',
            'image_rate' => 'required|string|max:255',
            'rating' => '',
            'reviews' => '',
        ]);


        if($validator->fails()) {
            return ResponseFormmater::error(
                null,
                $validator->errors(),
                500
            );
        }

        $rate = Rate::create([
            'user_id' => $request->user_id,
            'product_id' => $request->product_id,
            'variant_rate' => $request->variant_rate,
            'size_rate' => $request->size_rate,
            'category_rate' => $request->category_rate,
            'image_rate' => $request->image_rate,
            'rating' => $request->rating,
            'reviews' => $request->reviews
        ]);


        try {
            $rate->save();
            return ResponseFormmater::success(
                $rate,
                'Data Rate  Berhasil di tambahkan'
            );
        }

        catch(Exception $error) {
            return ResponseFormmater::error(
                $error->getMessage(),
                'Data Rate  tidak ada',
                404
            );
        }
    }

    public function Rating(Request $request)
    {
        $id = $request->input('id');
        $user_id = $request->input('user_id');
        $product_id = $request->input('product_id');
        $variant_name = $request->input('variant_name');
    
        // Jika ada ID spesifik, cari berdasarkan ID
        if ($id) {
            $rate = Rate::select('user_id', 'product_id')->find($id);
    
            if ($rate) {
                return ResponseFormmater::success(
                    $rate,
                    'Data Rate Berhasil diambil'
                );
            } else {
                return ResponseFormmater::error(
                    null,
                    'Data Rate tidak ada',
                    404
                );
            }
        }
    
        // Query dasar dengan kondisi yang ketat
        $rate = Rate::select('rates.*')
            ->when($user_id, function($query) use ($user_id) {
                return $query->where('user_id', $user_id);
            })
            ->when($product_id, function($query) use ($product_id) {
                return $query->where('product_id', $product_id);
            })
            ->when($variant_name, function($query) use ($variant_name) {
                return $query->where('variant_rate', $variant_name);
            });
    
        // Dapatkan hasil
        $results = $rate->get();
    
        // Jika tidak ada data
        if ($results->isEmpty()) {
            return ResponseFormmater::error(
                null,
                'Data Rate tidak ditemukan',
                404
            );
        }
    
        // Kembalikan response sukses
        return ResponseFormmater::success(
            $results,
            'Data Rate Berhasil diambil'
        );
    }
}
