<?php

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use App\Concert;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function customer_can_purchase_concert_tickets()
    {
        $this->withoutExceptionHandling();
        $paymentGateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $paymentGateway);

        $concert = factory(Concert::class)->create([
            'ticket_price' => 3250,
        ]);

        $response = $this->postJson("/concerts/{$concert->id}/orders", [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $paymentGateway->getValidTestToken(),
        ]);


        // Assertions
        $response->assertStatus(201);

        $this->assertEquals(9750, $paymentGateway->totalCharges());

        $order = $concert->orders()->where('email', 'john@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals(3, $order->tickets->count());
    }
}
