# Filament Team Chat

**A complete Slack-like team chat for Filament v5.** Drop it into any panel — channels, DMs, threads, reactions, mentions, file sharing, search, and unread tracking work out of the box. Self-hosted, no external services needed.

![Filament v5](https://img.shields.io/badge/Filament-v5.x-amber?style=flat-square)
![Laravel v13](https://img.shields.io/badge/Laravel-v13.x-red?style=flat-square)
![PHP 8.3+](https://img.shields.io/badge/PHP-8.3+-blue?style=flat-square)
![Tests](https://img.shields.io/badge/Tests-102%20passing-brightgreen?style=flat-square)
![License MIT](https://img.shields.io/badge/License-MIT-green?style=flat-square)

---

![Chat Overview](screenshots/01-chat-overview.png)

## Why Filament Team Chat?

- **Zero external dependencies** — No Pusher, no Redis, no WebSocket server. Works with Livewire polling out of the box.
- **Filament-native** — Lives inside your Filament panel. Uses your existing auth, your existing users, your existing database.
- **Multi-tenant ready** — Optional `team_id` scoping with automatic Filament tenant detection.
- **Self-hosted** — Your data stays on your server. No third-party chat services.

## Screenshots

<table>
  <tr>
    <td><strong>Threaded Conversations</strong><br/>Click any message to reply in a side panel — just like Slack.<br/><br/><img src="screenshots/02-thread-panel.png" alt="Thread Panel" /></td>
    <td><strong>File Attachments</strong><br/>Share images (with inline preview) and documents.<br/><br/><img src="screenshots/04-attachments.png" alt="File Attachments" /></td>
  </tr>
  <tr>
    <td colspan="2"><strong>Dark Mode</strong><br/>Full dark mode support, following your Filament panel theme.<br/><br/><img src="screenshots/03-dark-mode.png" alt="Dark Mode" /></td>
  </tr>
</table>

## Features

### Messaging
- **Channels** — Public and private channels with member management
- **Direct Messages** — 1-on-1 and group DMs
- **Threads** — Reply to any message in a side panel, with reply count on the main feed
- **Markdown** — Messages support **bold**, *italic*, `code`, lists, and links
- **Edit & Delete** — Edit or soft-delete your own messages (hover action bar)

### Collaboration
- **Reactions** — 8 built-in emoji reactions (toggle on/off)
- **@Mentions** — `@user`, `@channel`, `@here` with live autocomplete
- **File Attachments** — Upload multiple files per message, image previews, download links
- **Search** — Full-text search across all channels and DMs you belong to

### Awareness
- **Unread Badges** — Per-channel/DM unread counts with automatic read tracking
- **Instant Refresh** — Your own messages appear immediately; others update via polling
- **Online Status** — Presence indicators and custom status text
- **Notifications** — Database notifications for @mentions and DMs

### Management
- **Inline Channel Settings** — Edit name, topic, and visibility directly in the chat header (owner only)
- **Archive Channels** — Soft-archive channels to hide them from the sidebar
- **Public Auto-Join** — Public channels appear for all users; clicking auto-joins
- **Member List** — View channel/DM members with online indicators and profile cards

### Technical
- **Multi-Tenancy** — Optional `team_id` scoping with Filament tenant auto-detection
- **Dark Mode** — Follows your Filament panel theme
- **102 Tests** — Comprehensive test suite with Orchestra Testbench

## Installation

```bash
composer require qalainau/filament-team-chat
```

Publish and run the migrations:

```bash
php artisan vendor:publish --tag=team-chat-migrations
php artisan migrate
```

## Getting Started

### 1. Register the Plugin

```php
use Filament\TeamChat\FilamentTeamChatPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(FilamentTeamChatPlugin::make());
}
```

### 2. Add the Trait to Your User Model

```php
use Filament\TeamChat\Concerns\HasTeamChat;

class User extends Authenticatable
{
    use HasTeamChat;
}
```

### 3. Notifications Table (if needed)

Required for @mention and DM notifications:

```bash
php artisan make:notifications-table
php artisan migrate
```

### 4. Tailwind CSS Setup

The plugin uses Tailwind CSS classes that must be included in your Filament theme.

**If you don't have a custom theme yet**, create one first:

```bash
php artisan filament:theme
```

Then add the package views as a source in your theme file:

```css
/* resources/css/filament/admin/theme.css */
@source '../../../../vendor/qalainau/filament-team-chat/resources/views/**/*';
```

Build the theme:

```bash
npm run build
```

**Done!** Visit `/admin/team-chat` to start chatting.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=team-chat-config
```

```php
// config/team-chat.php
return [
    'table_prefix' => 'tc_',

    'user_model' => \App\Models\User::class,

    'polling' => [
        'messages' => 3,  // seconds
        'sidebar' => 5,   // seconds
    ],

    'uploads' => [
        'disk' => 'public',
        'directory' => 'team-chat-attachments',
        'max_size' => 10240, // KB
    ],

    'tenancy' => [
        'enabled' => false,
        'model' => null,    // e.g. \App\Models\Team::class
        'resolver' => null, // null = Filament::getTenant()
    ],
];
```

### Multi-Tenancy

Set `tenancy.enabled` to `true` to scope channels and conversations per team. The resolver supports:

| Mode | Config | Behavior |
|---|---|---|
| Auto (default) | `null` | Uses `Filament::getTenant()` |
| Callable | `fn () => auth()->user()->team_id` | Custom closure |
| Class | `TenantResolver::class` | Must have `resolve()` method |

## Programmatic API

All features are available as PHP classes — useful for seeders, commands, or integrations.

### Channels & DMs

```php
use Filament\TeamChat\Models\Channel;

// Create a channel
$channel = Channel::create([
    'name' => 'general',
    'slug' => 'general',
    'type' => 'public', // or 'private'
    'created_by' => $user->id,
]);
$channel->members()->attach($user->id, ['role' => 'owner']);

// DMs (idempotent — returns existing conversation if found)
$dm = $user->findOrCreateDirectMessage($otherUser->id);

// Group DM
$group = $user->createGroupConversation(
    userIds: [$user2->id, $user3->id],
    name: 'Project Team',
);
```

### Messages

```php
use Filament\TeamChat\Actions\SendMessage;

$message = app(SendMessage::class)->execute(
    messageable: $channel,    // Channel or Conversation
    userId: $user->id,
    body: 'Hello @Jordan! Check this **bold** text.',
    parentId: null,           // set for thread replies
    files: [],                // array of UploadedFile
);
```

### Reactions, Read Tracking, Search

```php
use Filament\TeamChat\Actions\{ToggleReaction, MarkAsRead, SearchMessages};

// Toggle reaction (returns true=added, false=removed)
app(ToggleReaction::class)->execute($message->id, $user->id, '👍');

// Unread count
$channel->unreadCountFor($user->id); // => 3

// Mark as read
app(MarkAsRead::class)->execute($channel, $user->id);

// Search (respects channel/DM membership)
$results = app(SearchMessages::class)->execute($user->id, 'deploy', limit: 20);
```

## Architecture

### Database

All tables use a configurable `tc_` prefix:

| Table | Purpose |
|---|---|
| `tc_channels` | Public/private channels (optional `team_id`) |
| `tc_channel_user` | Channel membership pivot with roles |
| `tc_conversations` | 1-on-1 and group DMs (optional `team_id`) |
| `tc_conversation_user` | DM participant pivot |
| `tc_messages` | Polymorphic messages (Channel or Conversation) |
| `tc_reactions` | Emoji reactions per message per user |
| `tc_attachments` | File metadata (name, path, MIME, size) |
| `tc_mentions` | @user / @channel / @here per message |
| `tc_read_receipts` | Per-user last-read tracking (polymorphic) |
| `tc_user_statuses` | Online status, display name, custom status |

### Livewire Components

| Component | Role | Updates |
|---|---|---|
| `Sidebar` | Channel/DM list, unread badges, create/join | 5s poll + event |
| `MessageFeed` | Messages, reactions, edit/delete, reply | 3s poll + event |
| `MessageComposer` | Input, file upload, @mention autocomplete | on submit |
| `ChannelHeader` | Name, topic, members, settings, archive | on event |
| `ThreadPanel` | Threaded replies with own composer | 3s poll |
| `SearchModal` | Full-text search across channels/DMs | on input |
| `MemberList` | Member list with online status | on open |
| `UserProfileCard` | Profile popup with DM shortcut | on open |

## Testing

```bash
# Run in the package directory
composer test

# Or in your Laravel app
php artisan test --filter=TeamChat
```

102 tests covering channels, DMs, threads, reactions, mentions, attachments, search, read receipts, notifications, user status, and channel management.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Fork notes (groupware)

This is a fork maintained for the [groupware](https://github.com/hayaking5150) internal
communication tool, kept because the upstream project is small and its long-term
maintenance is uncertain. It diverges from upstream in ways that matter for anyone
tracking this fork:

- **Private channels and DMs are now actually private.** Upstream's Livewire
  components (`Sidebar`, `MessageFeed`, `MessageComposer`, `ThreadPanel`) took a
  channel/conversation id from a dispatched browser event or a public method
  argument and trusted it. Any signed-in user could read or post into a private
  channel or someone else's DM by dispatching the event directly, bypassing the
  UI. Every entry point now calls `Channel::isAccessibleBy()` /
  `Conversation::isParticipant()`, and `SendMessage` checks again at the point a
  message is actually created, so the write path is safe even if a future caller
  forgets to check.
- **Message bodies are XSS-safe.** `Str::markdown()` was called with default
  options, which allow raw HTML straight through (`html_input` defaults to
  `allow` in league/commonmark) and are rendered with `{!! !!}`. A message
  containing `<script>` executed for everyone who read it. Markdown is now
  rendered with `html_input: escape`.
- **Attachments are private by default and always served through an authorizing
  route** (`AttachmentDownloadController`, registered as
  `team-chat.attachments.download`), not the storage disk's own URL. The default
  disk changed from `public` to `local`; `Attachment::getUrl()` no longer calls
  `Storage::url()` directly.
- Deactivated users (`deactivated_at` on the host app's user model) are excluded
  from the DM candidate list, mention suggestions and `startDirectMessage`'s
  validation.
- Default polling intervals changed to 5s (messages) / 15s (sidebar) for a
  ~100-user deployment.

See the upstream project at https://github.com/qalainau/filament-team-chat for
the original. Licensed MIT, same as upstream.
