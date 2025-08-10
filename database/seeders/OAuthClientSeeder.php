<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OAuthClient;
use Illuminate\Support\Facades\Hash;

class OAuthClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /* OAuthClient::create([
            'name' => 'App Web de Cliente',
            'client_id' => 'web123',
            'client_secret' => bcrypt('web-secret'),
            'redirect_uri' => 'http://localhost:3000/callback'
        ]);*/
        OAuthClient::create([
            'name' => 'App Movil de Cliente',
            'client_id' => 'app123',
            'client_secret' => Hash::make('app-secret'),
            'redirect_uri' => 'miapp://callback'
        ]);
        OAuthClient::create([
            'name' => 'App Web de Cliente',
            'client_id' => 'web123',
            'client_secret' => Hash::make('web-secret'),
            'redirect_uri' => 'http://127.0.0.1:3000/oauth/callback',
			//'redirect_uri' => 'https://pagina-prueba.com/oauth/callback'
        ]);
    }
}
