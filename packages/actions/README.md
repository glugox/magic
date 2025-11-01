
# Glugox/Actions

Nova-like **Actions** for Laravel apps with **progress tracking** and **broadcasted live updates** (works great with Reverb/Echo).

- Define actions as simple classes.
- Run sync or queued.
- Track progress in `action_runs` table.
- Broadcast `ActionProgressUpdated` so your UI shows live progress.

## Installation

```bash
composer require glugox/actions
php artisan vendor:publish --provider="Glugox\Actions\ActionServiceProvider" --tag=config
php artisan migrate
```

### Configure Broadcasting (optional but recommended)

Enable a broadcast driver (e.g., Reverb, Pusher) in your app's `.env`. Example (Reverb):

```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
```

## Defining an Action

```php
use Glugox\Actions\Contracts\Action;
use Glugox\Actions\DTO\ActionContext;
use Glugox\Actions\Support\Progress;

class ExportUsers implements Action
{
    public function name(): string
    {
        return 'Export Users';
    }

    public function handle(ActionContext $ctx, Progress $progress): array
    {
        $users = \App\Models\User::query()->cursor();
        $count = \App\Models\User::count();
        $done = 0;

        foreach ($users as $u) {
            // ... export ...
            $done++;
            if ($done % 50 === 0) {
                $progress->update(intval($done / max($count,1) * 100), "Exported {$done}/{$count} users");
            }
        }

        return ['message' => 'Export done', 'file' => '/storage/exports/users.csv'];
    }
}
```

## Triggering via HTTP

POST `actions/run` with:

```json
{
  "action": "App\\Actions\\ExportUsers",
  "queued": true,
  "params": { "format": "csv" },
  "targets": [1,2,3]
}
```

Response includes the `run_id`. Subscribe to the broadcast channel to get progress events:

```
private-action-run.{run_id}
event: Glugox\\Actions\\Events\\ActionProgressUpdated
```

## Triggering programmatically

```php
use Glugox\Actions\ActionRunner;

$runner = app(ActionRunner::class);
$run = $runner->run(new ExportUsers(), params: ['format' => 'csv'], userId: auth()->id());
// or queued:
$run = $runner->dispatch(new ExportUsers(), params: ['format' => 'csv'], userId: auth()->id());
```

## Security

By default, **any** fully-qualified class implementing `Contracts\Action` can be invoked.
You can restrict allowed actions by configuring `actions.allowed` in `config/actions.php`.

## Events & Channels

Event: `Glugox\Actions\Events\ActionProgressUpdated`  
Channel: `private-action-run.{id}` (configurable).  
Make sure you define the channel authorization callback in your app's `routes/channels.php`:

```php
Broadcast::channel('private-action-run.{id}', function ($user, $id) {
    // Check that the user can view this run id
    return true; // Replace with your policy
});
```

## Testing

```bash
composer install
vendor/bin/phpunit
```
