<?php


use App\Models\Balance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class BalanceApiTest extends TestCase
{
//    use RefreshDatabase;

    public function test_deposit_creates_balance()
    {
        $user = User::factory()->create();

        $response = $this->withHeader('X-API-TOKEN', env('API_TOKEN'))
            ->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount' => 500.00,
            'comment' => 'Test deposit'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(500.00, $user->balance->amount);
    }

    public function test_withdraw_insufficient_funds()
    {
        $user = User::factory()->create();

        $response = $this->withHeader('X-API-TOKEN', env('API_TOKEN'))
            ->postJson('/api/withdraw', [
            'user_id' => $user->id,
            'amount' => 100.00,
        ]);

        $response->assertStatus(409);
    }

    public function test_transfer_success()
    {
        $from = User::factory()->create();
        $to = User::factory()->create();

        $this->withHeader('X-API-TOKEN', env('API_TOKEN'))
            ->postJson('/api/deposit', ['user_id' => $from->id, 'amount' => 1000, 'comment' => 'Test deposit']);

        $response = $this->withHeader('X-API-TOKEN', env('API_TOKEN'))
            ->postJson('/api/transfer', [
            'from_user_id' => $from->id,
            'to_user_id' => $to->id,
            'amount' => 150.00,
            'comment' => 'Test transfer'
        ]);

        $response->assertStatus(200);

        $this->assertEquals(850, Balance::where('user_id', $from->id)->value('amount'));
        $this->assertEquals(150, Balance::where('user_id', $to->id)->value('amount'));
    }
}
