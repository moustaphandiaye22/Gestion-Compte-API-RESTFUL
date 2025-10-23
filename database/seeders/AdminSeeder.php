<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Admin::factory(5)->create()->each(function ($admin) {
            $user = \App\Models\User::factory()->create([
                'email' => fake()->unique()->safeEmail,
                'password' => bcrypt('password'),
                'userable_id' => $admin->id,
                'userable_type' => \App\Models\Admin::class,
            ]);
            $admin->user()->save($user);
        });
    }
}
