<?php

use App\Concert;
use App\Exceptions\NotEnoughTicketsException;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ConcertTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function can_get_formatted_date()
    {
        $concert = factory(Concert::class)->make([
            'date' => Carbon::parse('2016-12-01 17:00:00'),
        ]);

        $this->assertEquals('December 1, 2016', $concert->formatted_date);
    }

    /** @test */
    public function can_get_formatted_start_time()
    {
        $concert =  factory(Concert::class)->make([
            'date' => Carbon::parse('2016-12-01 17:00:00'),
        ]);

        $this->assertEquals('5:00pm', $concert->formatted_start_time);
    }

    /** @test */
    public function can_get_ticket_price_in_pounds()
    {
        $concert =  factory(Concert::class)->make([
            'ticket_price' => 2050
        ]);

        $this->assertEquals('£20.50', $concert->ticket_price_in_pounds);
    }

    /** @test */
    public function concerts_with_a_published_at_date_are_published()
    {
        $publishedConcertA = factory(Concert::class)->create([
            'published_at' => Carbon::parse('-2 weeks'),
        ]);
        $publishedConcertB = factory(Concert::class)->create([
            'published_at' => Carbon::parse('-2 weeks'),
        ]);
        $unpublishedConcert = factory(Concert::class)->create([
            'published_at' => null
        ]);

        $publishedConcerts = Concert::published()->get();

        $this->assertTrue($publishedConcerts->contains($publishedConcertA));
        $this->assertTrue($publishedConcerts->contains($publishedConcertB));
        $this->assertFalse($publishedConcerts->contains($unpublishedConcert));
    }

    /** @test */
    public function can_order_concert_tickets()
    {
        $concert = factory(Concert::class)->create()->addTickets(3);
        $order = $concert->orderTickets('bob@test.com', 3);

        $this->assertEquals('bob@test.com', $order->email);
        $this->assertEquals(3, $order->ticketQuantity());
    }

    /** @test */
    public function can_add_tickets()
    {
        $concert = factory(Concert::class)->create()->addTickets(50);

        $this->assertEquals(50, $concert->ticketsRemaining());
    }

    /** @test */
    public function tickets_remaining_does_not_include_tickets_sold()
    {
        $concert = factory(Concert::class)->create()->addTickets(50);

        $concert->orderTickets('bob@test.com', 25);

        $this->assertEquals(25, $concert->ticketsRemaining());
    }

    /** @test */
    public function trying_to_purchase_more_tickets_than_remain_throws_an_exception()
    {
        $concert = factory(Concert::class)->create()->addTickets(50);

        try {
            $concert->orderTickets('bob@web.com', 55);
        } catch (NotEnoughTicketsException $e) {
            $this->assertFalse($concert->hasOrderFor('bob@web.com'));
            $this->assertEquals(50, $concert->ticketsRemaining());
            return;
        }

        $this->fail('Order succeeded even though there weren\'t enough tickets remaining!');
    }

    /** @test */
    public function cannot_order_tickets_that_have_already_been_purchased()
    {
        $concert = factory(Concert::class)->create()->addTickets(20);

        $concert->orderTickets('steve@google.com', 10);

        try {
            $concert->orderTickets('bob@web.com', 15);
        } catch (NotEnoughTicketsException $e) {
            $this->assertFalse($concert->hasOrderFor('bob@web.com'));
            $this->assertEquals(10, $concert->ticketsRemaining());
            return;
        }

        $this->fail('Order succeeded even though there weren\'t enough tickets remaining!');
    }
}
