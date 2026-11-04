<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormmater;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function profile (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'address' => 'sometimes',
            'city_id' => 'sometimes|string|max:255'
        ]);


        if($validator->fails()) {
            return ResponseFormmater::error(
                null,
                $validator->errors(),
                500
            );
        }

        $profile = Profile::create([
            'user_id' => $request->user_id,
            'address' => $request->address,
            'city_id' => $request->city_id,
        ]);


        try {
            $profile->save();
            return ResponseFormmater::success(
                $profile,
                'Data Profile  Berhasil di tambahkan'
            );
        }

        catch(Exception $error) {
            return ResponseFormmater::error(
                $error->getMessage(),
                'Data profile  tidak ada',
                404
            );
        }
    }

    public function editProfile (Request $request , $id)
    {
        $profile = Profile::findOrFail($id);
        $data = $request->all();

        $profile->update($data);
        return ResponseFormmater::success(
            $profile,
            'Data Profile Berhasil di update'
        );
    }
}
