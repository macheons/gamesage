<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\CustomInstruction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Utilisateur de démonstration (idempotent)
        $user = User::firstOrCreate(
            ['email' => 'demo@gamesage.test'],
            [
                'name' => 'Demo GameSage',
                'password' => Hash::make('password'),
                'preferred_model' => 'openai/gpt-5-mini',
            ]
        );
        $user->forceFill(['email_verified_at' => now()])->save();

        // Instructions personnalisées (relation 1-1)
        CustomInstruction::updateOrCreate(
            ['user_id' => $user->id],
            [
                'about_you' => "Je joue surtout en solo, j'adore l'exploration et les ambiances calmes.",
                'behavior'  => 'Réponds de façon concise et termine par une emoji de manette.',
                'is_enabled' => true,
            ]
        );

        // Conversation 1 + messages
        $conv1 = Conversation::create([
            'user_id' => $user->id,
            'title'   => 'Open-world relaxant à conseiller',
            'model'   => 'openai/gpt-5-mini',
        ]);
        Message::create(['conversation_id' => $conv1->id, 'role' => 'user', 'content' => 'Un monde ouvert relaxant à conseiller ?']);
        Message::create(['conversation_id' => $conv1->id, 'role' => 'assistant', 'content' => 'Pour du relaxant : Sable ou Eastshade, exploration contemplative, zéro pression. 🎮']);

        // Conversation 2 + messages
        $conv2 = Conversation::create([
            'user_id' => $user->id,
            'title'   => 'Jeu de société pour 3 joueurs',
            'model'   => 'openai/gpt-5-mini',
        ]);
        Message::create(['conversation_id' => $conv2->id, 'role' => 'user', 'content' => 'Un jeu de société pour 3 joueurs, 45 min ?']);
        Message::create(['conversation_id' => $conv2->id, 'role' => 'assistant', 'content' => 'Wingspan ou 7 Wonders : nickel à 3, fluides et rejouables. 🎮']);
    }
}