<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        //  Listing::factory(6)->create();

        //        Listing::create([
        //            'title' => 'Laravel Senior Developer',
        //            'tags' => 'laravel, javascript',
        //            'company' => 'Acme Corp',
        //            'location' => 'Boston, MA',
        //            'email' => 'email1@email.com',
        //            'website' => 'https://www.acme.com',
        //            'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Ipsam minima et illo reprehenderit quas possimus voluptas repudiandae cum expedita, eveniet aliquid, quam illum quaerat consequatur! Expedita ab consectetur tenetur delensiti?'
        //        ]);
        //
        //        Listing::create([
        //            'title' => 'Laravel Senior Developer',
        //            'tags' => 'laravel, javascript',
        //            'company' => 'Acme Corp',
        //            'location' => 'Boston, MA',
        //            'email' => 'email1@email.com',
        //            'website' => 'https://www.acme.com',
        //            'description' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Ipsam minima et illo reprehenderit quas possimus voluptas repudiandae cum expedita, eveniet aliquid, quam illum quaerat consequatur! Expedita ab consectetur tenetur delensiti?'
        //        ]);

        // \App\Models\Pouzivatel::factory()->create([
        //     'name' => 'Test Pouzivatel',
        //     'email' => 'test@example.com',
        // ]);
    }
}
