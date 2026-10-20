<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    private array $bodies = [
        'Looks good to me! Ready to move forward.',
        'I have some concerns about the implementation — let\'s sync up.',
        'Can you provide more context on the requirements here?',
        'This has been resolved in commit abc123.',
        'Working on this now, should be done by EOD.',
        'Blocked by the upstream API change — need to coordinate with the platform team.',
        'Good progress! Left a few inline comments for review.',
        'Confirmed this works in staging. Deploying to prod after sign-off.',
        'Nice work! The UX on this is really clean.',
        'Found a related issue — adding to the backlog.',
        'Updated the docs to reflect this change.',
        'Tested on iOS and Android — both look great.',
    ];

    public function run(): void
    {
        $tasks = Task::all();
        $users = User::all();

        foreach ($tasks as $task) {
            $count = rand(1, 4);
            for ($i = 0; $i < $count; $i++) {
                Comment::create([
                    'commentable_type' => Task::class,
                    'commentable_id'   => $task->id,
                    'user_id'          => $users->random()->id,
                    'body'             => $this->bodies[array_rand($this->bodies)],
                    'is_pinned'        => $i === 0 && rand(0, 4) === 0,
                    'created_at'       => now()->subDays(rand(0, 20)),
                ]);
            }
        }
    }
}
