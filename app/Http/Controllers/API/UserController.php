<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormmater;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function generateCaptcha()
    {
        $num1 = rand(1, 9);
        $num2 = rand(1, 9);
        $operator = rand(0, 1) ? '+' : '-';
        
        // Ensure subtraction doesn't go negative
        if ($operator === '-' && $num1 < $num2) {
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        
        $answer = $operator === '+' ? $num1 + $num2 : $num1 - $num2;
        $token = bin2hex(random_bytes(16));
        
        // Store in cache with 5 minute expiration
        Cache::put('captcha_' . $token, $answer, now()->addMinutes(5));
        
        return ResponseFormmater::success([
            'token' => $token,
            'question' => "$num1 $operator $num2 = ?"
        ], 'Captcha generated');
    }
    
    public function register(Request $request)
    {
        try {
            // Validate captcha
            $captchaToken = $request->input('captcha_token');
            $captchaAnswer = $request->input('captcha_answer');
            
            $storedAnswer = Cache::get('captcha_' . $captchaToken);
            
            if (!$captchaToken || $storedAnswer === null) {
                return ResponseFormmater::error([
                    'message' => 'Captcha kadaluarsa. Silakan refresh halaman.'
                ], 'Captcha Error', 400);
            }
            
            if ($storedAnswer != $captchaAnswer) {
                Cache::forget('captcha_' . $captchaToken);
                return ResponseFormmater::error([
                    'message' => 'Jawaban captcha salah'
                ], 'Captcha Error', 400);
            }
            
            Cache::forget('captcha_' . $captchaToken);
            
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'phone' => 'sometimes',
                'role' => 'sometimes'
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => $request->role,
            ]);

            $user = User::where('email', $request->email)->first();
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormmater::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'Register Success');
        }

        catch(Exception $error) {
            return ResponseFormmater::error([
                'message' => 'Register Failed',
                'error' => $error
            ], 'Register Failed', 404);
        }
    }

    public function login (Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $credentials = request(['email', 'password']);

            if(!Auth::attempt($credentials)) {
                return ResponseFormmater::error([
                    'message' => 'Email dan password anda salah'
                ], 'Unauthorized Failed', 401);
            }

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormmater::success([
                'access_token' =>  $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'Login Success');
        }

        catch(Exception $error) {
            return ResponseFormmater::error([
                'message' => 'Login Failed',
                'error' => $error->getMessage()
            ], 'Login Failed', 401);
        }
    }
    
    public function registerLocal(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'phone' => 'sometimes',
                'role' => 'sometimes'
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'role' => $request->role ?? 'user',
            ]);

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormmater::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'Register Success');
        }
        
        catch(\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $emailError = $errors['email'] ?? null;
            if ($emailError && strpos($emailError[0], 'already been taken') !== false) {
                return ResponseFormmater::error([
                    'message' => 'Email sudah terdaftar'
                ], 'Register Failed', 404);
            }
            return ResponseFormmater::error([
                'message' => $emailError[0] ?? 'Validasi gagal'
            ], 'Register Failed', 404);
        }
        
        catch(Exception $error) {
            return ResponseFormmater::error([
                'message' => $error->getMessage()
            ], 'Register Failed', 404);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $user = User::where('email', $request->email)->first();
            
            if (!$user) {
                return ResponseFormmater::error([
                    'message' => 'Email tidak ditemukan'
                ], 'Not Found', 404);
            }

            // Generate reset token
            $token = bcrypt($user->email . time());
            
            // Store in password_resets table
            DB::table('password_resets')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => $token,
                    'created_at' => now()
                ]
            );

            return ResponseFormmater::success([
                'message' => 'Link reset password telah dikirim ke email anda',
                'token' => $token // In production, send via email
            ], 'Success');
        }
        catch(Exception $error) {
            return ResponseFormmater::error([
                'message' => 'Gagal mengirim link reset password'
            ], 'Error', 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'token' => 'required',
                'password' => 'required|confirmed|min:6'
            ]);

            $reset = DB::table('password_resets')
                ->where('email', $request->email)
                ->where('token', $request->token)
                ->first();

            if (!$reset) {
                return ResponseFormmater::error([
                    'message' => 'Token tidak valid atau sudah expire'
                ], 'Invalid Token', 400);
            }

            $user = User::where('email', $request->email)->first();
            $user->password = Hash::make($request->password);
            $user->save();

            // Delete used token
            DB::table('password_resets')->where('email', $request->email)->delete();

            return ResponseFormmater::success([
                'message' => 'Password berhasil direset'
            ], 'Success');
        }
        catch(Exception $error) {
            return ResponseFormmater::error([
                'message' => 'Gagal reset password'
            ], 'Error', 500);
        }
    }

    public function updated (Request $request , $id)
    {
        $user = User::findOrFail($id);

        $existingData = User::where('email', $request->email)->where('id', '!=', $id)->first();

        if($existingData) {
            return ResponseFormmater::error(
                null,
                'Email sudah dimiliki orang lain',
                505
            );
        }

        $data = $request->all();

        if($request->hasFile('avatar')) {

            if($request->file('avatar')->isValid()) {
                Storage::disk('upload')->delete($user->avatar);
                $avatar = $request->file('avatar');
                $extensions = $avatar->getClientOriginalExtension();
                $userAvatar = "user/".date('YmdHis').".".$extensions;
                $uploadPath = \env('UPLOAD_PATH'). "/user";
                $request->file('avatar')->move($uploadPath, $userAvatar);
                $data['avatar'] = $userAvatar;
            }
        }

        if($request->input('password')) {

            $data['password'] = Hash::make($data['password']);

        } else {

            $data = Arr::except($data,['password']);
        }

        $user->update($data);
        return ResponseFormmater::success(
            $user,
            'Data User berhasil diupdate'
        );
    }

    public function delete (Request $request , $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return ResponseFormmater::success(
            $user,
            'Data User Berhasil di delete'
        );
    }

    public function user (Request $request)
    {
        $id = $request->input('id');
        $name = $request->input('name');
        $role = $request->input('role');
        $profile_id = $request->input('profile_id');

        if($id) {
            $user = User::with(['profiles'])->find($id);

            if($user) {
                return ResponseFormmater::success(
                    $user,
                    'Data User berhasil diambil'
                );
            }

            else {
                return ResponseFormmater::error(
                    null,
                    'User tidak ada',
                    404
                );
            }
        }


        $user = User::with(['profiles']);

        if($name) {
            $user->where('name', 'LIKE', '%' . $name . '%');
        }


        if($role) {
            $user->where('role', 'LIKE', '%' . $role . '%');
        }

        if($profile_id)
        {
            $user->where('profile_id', $profile_id);
        }


        return ResponseFormmater::success(
            $user->get(),
            'List User Berhasil diambil'
        );
    }
}
