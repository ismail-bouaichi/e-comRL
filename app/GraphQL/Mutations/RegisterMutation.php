<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class RegisterMutation
{
    public function __invoke($_, array $args)
    {
        $input = $args['input'];
        
        $customerRole = Role::where('name', 'customer')->firstOrFail();
        
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role_id' => $customerRole->id,
        ]);
        
        $token = $user->createToken('auth_token')->accessToken;
        
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('role'),
        ];
    }
}
