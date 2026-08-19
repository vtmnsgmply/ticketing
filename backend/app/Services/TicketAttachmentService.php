<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use App\Repository\TicketAttachmentRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketAttachmentService
{
    public function __construct(
        private readonly TicketAttachmentRepository $attachments,
    ) {
    }

    /**
     * @param list<UploadedFile> $files
     * @return list<TicketAttachment>
     */
    public function storeMany(Ticket $ticket, User $user, array $files, ?TicketMessage $message = null): array
    {
        $created = [];

        foreach ($files as $file) {
            $storedName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs("tickets/{$ticket->ticket_number}", $storedName, 'local');
            $mimeType = $file->getMimeType() ?: 'application/octet-stream';
            $thumbnailPath = str_starts_with($mimeType, 'image/')
                ? $this->storeImageThumbnailFromPath($ticket, $file->getRealPath(), $storedName, $mimeType)
                : null;

            $created[] = $this->attachments->create([
                'ticket_id' => $ticket->id,
                'ticket_message_id' => $message?->id,
                'uploaded_by' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'file_path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'mime_type' => $mimeType,
                'file_size' => $file->getSize() ?: 0,
                'created_at' => now(),
            ]);
        }

        return $created;
    }

    public function read(TicketAttachment $attachment): string
    {
        return Storage::disk('local')->get($attachment->file_path);
    }

    public function readThumbnail(TicketAttachment $attachment): ?string
    {
        if ($attachment->thumbnail_path && Storage::disk('local')->exists($attachment->thumbnail_path)) {
            return Storage::disk('local')->get($attachment->thumbnail_path);
        }

        if (! str_starts_with((string) $attachment->mime_type, 'image/') || ! Storage::disk('local')->exists($attachment->file_path)) {
            return null;
        }

        $ticket = $attachment->ticket()->first();
        if (! $ticket) {
            return null;
        }

        $thumbnailPath = $this->storeImageThumbnailFromPath(
            $ticket,
            Storage::disk('local')->path($attachment->file_path),
            $attachment->stored_name,
            (string) $attachment->mime_type,
        );

        if (! $thumbnailPath) {
            return null;
        }

        $attachment->thumbnail_path = $thumbnailPath;
        $attachment->save();

        return Storage::disk('local')->get($thumbnailPath);
    }

    public function deleteForMessage(TicketMessage $message): void
    {
        $attachments = TicketAttachment::query()
            ->where('ticket_message_id', $message->id)
            ->get();

        foreach ($attachments as $attachment) {
            Storage::disk('local')->delete($attachment->file_path);
            if ($attachment->thumbnail_path) {
                Storage::disk('local')->delete($attachment->thumbnail_path);
            }
            $attachment->delete();
        }
    }

    private function storeImageThumbnailFromPath(Ticket $ticket, string $sourcePath, string $storedName, string $mimeType): ?string
    {
        $source = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/gif' => @imagecreatefromgif($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $targetSize = 360;
        $scale = min($targetSize / max($width, 1), $targetSize / max($height, 1), 1);
        $targetWidth = max((int) round($width * $scale), 1);
        $targetHeight = max((int) round($height * $scale), 1);
        $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

        imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($thumbnail, null, 78);
        $contents = ob_get_clean();
        imagedestroy($source);
        imagedestroy($thumbnail);

        if (! $contents) {
            return null;
        }

        $path = "tickets/{$ticket->ticket_number}/thumbs/".pathinfo($storedName, PATHINFO_FILENAME).'.jpg';
        Storage::disk('local')->put($path, $contents);

        return $path;
    }
}
