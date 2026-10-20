<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip,txt,csv',
        ]);

        $file         = $request->file('file');
        $filename     = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path         = $file->storeAs('attachments', $filename, 'public');

        $attachment = Attachment::create([
            'attachable_type' => Task::class,
            'attachable_id'   => $task->id,
            'user_id'         => $request->user()->id,
            'filename'        => $filename,
            'original_name'   => $file->getClientOriginalName(),
            'disk'            => 'public',
            'path'            => $path,
            'mime_type'       => $file->getMimeType(),
            'size'            => $file->getSize(),
        ]);

        return response()->json([
            'attachment' => $attachment->load('user'),
            'url'        => Storage::disk('public')->url($path),
        ]);
    }

    public function destroy(Attachment $attachment)
    {
        $this->authorize('update', $attachment->attachable);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->json(['success' => true]);
    }
}
