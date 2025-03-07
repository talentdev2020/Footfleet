<?php

namespace Tests\Feature\Http\Controllers\Foodfleet\Square\PaymentTypes;

use App\Models\Foodfleet\Square\PaymentType;
use App\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentTypeTest extends TestCase
{
    use RefreshDatabase, WithFaker, WithoutMiddleware;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGetList()
    {
        $user = factory(User::class)->create();

        Passport::actingAs($user);

        $paymentTypes = factory(PaymentType::class, 5)->create();

        $data = $this
            ->json('get', "/api/foodfleet/payment/types")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data'
            ])
            ->json('data');

        $this->assertNotEmpty($data);
        $this->assertEquals(5, count($data));
        foreach ($paymentTypes as $idx => $paymentType) {
            $this->assertArraySubset([
                'uuid' => $paymentType->uuid,
                'name' => $paymentType->name
            ], $data[$idx]);
        }

        $data = $this
            ->json('get', "/api/foodfleet/payment/types?filter[uuid]=" . $paymentTypes->first()->uuid)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data'
            ])
            ->json('data');

        $this->assertNotEmpty($data);
        $this->assertEquals(1, count($data));

        $this->assertArraySubset([
            'uuid' => $paymentTypes->first()->uuid,
            'name' => $paymentTypes->first()->name
        ], $data[0]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGetListWithUuidFilter()
    {
        $user = factory(User::class)->create();

        Passport::actingAs($user);

        factory(PaymentType::class)->create();

        $paymentTypeToFind = factory(PaymentType::class)->create();

        $data = $this
            ->json('get', "/api/foodfleet/payment/types")
            ->assertStatus(200)
            ->assertJsonStructure([
                'data'
            ])
            ->json('data');

        $this->assertNotEmpty($data);
        $this->assertEquals(2, count($data));


        $data = $this
            ->json('get', "/api/foodfleet/payment/types?filter[uuid]=" . $paymentTypeToFind->uuid)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data'
            ])
            ->json('data');

        $this->assertNotEmpty($data);
        $this->assertEquals(1, count($data));

        $this->assertArraySubset([
            'uuid' => $paymentTypeToFind->uuid,
            'name' => $paymentTypeToFind->name
        ], $data[0]);
    }
}
