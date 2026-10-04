<?php

namespace App\Services\Paiement;
use Illuminate\Support\Facades\Http;

class FedaPayGateway
{
    public function createTransaction(array $payload)
    {
        return Http::withToken(config('services.fedapay.secret'))
            ->post(
                config('services.fedapay.base_url').'/transactions',
                $payload
            )
            ->throw()
            ->json();
    }

    public function getTransaction(string $transactionId)
    {
        return Http::withToken(config('services.fedapay.secret'))
            ->get(
                config('services.fedapay.base_url')."/transactions/{$transactionId}"
            )
            ->throw()
            ->json();
    }
}
