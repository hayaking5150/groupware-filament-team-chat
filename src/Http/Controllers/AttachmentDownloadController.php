<?php

namespace Filament\TeamChat\Http\Controllers;

use Filament\TeamChat\Models\Attachment;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;

/**
 * Serves chat attachments. Access to a file is access to the message it is
 * attached to: a member of the channel, or a participant of the DM. The
 * bytes never pass through this controller; it only authorizes and redirects
 * to a short-lived signed URL, the same pattern the host application uses
 * for its own attachments — the file's own storage URL is never exposed.
 */
class AttachmentDownloadController extends Controller
{
    public function __invoke(Attachment $attachment): RedirectResponse
    {
        $message = $attachment->message()->with('messageable')->first();

        abort_if($message === null, 404);

        $messageable = $message->messageable;
        $userId = auth()->id();

        $accessible = match (true) {
            $messageable instanceof Channel => $messageable->isAccessibleBy($userId),
            $messageable instanceof Conversation => $messageable->isParticipant($userId),
            default => false,
        };

        // Unseen, not forbidden: reaching the message already went through
        // its channel or conversation, so anyone who cannot is told the file
        // does not exist rather than that it is off-limits.
        abort_unless($accessible, 404);

        return redirect()->away(
            Storage::disk(config('team-chat.uploads.disk', 'local'))->temporaryUrl(
                $attachment->file_path,
                now()->addMinutes((int) config('team-chat.uploads.url_lifetime_minutes', 10)),
            ),
        );
    }
}
