<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Psy\Test\Fixtures\ImplicitUse\App\Service\UserService;
use Tests\TestCase;

class register extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_user_vendeur_can_register(): void
    {
        $response = $this->get('/register/vendeur');

        $response->assertStatus(200);
    }
    public function test_it_can_register_a_buyer_successfully(): void
    {
        $service = new UserService();
        $data = [
            'nom' => 'John Doe',
            'email' => 'jenny@docspace',
            'password' => 'passwd123',
            'telephone' => '123456789'
        ];

        $user = $service->registerBuyer($data);

        //Assertions

        $this->assertInstanceOf(User::class, $user);
    }
}
