<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttachmentResource;
use App\Jobs\ProcessAttachment;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    /**
     * List attachments for a given attachable entity.
     */
    public function index(Request $request)
    {
        $request->validate([
            'attachable_type' => 'required|string',
            'attachable_id' => 'required|integer',
        ]);

        $attachments = Attachment::where('attachable_type', $request->attachable_type)
            ->where('attachable_id', $request->attachable_id)
            ->orderBy('order_index')
            ->get();

        return AttachmentResource::collection($attachments);
    }

    /**
     * Upload and attach a file to a polymorphic entity.
     */
    public function store(Request $request)
    {
        $request->validate([
            'attachable_type' => 'required|string',
            'attachable_id' => 'required|integer',
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('attachments', $filename, 'public');

        $attachment = Attachment::create([
            'attachable_type' => $request->attachable_type,
            'attachable_id' => $request->attachable_id,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'file_path' => $path,
            'order_index' => Attachment::where('attachable_type', $request->attachable_type)
                ->where('attachable_id', $request->attachable_id)
                ->max('order_index') + 1,
        ]);

        // Queue processing (thumbnails, etc.)
        ProcessAttachment::dispatch($attachment);

        return new AttachmentResource($attachment);
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Attachment $attachment)
    {
        DB::transaction(function () use ($attachment): void {
            Storage::disk('public')->delete($attachment->path);

            // delete thumbnail if exists
            $thumbnailPath = preg_replace('/(\.\w+)$/', '_thumb$1', $attachment->path);
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }

            $attachment->delete();
        });

        return response()->json(['message' => 'Attachment deleted.']);
    }
}
