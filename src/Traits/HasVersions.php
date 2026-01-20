<?php

declare(strict_types=1);

namespace Thumbrise\LaravelVersionedModel\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Thumbrise\LaravelVersionedModel\Models\ModelVersion;

/**
 * @mixin Model
 */
trait HasVersions
{
    /**
     * @return MorphMany<ModelVersion>
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(ModelVersion::class, 'model')->orderBy('version');
    }

    /**
     * @return MorphOne<ModelVersion>
     */
    public function latestVersion(): MorphOne
    {
        return $this->morphOne(ModelVersion::class, 'model')->latestOfMany('version');
    }

    /**
     * Update model and create a version snapshot.
     *
     * @param array<string, mixed> $attributes
     */
    public function updateVersioned(array $attributes): bool
    {
        if (empty($attributes)) {
            return true;
        }

        return DB::transaction(function () use ($attributes) {
            // Fill and save the model
            $this->fill($attributes);

            if (! $this->save()) {
                return false;
            }

            // Get next version number
            $latestVersion = ModelVersion::where('model_type', $this->getMorphClass())
                ->where('model_id', $this->getKey())
                ->max('version') ?? 0
            ;

            $nextVersion = $latestVersion + 1;

            // Create version snapshot
            ModelVersion::create([
                'model_type'   => $this->getMorphClass(),
                'model_id'     => $this->getKey(),
                'changer_type' => static::resolveChanger()?->getMorphClass(),
                'changer_id'   => static::resolveChanger()?->getKey(),
                'version'      => $nextVersion,
                'snapshot'     => static::createSnapshot($this),
            ]);

            // Clear cached relationships
            unset($this->relations['versions'], $this->relations['latestVersion']);

            return true;
        });
    }

    /**
     * Get a specific version of the model.
     */
    public function getVersion(int $version): ?ModelVersion
    {
        return $this->versions()->where('version', $version)->first();
    }

    /**
     * Get the latest version.
     */
    public function getLatestVersion(): ?ModelVersion
    {
        return $this->latestVersion;
    }

    /**
     * Get all versions.
     *
     * @return Collection<int, ModelVersion>
     */
    public function getVersions(): Collection
    {
        return $this->versions()->get();
    }

    /**
     * Get diff between two versions
     * If $fromVersion is null, uses initial state (empty array)
     * If $toVersion is null, uses current state.
     *
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public function getDiff(?int $fromVersion = null, ?int $toVersion = null): array
    {
        $fromSnapshot = $fromVersion
            ? $this->getVersion($fromVersion)?->snapshot ?? []
            : [];

        $toSnapshot = $toVersion
            ? $this->getVersion($toVersion)?->snapshot ?? []
            : static::createSnapshot($this);

        $diff    = [];
        $allKeys = array_unique(array_merge(array_keys($fromSnapshot), array_keys($toSnapshot)));

        foreach ($allKeys as $key) {
            $oldValue = $fromSnapshot[$key] ?? null;
            $newValue = $toSnapshot[$key]   ?? null;

            if ($oldValue !== $newValue) {
                $diff[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $diff;
    }

    /**
     * Revert model to a specific version.
     */
    public function revertToVersion(int $version): bool
    {
        $versionModel = $this->getVersion($version);

        if (! $versionModel) {
            return false;
        }

        $snapshot = $versionModel->snapshot;

        foreach ($snapshot as $key => $value) {
            if (static::shouldTrackField($key)) {
                $this->{$key} = $value;
            }
        }

        return $this->updateVersioned($this->getDirty());
    }

    /**
     * Get history of changes for a specific field.
     *
     * @return array<int, array{version: int, value: mixed, changed_at: null|Carbon, changer: null|Model}>
     */
    public function getFieldHistory(string $field): array
    {
        $history  = [];
        $versions = $this->getVersions();

        foreach ($versions as $version) {
            if (isset($version->snapshot[$field])) {
                $history[] = [
                    'version'    => $version->version,
                    'value'      => $version->snapshot[$field],
                    'changed_at' => $version->created_at,
                    'changer'    => $version->changer,
                ];
            }
        }

        return $history;
    }

    /**
     * Get history of changes for multiple fields.
     *
     * @param array<int, string> $fields
     *
     * @return array<string, array<int, array{version: int, value: mixed, changed_at: null|Carbon, changer: null|Model}>>
     */
    public function getFieldsHistory(array $fields): array
    {
        $history = [];

        foreach ($fields as $field) {
            $history[$field] = $this->getFieldHistory($field);
        }

        return $history;
    }

    /**
     * Determine if a field should be tracked.
     */
    protected static function shouldTrackField(string $field): bool
    {
        $excludedFields = array_merge(
            ['updated_at', 'created_at'],
            static::getExcludedVersionFields(),
        );

        return ! in_array($field, $excludedFields, true);
    }

    /**
     * Get additional fields to exclude from versioning
     * Override this method in your model to exclude custom fields.
     *
     * @return array<int, string>
     */
    protected static function getExcludedVersionFields(): array
    {
        return [];
    }

    /**
     * Resolve the user/entity that made the change
     * Override this method to customize how the changer is resolved.
     */
    protected static function resolveChanger(): ?Model
    {
        return Auth::user();
    }

    /**
     * Create a snapshot of the model's current state
     * Stores all attributes except system fields.
     *
     * @return array<string, mixed>
     */
    protected static function createSnapshot(Model $model): array
    {
        $snapshot   = [];
        $attributes = $model->getAttributes();

        foreach ($attributes as $key => $value) {
            if (static::shouldTrackField($key)) {
                $snapshot[$key] = $value;
            }
        }

        return $snapshot;
    }
}
