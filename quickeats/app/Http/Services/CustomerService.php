<?php

namespace App\Http\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    public function createCustomer(array $data)
    {
        return DB::transaction(function () use ($data) {

            $user = $this->createUser($data);
            
            Customer::create([
                'name' => $data['name'],
                'cpf' => $data['cpf'],
                'date_of_birth' => $data['date_of_birth'],
                'phone' => $data['phone'],
                'user_id' => $user->id,
            ]);
        });
    }

    private function createUser(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }
}
