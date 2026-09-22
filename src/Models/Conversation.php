<?php

namespace Filament\TeamChat\Models;

use Filament\TeamChat\Concerns\BelongsToTeam;
use Filament\TeamChat\Concerns\HasReadReceipts;
use Filament\TeamChat\Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use BelongsToTeam, HasFactory, HasReadReceipts;

    protected static string $factory = ConversationFactory::class;

    protected $table = 'tc_conversations';

    protected $fillable = [
        'is_group',
        'name',
        'team_id',
    ];

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
        ];
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(config('team-chat.user_model'), 'tc_conversation_user')
            ->withPivot(['is_muted'])
            ->withTimestamps();
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable');
    }

    public function isParticipant(int $userId): bool
    {
        return $this->participants()->where('user_id', $userId)->exists();
    }

    public function getDisplayNameForUser(Model $user): string
    {
        if ($this->is_group && $this->name) {
            return $this->name;
        }

        return $this->participants
            ->reject(fn (Model $participant) => $participant->getKey() === $user->getKey())
            ->pluck('name')
            ->join(', ') ?: 'DM';
    }
}
