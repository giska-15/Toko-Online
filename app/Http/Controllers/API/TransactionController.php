<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormmater;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Midtrans\Config;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function checkout (Request $request)
    {
        $validator =  Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'total_transaction' => 'sometimes|integer',
            'courier' => 'required|string|max:255',
            'service' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'cost' => '',
            'estimated' => '',
            'status_transaction' => '',
            'payment_type' => '',
            'order_id' => '',
            'token_payment' => 'sometimes',
            'cart_ids' => 'required|array',
            'cart_ids.*' => 'exists:carts,id',
        ]);


        if($validator->fails()) {
            return ResponseFormmater::error(
                null,
                $validator->errors(),
                500
            );
        }

        $transaction = Transaction::create([
            'user_id' => $request->user_id,
            'total_transaction' => $request->total_transaction,
            'courier' => $request->courier,
            'service' => $request->service,
            'description' => $request->description,
            'cost' => $request->cost,
            'estimated' => $request->estimated,
            'status_transaction' => $request->status_transaction,
            'payment_type' => $request->payment_type,
            'order_id' => $request->order_id,
            'token_payment' => $request->token_payment
        ]);

        $transaction->carts()->attach($request->cart_ids);

        // Konfigurasi Midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        $transaction = Transaction::find($transaction->id);

        $timestamp = now()->format('YmdHis');
        $orderId = $transaction->id . '_' . $timestamp;

        $transaction->order_id = $orderId;
        $transaction->save();

        // Midtrans Snap
        $midtrans = array(
            'transaction_details' => array(
                'order_id' => $orderId,
                'gross_amount' => $transaction->total_transaction,
            ),
        );

        if ($transaction->users) {
            $midtrans['customer_details'] = array(
                'first_name' => $transaction->users->name,
                'email' => $transaction->users->email
            );
        }

        try {
            $snapToken = Snap::getSnapToken($midtrans);
            $transaction->token_payment = $snapToken;
            $transaction->save();

            return ResponseFormmater::success(
                $transaction->load('carts'),
                'Data Transaction Berhasil di tambahkan'
            );
        } catch (Exception $error) {
            return ResponseFormmater::error(
                $error->getMessage(),
                'Data Transaction tidak ada',
                404
            );
        }
    }

    public function updateCheckout(Request $request , $id)
    {
        $transaction = Transaction::findOrFail($id);
        $data = $request->all();

        $transaction->update($data);
        return ResponseFormmater::success(
            $transaction,
            'Data Transaction Berhasil di update'
        );

    }

    public function deleteCheckout(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $transaction->delete();
        return ResponseFormmater::success(
            $transaction,
            'Data Transaction Berhasil di Delete'
        );
    }

    public function transaction (Request $request)
    {
        $id = $request->input('id');
        $user_id = $request->input('user_id');
        $status_transaction = $request->input('status_transaction');
        $limit = $request->input('limit', 10);

        if($id)
        {
            $transaction = Transaction::with(['users', 'carts.rates', 'users.profiles'])->find($id);

            if($transaction)
            {
                return ResponseFormmater::success(
                    $transaction,
                    'Transaction berhasil diambil'
                );
            }

            else {
                return ResponseFormmater::error(
                    null,
                    'Transaction tidak ditemukan',
                    404,
                );
            }
        }

        $transaction = Transaction::with(['users', 'carts.rates', 'users.profiles']);

        if($user_id)
        {
            $transaction->where('user_id', $user_id);
        }

        if($status_transaction)
        {
            $transaction->where('status_transaction', $status_transaction);
        }

        return ResponseFormmater::success(
            $transaction->paginate($limit),
            'Transaction berhasil diambil'
        );

    }

    public function searchCityByName(Request $request)
    {
        // Ambil input 'city_name' dari request dan bersihkan dari spasi
        $cityName = strtolower(trim($request->input('city_name')));

        // Validasi input
        if (!$cityName) {
            return ResponseFormmater::error(
                null,
                'City name is required',
                400
            );
        }

        // Mengirim request ke API Raja Ongkir menggunakan HTTP Client Laravel
        $response = Http::withHeaders([
            'key' => env('RAJAONGKIR_API_KEY')
        ])->get(env('RAJAONGKIR_API_URL') . '/city');

        // Parse JSON response dari API
        $cities = json_decode($response->body())->rajaongkir->results;

        // Pecah input menjadi array kata
        $searchKeywords = explode(' ', $cityName);

        // Filter kota berdasarkan kecocokan kata kunci dalam `city_name`
        $filteredCities = array_filter($cities, function($city) use ($searchKeywords) {
            // Bandingkan setiap kata kunci dengan nama kota, tidak case sensitive
            foreach ($searchKeywords as $keyword) {
                if (stripos($city->city_name, $keyword) !== false) {
                    return true;
                }
            }
            return false;
        });

        // Jika kota ditemukan, kembalikan data kota dengan ResponseFormmater::success()
        if (!empty($filteredCities)) {
            // Menghilangkan indeks numerik acak dan menggantinya dengan indeks berurutan
            $filteredCities = array_values($filteredCities);

            return ResponseFormmater::success(
                $filteredCities,
                'Cities found successfully'
            );
        } else {
            return ResponseFormmater::error(
                null,
                'No cities found',
                404
            );
        }
    }

    public function cekOngkir(Request $request)
        {
            // Validasi input request
            $request->validate([
                'origin' => 'required|string',
                'destination' => 'required|string',
                'weight' => 'required|integer',
                'courier' => 'required|string',
            ]);

            // Ambil input dari request
            $origin = $request->input('origin');
            $destination = $request->input('destination');
            $weight = $request->input('weight');
            $courier = $request->input('courier');

            // Kirim request ke API RajaOngkir
            $response = Http::withHeaders([
                'key' => env('RAJAONGKIR_API_KEY')
            ])->post(env('RAJAONGKIR_API_URL') . '/cost', [
                'origin' => $origin,
                'destination' => $destination,
                'weight' => $weight,
                'courier' => $courier
            ]);

            // Parse JSON response dari API RajaOngkir
            $rajaOngkirResponse = json_decode($response->body());

            // Cek apakah response sukses
            if ($rajaOngkirResponse->rajaongkir->status->code == 200) {
                // Mengambil data results dari RajaOngkir
                $results = $rajaOngkirResponse->rajaongkir->results;

                // Format data menjadi format yang diinginkan
                $formattedResponse = [
                    "meta" => [
                        "code" => 200,
                        "status" => "success",
                        "message" => "Cost data fetched successfully"
                    ],
                    "data" => $results // mengambil data hasil dari API
                ];

                // Return formatted response
                return response()->json($formattedResponse, 200);
            } else {
                // Jika API RajaOngkir memberikan error
                $errorResponse = [
                    "meta" => [
                        "code" => $rajaOngkirResponse->rajaongkir->status->code,
                        "status" => "error",
                        "message" => $rajaOngkirResponse->rajaongkir->status->description
                    ],
                    "data" => null
                ];

                return response()->json($errorResponse, $rajaOngkirResponse->rajaongkir->status->code);
            }
        }
}
