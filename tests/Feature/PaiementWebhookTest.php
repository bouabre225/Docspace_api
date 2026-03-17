<?php

namespace Tests\Feature;

use App\Models\Commandes;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Annonce;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaiementWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret = 'test_webhook_secret';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.fedapay.webhook_secret', $this->webhookSecret);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeSignature(string $payload): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $this->webhookSecret);
    }

    private function makeCommande(string $statut = 'en_attente'): Commandes
    {
        $acheteur = User::factory()->create(['role' => 'acheteur']);
        $vendeur  = User::factory()->create(['role' => 'vendeur']);
        $annonce  = Annonce::factory()->create(['vendeur_id' => $vendeur->id, 'quantite' => 10]);

        return Commandes::create([
            'acheteur_id' => $acheteur->id,
            'vendeur_id'  => $vendeur->id,
            'annonce_id'  => $annonce->id,
            'quantite'    => 2,
            'montant'     => 5000,
            'statut'      => $statut,
        ]);
    }

    private function makePaiement(Commandes $commande, string $transactionId = 'txn_123'): Paiement
    {
        return Paiement::create([
            'commande_id'        => $commande->id,
            'moyen'              => 'fedapay',
            'montant'            => $commande->montant,
            'statut'             => 'en_attente',
            'provider_reference' => $transactionId,
        ]);
    }

    // ─── Tests : vérification de signature ───────────────────────────────────

    public function test_webhook_rejecte_requete_sans_signature(): void
    {
        $payload = json_encode(['event' => 'transaction.approved', 'transaction' => ['id' => 'txn_123']]);

        $response = $this->postJson('/api/webhooks/fedapay', json_decode($payload, true));

        $response->assertStatus(401)
                 ->assertJson(['success' => false, 'message' => 'Signature manquante']);
    }

    public function test_webhook_rejecte_signature_invalide(): void
    {
        $payload = json_encode(['event' => 'transaction.approved', 'transaction' => ['id' => 'txn_123']]);

        $response = $this->postJson(
            '/api/webhooks/fedapay',
            json_decode($payload, true),
            ['X-FedaPay-Signature' => 'sha256=invalide']
        );

        $response->assertStatus(401)
                 ->assertJson(['success' => false, 'message' => 'Signature invalide']);
    }

    public function test_webhook_accepte_signature_valide(): void
    {
        $commande = $this->makeCommande();
        $this->makePaiement($commande, 'txn_123');

        $payload = json_encode(['event' => 'transaction.approved', 'transaction' => ['id' => 'txn_123']]);

        $response = $this->call(
            'POST',
            '/api/webhooks/fedapay',
            [],
            [],
            [],
            ['HTTP_X-FedaPay-Signature' => $this->makeSignature($payload), 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);
    }

    public function test_webhook_ignore_verification_si_secret_non_configure(): void
    {
        Config::set('services.fedapay.webhook_secret', null);

        $commande = $this->makeCommande();
        $this->makePaiement($commande, 'txn_456');

        $payload = ['event' => 'transaction.approved', 'transaction' => ['id' => 'txn_456']];

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn($msg) => str_contains($msg, 'non configuré'));

        $response = $this->postJson('/api/webhooks/fedapay', $payload);

        // Pas de 401 — la vérification est ignorée
        $this->assertNotEquals(401, $response->status());
    }

    // ─── Tests : payload incomplet ────────────────────────────────────────────

    public function test_webhook_rejecte_payload_sans_event(): void
    {
        $payload = json_encode(['transaction' => ['id' => 'txn_123']]);

        $response = $this->call(
            'POST',
            '/api/webhooks/fedapay',
            [],
            [],
            [],
            ['HTTP_X-FedaPay-Signature' => $this->makeSignature($payload), 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(400)
                 ->assertJson(['success' => false, 'message' => 'Payload incomplet']);
    }

    public function test_webhook_rejecte_payload_sans_transaction_id(): void
    {
        $payload = json_encode(['event' => 'transaction.approved']);

        $response = $this->call(
            'POST',
            '/api/webhooks/fedapay',
            [],
            [],
            [],
            ['HTTP_X-FedaPay-Signature' => $this->makeSignature($payload), 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $response->assertStatus(400)
                 ->assertJson(['success' => false, 'message' => 'Payload incomplet']);
    }

    // ─── Tests : événements webhook ──────────────────────────────────────────

    public function test_webhook_approuve_paiement_et_commande(): void
    {
        $commande = $this->makeCommande();
        $paiement = $this->makePaiement($commande, 'txn_approved');

        $payload = json_encode([
            'event'       => 'transaction.approved',
            'transaction' => ['id' => 'txn_approved'],
        ]);

        $this->call(
            'POST',
            '/api/webhooks/fedapay',
            [],
            [],
            [],
            ['HTTP_X-FedaPay-Signature' => $this->makeSignature($payload), 'CONTENT_TYPE' => 'application/json'],
            $payload
        )->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('paiements', [
            'id'     => $paiement->id,
            'statut' => 'bloque',
        ]);

        $this->assertDatabaseHas('commandes', [
            'id'     => $commande->id,
            'statut' => 'payee',
        ]);
    }

    public function test_webhook_annule_paiement_et_commande(): void
    {
        $commande = $this->makeCommande();
        $paiement = $this->makePaiement($commande, 'txn_canceled');

        $payload = json_encode([
            'event'       => 'transaction.canceled',
            'transaction' => ['id' => 'txn_canceled'],
        ]);

        $this->call(
            'POST',
            '/api/webhooks/fedapay',
            [],
            [],
            [],
            ['HTTP_X-FedaPay-Signature' => $this->makeSignature($payload), 'CONTENT_TYPE' => 'application/json'],
            $payload
        )->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('paiements', [
            'id'     => $paiement->id,
            'statut' => 'annule',
        ]);

        $this->assertDatabaseHas('commandes', [
            'id'     => $commande->id,
            'statut' => 'annulee',
        ]);
    }

    public function test_webhook_marque_paiement_echoue(): void
    {
        $commande = $this->makeCommande();
        $paiement = $this->makePaiement($commande, 'txn_failed');

        $payload = json_encode([
            'event'       => 'transaction.failed',
            'transaction' => ['id' => 'txn_failed'],
        ]);

        $this->call(
            'POST',
            '/api/webhooks/fedapay',
            [],
            [],
            [],
            ['HTTP_X-FedaPay-Signature' => $this->makeSignature($payload), 'CONTENT_TYPE' => 'application/json'],
            $payload
        )->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('paiements', [
            'id'     => $paiement->id,
            'statut' => 'echoue',
        ]);
    }
}
