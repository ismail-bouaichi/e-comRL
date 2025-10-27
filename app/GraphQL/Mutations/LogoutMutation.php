<?php

namespace App\GraphQL\Mutations;

class LogoutMutation
{
    public function __invoke($_, array $args, $context)
    {
        $user = $context->user();
        $user->token()->revoke();
        
        return [
            'message' => 'Successfully logged out'
        ];
    }
}
