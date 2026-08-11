<?php

namespace App\Http\Services;

use App\Models\Customer;
use App\Http\Services\AuthService;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function createCustomer(array $data)
    {
        return DB::transaction(function () use ($data) {

            $user = $this->authService->createUser($data);
            
            Customer::create([
                'name' => $data['name'],
                'cpf' => $data['cpf'],
                'date_of_birth' => $data['date_of_birth'],
                'phone' => $data['phone'],
                'user_id' => $user->id,
            ]);
        });
    }
}
