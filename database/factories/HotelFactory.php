<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hotel>
 */
class HotelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cityCodes = ['NYC', 'PAR', 'LON', 'TOK', 'SYD', 'LAX', 'ROM', 'BER', 'MAD', 'AMS'];
        $chainCodes = ['RT', 'HI', 'MC', 'WH', 'IH', 'AC', 'BW', 'HY', 'SH', 'MR'];

        return [
            'amadeus_hotel_id' => fake()->randomElement($chainCodes) . fake()->randomElement($cityCodes) . str_pad(fake()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'name' => fake()->company() . ' ' . fake()->randomElement(['Hotel', 'Resort', 'Inn', 'Suites', 'Lodge', 'Palace']),
            'chain_code' => fake()->optional(0.7)->randomElement($chainCodes),
            'dupe_id' => fake()->optional(0.3)->numerify('#########'),
            'rating' => fake()->optional(0.8)->randomFloat(1, 2.0, 5.0),
            'city_code' => fake()->randomElement($cityCodes),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'address' => [
                'lines' => [fake()->streetAddress()],
                'postalCode' => fake()->postcode(),
                'cityName' => fake()->city(),
                'countryCode' => fake()->countryCode(),
            ],
            'contact' => [
                'phone' => fake()->phoneNumber(),
                'fax' => fake()->optional(0.5)->phoneNumber(),
            ],
            'description' => [
                'lang' => 'en',
                'text' => fake()->optional(0.6)->paragraph(),
            ],
            'amenities' => fake()->optional(0.7)->randomElements([
                'WIFI',
                'PARKING',
                'POOL',
                'GYM',
                'SPA',
                'RESTAURANT',
                'BAR',
                'ROOM_SERVICE',
                'CONCIERGE',
                'LAUNDRY',
                'AIR_CONDITIONING'
            ], fake()->numberBetween(2, 6)),
            'media' => fake()->optional(0.5)->randomElements([
                ['uri' => fake()->imageUrl(), 'category' => 'EXTERIOR'],
                ['uri' => fake()->imageUrl(), 'category' => 'LOBBY'],
                ['uri' => fake()->imageUrl(), 'category' => 'ROOM'],
            ], fake()->numberBetween(1, 3)),
        ];
    }

    /**
     * Indicate that the hotel is in a specific city.
     */
    public function inCity(string $cityCode): static
    {
        return $this->state(fn(array $attributes) => [
            'city_code' => strtoupper($cityCode),
        ]);
    }

    /**
     * Indicate that the hotel has a high rating.
     */
    public function highRated(): static
    {
        return $this->state(fn(array $attributes) => [
            'rating' => fake()->randomFloat(1, 4.0, 5.0),
        ]);
    }

    /**
     * Indicate that the hotel is part of a chain.
     */
    public function chain(string $chainCode): static
    {
        return $this->state(fn(array $attributes) => [
            'chain_code' => $chainCode,
        ]);
    }

    /**
     * Set a specific location (Paris example).
     */
    public function inParis(): static
    {
        return $this->state(fn(array $attributes) => [
            'amadeus_hotel_id' => 'RTPAR' . str_pad(fake()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'name' => fake()->company() . ' Hotel Paris',
            'city_code' => 'PAR',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'address' => [
                'lines' => [fake()->streetAddress()],
                'postalCode' => fake()->postcode(),
                'cityName' => 'Paris',
                'countryCode' => 'FR',
            ],
        ]);
    }
}
