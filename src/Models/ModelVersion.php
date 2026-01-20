<?php

declare(strict_types=1);

namespace Thumbrise\LaravelVersionedModel\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int                  $id
 * @property null|Carbon          $created_at
 * @property null|Carbon          $updated_at
 * @property string               $model_type
 * @property int                  $model_id
 * @property null|string          $changer_type
 * @property null|int             $changer_id
 * @property int                  $version
 * @property array<string, mixed> $snapshot
 * @property Eloquent|Model       $model
 * @property Eloquent|Model       $changer
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion whereChangerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion whereChangerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion whereVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion whereSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ModelVersion whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class ModelVersion extends Model
{
    protected $table = 'model_versions';

    protected $fillable = [
        'model_type',
        'model_id',
        'changer_type',
        'changer_id',
        'version',
        'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'version'  => 'integer',
    ];

    /**
     * @return MorphTo<Model, ModelVersion>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, ModelVersion>
     */
    public function changer(): MorphTo
    {
        return $this->morphTo();
    }
}
