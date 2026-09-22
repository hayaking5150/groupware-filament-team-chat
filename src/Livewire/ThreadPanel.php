<?php

namespace Filament\TeamChat\Livewire;

use Filament\TeamChat\Actions\SendMessage;
use Filament\TeamChat\Models\Channel;
use Filament\TeamChat\Models\Conversation;
use Filament\TeamChat\Models\Message;
use Illuminate\Support\Collection;
use Livewire\Attributes\Isolate;
use Livewire\Attributes\On;
use Livewire\Component;

#[Isolate]
class ThreadPanel extends Component
{
    public ?int $parentMessageId = null;

    public ?Message $parentMessage = null;

    public string $replyBody = '';

    public function mount(?int $parentMessageId = null): void
    {
        if ($parentMessageId) {
            $this->loadThread($parentMessageId);
        }
    }

    #[On('open-thread')]
    public function loadThread(int $messageId): void
    {
        $message = Message::with(['user', 'messageable'])->findOrFail($messageId);

        abort_unless($this->accessible($message), 403);

        $this->parentMessageId = $messageId;
        $this->parentMessage = $message;
        $this->replyBody = '';
    }

    /**
     * A thread can be opened for any message id via a dispatched browser
     * event, not only one from the feed currently on screen, so this is
     * checked again here rather than trusted from the caller.
     */
    private function accessible(Message $message): bool
    {
        $messageable = $message->messageable;
        $userId = auth()->id();

        return match (true) {
            $messageable instanceof Channel => $messageable->isAccessibleBy($userId),
            $messageable instanceof Conversation => $messageable->isParticipant($userId),
            default => false,
        };
    }

    public function getRepliesProperty(): Collection
    {
        if (! $this->parentMessageId) {
            return collect();
        }

        return Message::where('parent_id', $this->parentMessageId)
            ->with('user')
            ->orderBy('created_at')
            ->get();
    }

    public function sendReply(): void
    {
        if (! $this->parentMessage || trim($this->replyBody) === '') {
            return;
        }

        app(SendMessage::class)->execute(
            messageable: $this->parentMessage->messageable,
            userId: auth()->id(),
            body: $this->replyBody,
            parentId: $this->parentMessageId,
        );

        $this->replyBody = '';
        $this->dispatch('message-sent');
    }

    public function render()
    {
        return view('team-chat::livewire.thread-panel');
    }
}
