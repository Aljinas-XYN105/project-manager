<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $expenses = $user->expenses()
            ->with('reviewer')
            ->latest('expense_date')
            ->paginate(20);

        $pendingReview = $this->reviewableQuery($user)->where('status', 'pending')->count();

        $summary = [
            'total_pending'  => $user->expenses()->where('status', 'pending')->sum('amount'),
            'total_approved' => $user->expenses()->where('status', 'approved')->sum('amount'),
            'count_pending'  => $user->expenses()->where('status', 'pending')->count(),
            'count_approved' => $user->expenses()->where('status', 'approved')->count(),
        ];

        $categories = \App\Models\Expense::CATEGORIES;
        $statuses   = \App\Models\Expense::STATUSES;

        return Inertia::render('Expenses/Index', [
            'expenses'      => $expenses->through(fn ($e) => [
                'id'          => $e->id,
                'title'       => $e->title,
                'amount'      => $e->amount,
                'currency'    => $e->currency,
                'category'    => $e->category,
                'status'      => $e->status,
                'status_label'=> $statuses[$e->status]['label'] ?? $e->status,
                'status_class'=> $statuses[$e->status]['class'] ?? '',
                'expense_date'=> $e->expense_date->format('D, M j, Y'),
                'description' => $e->description,
                'review_note' => $e->review_note,
                'reviewed_at' => $e->reviewed_at?->diffForHumans(),
                'reviewer'    => $e->reviewer ? ['name' => $e->reviewer->name] : null,
                'has_receipts'  => !empty($e->receipts) || !empty($e->receipt_path),
                'receipt_urls'  => collect($e->receipts ?? [])->keys()->map(fn ($i) => [
                    'url'  => route('expenses.receipt', [$e, $i]),
                    'name' => ($e->receipts[$i]['name'] ?? 'Receipt '.($i+1)),
                ])->when(empty($e->receipts) && $e->receipt_path, fn ($c) => $c->push([
                    'url'  => route('expenses.receipt', $e),
                    'name' => $e->receipt_name ?? 'Receipt',
                ]))->values(),
                'cancel_url'  => $e->status === 'pending' ? route('expenses.cancel', $e) : null,
            ]),
            'pending_review'=> $pendingReview,
            'summary'       => $summary,
            'categories'    => $categories,
            'store_url'     => route('expenses.store'),
            'approvals_url' => route('expenses.approvals'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'          => ['required', 'string', 'max:255'],
            'amount'         => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'currency'       => ['nullable', 'string', 'size:3'],
            'category'       => ['required', 'string', 'in:'.implode(',', Expense::CATEGORIES)],
            'expense_date'   => ['required', 'date', 'before_or_equal:today'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'receipts'       => ['nullable', 'array', 'max:10'],
            'receipts.*'     => ['file', 'mimes:jpg,jpeg,png,pdf,webp', 'max:5120'],
        ]);

        $receipts = [];
        foreach ($request->file('receipts', []) as $file) {
            $receipts[] = [
                'path' => $file->store('expenses/receipts', 'private'),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        $request->user()->expenses()->create([
            'title'        => $data['title'],
            'amount'       => $data['amount'],
            'currency'     => $data['currency'] ?? 'INR',
            'category'     => $data['category'],
            'expense_date' => $data['expense_date'],
            'description'  => $data['description'] ?? null,
            'receipts'     => $receipts ?: null,
            'status'       => 'pending',
        ]);

        return redirect()->route('expenses.index')->with('success', 'Expense submitted for approval.');
    }

    public function cancel(Request $request, Expense $expense)
    {
        abort_unless($expense->user_id === $request->user()->id, 403);
        abort_unless($expense->status === 'pending', 422);

        $expense->update(['status' => 'rejected', 'review_note' => 'Cancelled by submitter.']);

        return back()->with('success', 'Expense cancelled.');
    }

    public function approvals(Request $request)
    {
        $user   = $request->user();
        $status = $request->input('status', 'pending');

        $expenses = $this->reviewableQuery($user)
            ->with(['user', 'reviewer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderBy($status === 'pending' ? 'created_at' : 'reviewed_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        $statuses = \App\Models\Expense::STATUSES;

        return Inertia::render('Expenses/Approvals', [
            'expenses' => $expenses->through(fn ($e) => [
                'id'          => $e->id,
                'title'       => $e->title,
                'amount'      => $e->amount,
                'currency'    => $e->currency,
                'category'    => $e->category,
                'status'      => $e->status,
                'status_label'=> $statuses[$e->status]['label'] ?? $e->status,
                'status_class'=> $statuses[$e->status]['class'] ?? '',
                'expense_date'=> $e->expense_date->format('D, M j, Y'),
                'description' => $e->description,
                'review_note' => $e->review_note,
                'reviewed_at' => $e->reviewed_at?->diffForHumans(),
                'created_at'  => $e->created_at->diffForHumans(),
                'reviewer'    => $e->reviewer ? ['name' => $e->reviewer->name] : null,
                'user'        => $e->user ? ['name' => $e->user->name] : null,
                'has_receipts'=> !empty($e->receipts) || !empty($e->receipt_path),
                'receipt_urls'=> collect($e->receipts ?? [])->keys()->map(fn ($i) => [
                    'url'  => route('expenses.receipt', [$e, $i]),
                    'name' => ($e->receipts[$i]['name'] ?? 'Receipt '.($i+1)),
                ])->values(),
                'review_url'  => route('expenses.review', $e),
            ]),
            'status'       => $status,
            'index_url'    => route('expenses.index'),
        ]);
    }

    public function review(Request $request, Expense $expense)
    {
        abort_unless($expense->canBeReviewedBy($request->user()), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'note'     => ['nullable', 'string', 'max:500', 'required_if:decision,rejected'],
        ], ['note.required_if' => 'Please give a reason when rejecting.']);

        if ($expense->status !== 'pending') {
            return back()->with('error', 'This expense was already '.$expense->status.'.');
        }

        $expense->update([
            'status'      => $data['decision'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['note'] ?? null,
        ]);

        return back()->with('success', "{$expense->user->name}'s expense ".($data['decision'] === 'approved' ? 'approved' : 'rejected').'.');
    }

    public function receipt(Expense $expense, Request $request, int $index = 0)
    {
        $user = $request->user();
        abort_unless(
            $expense->user_id === $user->id || $expense->canBeReviewedBy($user) || $user->hasRole('admin'),
            403
        );

        // Support legacy single receipt_path
        if ($expense->receipt_path && empty($expense->receipts)) {
            return Storage::disk('private')->download($expense->receipt_path, $expense->receipt_name ?? 'receipt');
        }

        $receipt = $expense->receipts[$index] ?? null;
        abort_if(! $receipt, 404);

        return Storage::disk('private')->download($receipt['path'], $receipt['name'] ?? 'receipt');
    }

    private function reviewableQuery(User $user)
    {
        $query = Expense::query()->where('user_id', '!=', $user->id);

        if ($user->hasRole('admin') || $user->hasRole('manager')) {
            return $query;
        }

        return $query->whereIn('user_id', $user->directReports()->pluck('id'));
    }
}
