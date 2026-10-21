<?php

namespace App\Services;

use App\Support\AuditRedactor;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;

/**
 * Audit trail of every write made from the /platform panel, kept in activity_log under `platform`.
 */
class PlatformAudit
{
    public const string LOG = 'platform';

    /** Called while serving the panel, so model writes elsewhere in the app are not recorded here. */
    public function listenForModelChanges(): void
    {
        if (app()->bound('platform-audit.listening')) {
            return;
        }

        app()->instance('platform-audit.listening', true);

        Event::listen(['eloquent.created: *', 'eloquent.updated: *', 'eloquent.deleted: *'], function (string $event, array $payload): void {
            $model = $payload[0];

            if (! $model instanceof Model || $model instanceof Activity) {
                return;
            }

            $verb = str($event)->between('eloquent.', ':')->toString();
            $changes = AuditRedactor::redact($verb === 'deleted' ? $model->getAttributes() : $model->getChanges());
            unset($changes['updated_at'], $changes['created_at']);

            if ($changes === []) {
                return;
            }

            $this->record($verb, $model, ['attributes' => $changes]);
        });
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(string $event, ?Model $subject, array $properties = []): void
    {
        $logger = activity(self::LOG)
            ->causedBy(Filament::auth()->user())
            ->event($event)
            ->withProperties($properties);

        if ($subject) {
            $logger->performedOn($subject);
        }

        $logger->log($event.' '.($subject ? class_basename($subject).' #'.$subject->getKey() : ''));
    }
}
