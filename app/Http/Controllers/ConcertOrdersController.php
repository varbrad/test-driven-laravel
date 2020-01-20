<?php

namespace App\Http\Controllers;

use App\Billing\PaymentFailedException;
use App\Billing\PaymentGateway;
use App\Concert;

class ConcertOrdersController extends Controller
{
    /** @var PaymentGateway */
    private $paymentGateway;

    public function __construct(PaymentGateway $paymentGateway) {
        $this->paymentGateway = $paymentGateway;
    }

    public function store($id)
    {
        $concert = Concert::published()->findOrFail($id);

        $this->validate(request(), [
            'email' => ['required', 'email'],
            'ticket_quantity' => ['required', 'integer', 'min:1'],
            'payment_token' => ['required'],
        ]);

        try {
            $ticketQuantity = request('ticket_quantity');
            $amount = $ticketQuantity * $concert->ticket_price;
            $token = request('payment_token');
            $this->paymentGateway->charge($amount, $token);

            $concert->orderTickets(request('email'), request('ticket_quantity'));

            return response()->json([], 201);
        } catch (PaymentFailedException $e) {
            return response()->json([], 422);
        }
    }
}
