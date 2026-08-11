<?php

namespace App\Http\Services;

use App\Models\User;

class AuthService
{
    public function createUser(array $data)
    {
        return User::create([
            'name' => $data['name'] ?? $data['company_name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }
}
