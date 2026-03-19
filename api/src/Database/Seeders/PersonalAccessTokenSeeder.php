<?php

namespace Alphasky\Api\Database\Seeders;

use Alphasky\Api\Models\PersonalAccessToken;
use Alphasky\Base\Supports\BaseSeeder;
use Alphasky\Member\Models\Member;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PersonalAccessTokenSeeder extends BaseSeeder
{
    public function run(): void
    {
        PersonalAccessToken::truncate();

        $members = Member::query()->limit(15)->get();

        if ($members->isEmpty()) {
            $this->command->warn('No members found. Please run member seeder first.');

            return;
        }

        $abilities = [
            ['*'],
            ['read', 'write'],
            ['read'],
            ['products:read', 'orders:read'],
            ['products:read', 'products:write', 'orders:read', 'orders:write'],
            ['profile:read', 'profile:write'],
            ['notifications:read', 'notifications:write'],
        ];

        $tokenNames = [
            'Mobile App',
            'Web App',
            'API Client',
            'Testing Token',
            'Development Token',
            'Third-party Integration',
            'Analytics Service',
            'Backup Service',
        ];

        $data = [];

        foreach ($members as $member) {
            $numberOfTokens = rand(1, 4);

            for ($i = 0; $i < $numberOfTokens; $i++) {
                $createdAt = $this->fake()->dateTimeBetween('-6 months', 'now');
                $lastUsedAt = $this->fake()->dateTimeBetween($createdAt, 'now');
                $expiresAt = $this->fake()->optional(0.3)->dateTimeBetween('now', '+1 year');

                $data[] = [
                    'tokenable_type' => Member::class,
                    'tokenable_id' => $member->id,
                    'name' => Arr::random($tokenNames) . ' #' . ($i + 1),
                    'token' => hash('sha256', Str::random(40)),
                    'abilities' => json_encode(Arr::random($abilities)),
                    'last_used_at' => $this->fake()->optional(0.7)->passthrough($lastUsedAt),
                    'expires_at' => $expiresAt,
                    'created_at' => $createdAt,
                    'updated_at' => $lastUsedAt,
                ];
            }
        }

        DB::table('personal_access_tokens')->insert($data);

        $this->command->info('Personal access tokens seeded successfully: ' . count($data) . ' tokens created.');
    }
}
