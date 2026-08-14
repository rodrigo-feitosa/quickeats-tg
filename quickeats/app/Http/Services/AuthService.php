<?php

namespace App\Http\Services;

use App\Models\User;

class AuthService
{
    public function createUser(string $userType, array $data)
    {
        $user = User::create([
            'name' => $data['name'] ?? $data['company_name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'user_type' => $userType,
        ]);
        
        return $user;
    }
}