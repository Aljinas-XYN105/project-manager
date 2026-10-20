<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $admin  = User::where('email', 'admin@example.com')->first();
        $maria  = User::where('email', 'maria@example.com')->first();
        $james  = User::where('email', 'james@example.com')->first();

        // Engineering team
        $eng = Team::firstOrCreate(
            ['slug' => 'engineering'],
            ['name' => 'Engineering', 'owner_id' => $maria->id]
        );

        $engMembers = User::whereIn('email', [
            'maria@example.com', 'sam@example.com', 'lily@example.com',
            'carlos@example.com', 'tom@example.com', 'omar@example.com',
        ])->get();

        foreach ($engMembers as $member) {
            $role = $member->email === 'maria@example.com' ? 'owner' : 'member';
            $eng->users()->syncWithoutDetaching([$member->id => ['role' => $role]]);
        }

        // Design team
        $design = Team::firstOrCreate(
            ['slug' => 'design'],
            ['name' => 'Design', 'owner_id' => $james->id]
        );

        $designMembers = User::whereIn('email', [
            'james@example.com', 'aisha@example.com', 'nadia@example.com', 'zoe@example.com',
        ])->get();

        foreach ($designMembers as $member) {
            $role = $member->email === 'james@example.com' ? 'owner' : 'member';
            $design->users()->syncWithoutDetaching([$member->id => ['role' => $role]]);
        }
    }
}
