<?php

namespace Filament\TeamChat\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Livewire\Attributes\On;
use UnitEnum;

class TeamChat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $slug = 'team-chat';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('team-chat::messages.page_title');
    }

    public function getTitle(): string
    {
        return __('team-chat::messages.page_title');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('team-chat::messages.nav_group');
    }

    protected string $view = 'team-chat::pages.team-chat';

    protected ?string $heading = '';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public ?string $activeType = null; // 'channel' or 'conversation'

    public ?int $activeId = null;

    public bool $showThreadPanel = false;

    public ?int $threadParentId = null;

    public function mount(): void
    {
        auth()->user()->touchOnline();

        $channel = auth()->user()->channels()->first();

        if ($channel) {
            $this->activeType = 'channel';
            $this->activeId = $channel->id;
        }
    }

    #[On('channel-selected')]
    public function selectChannel(int $channelId): void
    {
        $this->activeType = 'channel';
        $this->activeId = $channelId;
        $this->showThreadPanel = false;
        $this->threadParentId = null;
    }

    #[On('conversation-selected')]
    public function selectConversation(int $conversationId): void
    {
        $this->activeType = 'conversation';
        $this->activeId = $conversationId;
        $this->showThreadPanel = false;
        $this->threadParentId = null;
    }

    #[On('open-thread')]
    public function openThread(int $messageId): void
    {
        $this->threadParentId = $messageId;
        $this->showThreadPanel = true;
    }

    #[On('close-thread')]
    public function closeThread(): void
    {
        $this->showThreadPanel = false;
        $this->threadParentId = null;
    }
}
