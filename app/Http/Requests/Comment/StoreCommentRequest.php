<?php

namespace App\Http\Requests\Comment;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Comment::class);
    }

    public function rules(): array
    {
        return [
            'body'               => ['required', 'string', 'max:5000'],
            'commentable_type'   => ['required', 'in:task,project'],
            'commentable_id'     => ['required', 'integer'],
            'parent_id'          => ['nullable', 'exists:comments,id'],
        ];
    }
}
