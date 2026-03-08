<?php

namespace Tests\Unit;

use App\Models\Commandes;
use App\Models\Paiement;
use App\Models\User;
use App\Models\Annonce;
use App\Services\paiementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PaiementServiceTest extends TestCase
{
    use RefreshDatabase;

    private paiementService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.fedapay.secret', 'sk_sandbox_test');
        Config::set('services.fedapay.environment', 'sandbox');

        // On instancie le service sans le constructeur FedaPay pour les tests unitaires
        $this->service = $this->getMockBuilder(paiementService::class)
            ->onlyMethods([])
            ->disableOriginalConstructor()
            ->getMock();
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

    private function makePaiement(Commandes $commande, string $ref = 'txn_test'): Paiement
    {
        return Paiement::create([
            'commande_id'        => $commande->id,
            'moyen'              => 'fedapay',
            'montant'            => $commande->montant,
            'statut'             => 'en_attente',
            'provider_reference' => $ref,
        ]);
    }

    // ─── Tests : handleWebhookEvent ───────────────────────────────────────────

    public function test_handle_transaction_approved(): void
    {
        $commande = $this->makeCommande();
        $paiement = $this->makePaiement($commande, 'txn_001');

        $this->service->handleWebhookEvent('transaction.approved', 'txn_001');

        $this->assertDatabaseHas('paiements', [
            'id'     => $paiement->id,
            'statut' => 'bloque',
        ]);

        $this->assertDatabaseHas('commandes', [
            'id'     => $commande->id,
            'statut' => 'payee',
        ]);

        $this->assertNotNull(Paiement::find($paiement->id)->date_paiement);
    }

    public function test_handle_transaction_canceled_restaure_quantite(): void
    {
        $commande = $this->makeCommande();
        $paiement = $this->makePaiement($commande, 'txn_002');

        $quantiteAvant = $commande->annonce->quantite;

        $this->service->handleWebhookEvent('transaction.canceled', 'txn_002');

        $this->assertDatabaseHas('paiements', [
            'id'     => $paiement->id,
            'statut' => 'annule',
        ]);

        $this->assertDatabaseHas('commandes', [
            'id'     => $commande->id,
            'statut' => 'annulee',
        ]);

        // La quantité de l'annonce doit être restaurée
        $this->assertDatabaseHas('annonces', [
            'id'      => $commande->annonce_id,
            'quantite' => $quantiteAvant + $commande->quantite,
        ]);
    }

    public function test_handle_transaction_failed(): void
    {
        $commande = $this->makeCommande();
        $paiement = $this->makePaiement($commande, 'txn_003');

        $this->service->handleWebhookEvent('transaction.failed', 'txn_003');

        $this->assertDatabaseHas('paiements', [
            'id'     => $paiement->id,
            'statut' => 'echoue',
        ]);
    }

    public function test_handle_evenement_inconnu_logue_warning(): void
    {
        $commande = $this->makeCommande();
        $this->makePaiement($commande, 'txn_004');

        Log::shouldReceive('warning')
            ->once()
            ->with('Unknown webhook event', ['event' => 'transaction.unknown']);

        $this->service->handleWebhookEvent('transaction.unknown', 'txn_004');
    }

    public function test_handle_transaction_introuvable_leve_exception(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->handleWebhookEvent('transaction.approved', 'txn_inexistant');
    }

    // ─── Tests : modèle Paiement ──────────────────────────────────────────────

    public function test_paiement_appartient_a_une_commande(): void
    {
        $commande = $this->makeCommande();
        $paiement = $this->makePaiement($commande);

        $this->assertInstanceOf(Commandes::class, $paiement->commande);
        $this->assertEquals($commande->id, $paiement->commande->id);
    }

    public function test_paiement_a_des_timestamps(): void
    {
        $commande = $this->makeCommande();
        $paiement = $this->makePaiement($commande);

        $this->assertNotNull($paiement->created_at);
        $this->assertNotNull($paiement->updated_at);
    }

    public function test_paiement_valeurs_par_defaut(): void
    {
        $commande = $this->makeCommande();
        $paiement = $this->makePaiement($commande);

        $this->assertEquals('fedapay', $paiement->moyen);
        $this->assertEquals('en_attente', $paiement->statut);
        $this->assertNull($paiement->date_paiement);
    }
}
