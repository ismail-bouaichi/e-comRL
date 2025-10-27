<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use App\Http\Requests\RegisterRequest;
use App\Models\Role;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            if (Auth::attempt($request->only('email', 'password'))) {
                $user = Auth::user();
    
                $token = $user->createToken('app')->accessToken;
    
                if ($user->hasRole('customer')) {
                    return response([
                        'message' => 'Successfully Logged In as Customer',
                        'token' => $token,
                        'user' => $user
                    ], 200);
                } elseif ($user->hasRole('admin')) {
                    return response([
                        'message' => 'Successfully Logged In as Admin',
                        'token' => $token,
                        'user' => $user
                    ], 200);
                }
                elseif ($user->hasRole('delivery_worker')) {
                    return response([
                        'message' => 'Successfully Logged In as delivery_worker',
                        'token' => $token,
                        'user' => $user
                    ], 200);
                }
                 else {
                    return response([
                        'message' => 'You do not have permission to access this application.',
                    ], 403);
                }
            }
        } catch (\Exception $exception) {
            return response(['message' => $exception->getMessage()], 500);
        }
    
        return response([
            'message' => 'Invalid Email or Password'
        ], 401);
    }
    
public function register(RegisterRequest $request)
{
    $customerRole = Role::where('name', 'customer')->firstOrFail();
    
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'role_id' => $customerRole->id // ✅ Dynamic
    ]);
    
    $token = $user->createToken('app')->accessToken;

    return response([
        'message' => 'Registration Successfully',
        'token' => $token,
        'user' => $user
    ], 200);
}

public function loginDelivery(Request $request)
{
    try {
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();
            if ($user->hasRole('delivery_worker')) {
                $token = $user->createToken('app')->accessToken;
                return response([
                    'message' => 'Successfully Login',
                    'token' => $token,
                    'user' => $user
                ], 200);
            } else {
                return response([
                    'message' => 'You do not have the rights to access',
                ], 403);
            }
        } else {
            return response([
                'message' => 'Invalid credentials',
            ], 401);
        }
    } catch (\Exception $exception) {
        return response([
            'message' => 'An error occurred during login',
            'error' => $exception->getMessage()
        ], 500);
    }
}

    public function registerDelivery(Request $request)
    {
        try {
            $deliveryRole = Role::where('name', 'delivery_worker')->firstOrFail();
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $deliveryRole->id
            ]);

            $token = $user->createToken('app')->accessToken;

            return response([
                'message' => 'Registration Successfully',
                'token' => $token,
                'user' => $user
            ], 200);
            
        } catch (\Exception $exception) {
            return response(['message' => $exception->getMessage()], 400);
        }
    }
}
