<?php

use App\Billing\FakePaymentGateway;
use App\Billing\PaymentGateway;
use App\Concert;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class PurchaseTicketsTest extends TestCase
{
    use DatabaseMigrations;

    protected $paymentGateway;

    protected function setUp(): void
    {
        parent::setUp();
        //
        $this->paymentGateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $this->paymentGateway);
    }

    private function orderTickets($concert, $params)
    {
        return $this->postJson("/concerts/{$concert->id}/orders", $params);
    }

    /** @test */
    public function customer_can_purchase_tickets_to_a_published_concert()
    {
        $concert = factory(Concert::class)->state('published')->create([
            'ticket_price' => 3250,
        ])->addTickets(5);

        $response = $this->orderTickets($concert, [
            'email' => 'john@example.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);


        // Assertions
        $response->assertStatus(201);

        $this->assertEquals(9750, $this->paymentGateway->totalCharges());

        $this->assertTrue($concert->hasOrderFor('john@example.com'));
        $this->assertEquals(3, $concert->ordersFor('john@example.com')->first()->ticketQuantity());
    }

    /** @test */
    public function an_order_is_not_created_if_a_payment_fails()
    {
        $concert = factory(Concert::class)->state('published')->create(['ticket_price' => 1999])->addTickets(5);

        $response = $this->orderTickets($concert, [
            'email' => 'bob@test.com',
            'ticket_quantity' => 3,
            'payment_token' => 'invalid-payment-token',
        ]);

        $response->assertStatus(422);
        $this->assertFalse($concert->hasOrderFor('bob@test.com'));
    }

    /** @test */
    public function cannot_purchase_tickets_if_the_concert_is_unpublished()
    {
        $concert = factory(Concert::class)->state('unpublished')->create()->addTickets(10);

        $response = $this->orderTickets($concert, [
            'email' => 'bob@test.com',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(404);

        $this->assertFalse($concert->hasOrderFor('bob@test.com'));
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
    }

    /** @test */
    public function email_is_required_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->state('published')->create();

        $response = $this->orderTickets($concert, [
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    /** @test */
    public function email_must_be_valid_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->state('published')->create();

        $response = $this->orderTickets($concert, [
            'email' => 'this-is-not-an-email',
            'ticket_quantity' => 3,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }

    /** @test */
    public function ticket_quantity_is_required_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->state('published')->create();

        $response = $this->orderTickets($concert, [
            'email' => 'bob@test.com',
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ticket_quantity');
    }

    /** @test */
    public function ticket_quantity_must_be_at_least_1_to_purchase_tickets()
    {
        $concert = factory(Concert::class)->state('published')->create();

        $response = $this->orderTickets($concert, [
            'email' => 'bob@test.com',
            'ticket_quantity' => 0,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ticket_quantity');
    }

    /** @test */
    public function payment_token_is_required()
    {
        $concert = factory(Concert::class)->state('published')->create();

        $response = $this->orderTickets($concert, [
            'email' => 'bob@test.com',
            'ticket_quantity' => 2,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('payment_token');
    }

    /** @test */
    public function cannot_purchase_more_tickets_than_remain()
    {
        $concert = factory(Concert::class)->state('published')->create()->addTickets(50);

        $response = $this->orderTickets($concert, [
            'email' => 'bob@test.com',
            'ticket_quantity' => 51,
            'payment_token' => $this->paymentGateway->getValidTestToken(),
        ]);

        $response->assertStatus(422);
        $this->assertFalse($concert->hasOrderFor('bob@test.com'));
        $this->assertEquals(0, $this->paymentGateway->totalCharges());
        $this->assertEquals(50, $concert->ticketsRemaining());
    }
}
