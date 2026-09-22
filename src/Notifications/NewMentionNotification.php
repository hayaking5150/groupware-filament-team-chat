<?php

namespace Filament\TeamChat\Notifications;

use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\TeamChat\Models\Message;
use Filament\TeamChat\Pages\TeamChat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Message $message,
        public string $mentionType = 'user',
    ) {}

    /**
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Shaped for Filament's own notification bell, which every panel user
     * already has (the host app enables it on its single panel).
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->message->loadMissing('user');

        return FilamentNotification::make()
            ->title(__('team-chat::messages.notification.mentioned_title', ['name' => $this->message->user?->name ?? '']))
            ->body(str($this->message->body)->limit(120)->toString())
            ->actions([
                Action::make('open')
                    ->label(__('team-chat::messages.notification.open'))
                    ->url(TeamChat::getUrl())
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
