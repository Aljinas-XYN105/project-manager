<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPosted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $showExpired = $request->boolean('expired') && $this->canManage($request->user());

        $announcements = Announcement::with('author')
            ->when(! $showExpired, fn ($q) => $q->visible())
            ->when($showExpired, fn ($q) => $q->orderByDesc('is_pinned')->latest())
            ->paginate(15)
            ->withQueryString();

        $canManage = $this->canManage($request->user());

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements->through(fn ($a) => [
                'id'         => $a->id,
                'title'      => $a->title,
                'body'       => $a->body,
                'is_pinned'  => $a->is_pinned,
                'expires_at' => $a->expires_at?->format('M j, Y'),
                'is_expired' => $a->expires_at && $a->expires_at->isPast(),
                'author'     => ['name' => $a->author?->name, 'avatar_url' => $a->author?->avatar_url],
                'created_at' => $a->created_at->diffForHumans(),
                'edit_url'   => $canManage ? route('announcements.update', $a) : null,
                'destroy_url'=> $canManage ? route('announcements.destroy', $a) : null,
            ]),
            'can_manage'  => $canManage,
            'show_expired'=> $showExpired,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage($request);
        $announcement = $request->user()->announcements()->create($this->validated($request));

        if ($request->boolean('notify')) {
            Notification::send(User::active()->where('id', '!=', $request->user()->id)->get(), new AnnouncementPosted($announcement));
        }

        return redirect()->route('announcements.index')->with('success', 'Announcement published.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $this->authorizeManage($request);
        $announcement->update($this->validated($request));

        return redirect()->route('announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        $this->authorizeManage($request);
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:160'],
            'body'       => ['required', 'string', 'max:5000'],
            'category'   => ['required', Rule::in(array_keys(Announcement::CATEGORIES))],
            'expires_on' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        return $data + ['is_pinned' => $request->boolean('is_pinned')];
    }

    private function canManage(User $user): bool
    {
        return $user->hasRole('admin') || $user->can('announcements.manage');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($this->canManage($request->user()), 403);
    }
}
