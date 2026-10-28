<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::where('email', 'aljinas@xynosoft.in')->firstOrFail();

        $devMembers = User::whereIn('email', [
            'sunitha@xynosoft.in', 'pranathi@xynosoft.in', 'sajin@xynosoft.in',
            'jinu@xynosoft.in', 'annmaria@xynosoft.in',
        ])->get();

        $qaDesignMembers = User::whereIn('email', [
            'lidiya@xynosoft.in', 'suhana@xynosoft.in', 'aishwarya@xynosoft.in',
        ])->get();

        // Development team
        $dev = Team::firstOrCreate(
            ['slug' => 'development'],
            ['name' => 'Development', 'owner_id' => $manager->id]
        );
        $dev->users()->syncWithoutDetaching([$manager->id => ['role' => 'owner']]);
        foreach ($devMembers as $m) {
            $dev->users()->syncWithoutDetaching([$m->id => ['role' => 'member']]);
        }

        // Design & QA team
        $qa = Team::firstOrCreate(
            ['slug' => 'design-qa'],
            ['name' => 'Design & QA', 'owner_id' => $manager->id]
        );
        $qa->users()->syncWithoutDetaching([$manager->id => ['role' => 'owner']]);
        foreach ($qaDesignMembers as $m) {
            $qa->users()->syncWithoutDetaching([$m->id => ['role' => 'member']]);
        }
    }
}
