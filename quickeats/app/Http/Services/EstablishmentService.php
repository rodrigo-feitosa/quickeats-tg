<?php

namespace App\Http\Services;

use App\Models\Establishment;
use Illuminate\Support\Facades\DB;
use App\Http\Services\AuthService;

class EstablishmentService
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function createEstablishment(array $data)
    {
        return DB::transaction(function () use ($data) {

            $userType = 'estabelecimento';
            $user = $this->authService->createUser($userType, $data);
            
            Establishment::create([
                'company_name' => $data['company_name'],
                'trade_name' => $data['trade_name'],
                'cnpj' => $data['cnpj'],
                'phone' => $data['phone'],
                'account_holder_cpf' => $data['account_holder_cpf'],
                'account_holder_rg' => $data['account_holder_rg'],
                'cnae' => $data['cnae'],
                'street' => $data['street'],
                'number' => $data['number'],
                'complement' => $data['complement'] ?? null,
                'neighborhood' => $data['neighborhood'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip_code' => $data['zip_code'],
                'profile_picture' => $data['profile_picture'] ?? null,
                'user_id' => $user->id,
            ]);
        });
    }
}
