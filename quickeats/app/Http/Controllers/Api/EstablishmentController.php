<?php

namespace App\Http\Controllers\Api;

use App\Models\Establishment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Rules\validaCelular;
use App\Rules\validaCNPJ;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\ConfirmacaoEmail;
use App\Mail\ConfirmaEmail;
use Illuminate\Support\Facades\DB;
use App\Http\Services\EstablishmentService;
use App\Http\Requests\RegisterEstablishmentRequest;

class EstablishmentController extends Controller
{
    private EstablishmentService $establishmentService;

    public function __construct(EstablishmentService $establishmentService)
    {
        $this->establishmentService = $establishmentService;
    }

    public function store(RegisterEstablishmentRequest $request)
    {
        $data = $request->validated();

        try {
            $establishment = $this->establishmentService->createEstablishment($data);
            return response()->json($establishment, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
