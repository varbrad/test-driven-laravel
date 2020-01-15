<?php

use App\Billing\FakePaymentGateway;
use Tests\TestCase;

class FakePaymentGatewayTest extends TestCase
{
    /** @test */
    public function charges_with_a_valid_payment_token_are_successful()
    {
        $paymentGateway = new FakePaymentGateway;

        $token = $paymentGateway->getValidTestToken();
        $paymentGateway->charge(2500, $token);

        $this->assertEquals(2500, $paymentGateway->totalCharges());
    }
}
