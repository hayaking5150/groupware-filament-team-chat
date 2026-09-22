<?php

namespace Filament\TeamChat\Actions;

use Filament\TeamChat\Models\Attachment;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Conversation;
use Filament\TeamChat\Models\Message;
use Filament\TeamChat\Notifications\NewDirectMessageNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SendMessage
{
    /**
     * @param  array<UploadedFile>  $files
     */
    public function execute(Model $messageable, int $userId, string $body, ?int $parentId = null, array $files = []): Message
    {
        $this->authorizeSender($messageable, $userId);

        // Escaped, not stripped: unlike the plain-text comments elsewhere in
        // the app, chat bodies are rendered from Markdown, so raw HTML in the
        // input must not reach the page as markup.
        $bodyHtml = Str::markdown($body, ['html_input' => 'escape', 'allow_unsafe_links' => false]);

        $message = Message::create([
            'messageable_type' => $messageable->getMorphClass(),
            'messageable_id' => $messageable->getKey(),
            'user_id' => $userId,
            'parent_id' => $parentId,
            'body' => $body,
            'body_html' => $bodyHtml,
        ]);

        // Parse and store mentions, update body_html with styled spans
        $updatedHtml = app(ParseMentions::class)->execute($message, $bodyHtml);

        if ($updatedHtml !== $bodyHtml) {
            $message->updateQuietly(['body_html' => $updatedHtml]);
        }

        // Store file attachments
        foreach ($files as $file) {
            $path = $file->store(
                config('team-chat.uploads.directory', 'team-chat-attachments'),
                config('team-chat.uploads.disk', 'local'),
            );

            Attachment::create([
                'message_id' => $message->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        // Notify DM participants (excluding the sender)
        if ($messageable instanceof Conversation) {
            $message->load('user');

            $messageable->participants()
                ->where('user_id', '!=', $userId)
                ->get()
                ->each(fn (Model $participant) => $participant->notify(
                    new NewDirectMessageNotification($message, $messageable),
                ));
        }

        return $message;
    }

    /**
     * The definitive check: even if every UI entry point were bypassed, a
     * message cannot be created into a channel or conversation the sender
     * does not belong to.
     */
    private function authorizeSender(Model $messageable, int $userId): void
    {
        $accessible = match (true) {
            $messageable instanceof Channel => $messageable->isAccessibleBy($userId),
            $messageable instanceof Conversation => $messageable->isParticipant($userId),
            default => false,
        };

        abort_unless($accessible, 403);
    }
}
