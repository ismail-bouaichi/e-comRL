<?php

namespace App\GraphQL\Mutations;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginMutation
{
    public function __invoke($_, array $args)
    {
        $user = User::where('email', $args['email'])->first();
        
        if (!$user || !Hash::check($args['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }
        
        $token = $user->createToken('auth_token')->accessToken;
        
        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('role'),
        ];
    }
}
