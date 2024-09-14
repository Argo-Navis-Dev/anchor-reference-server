<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Sep12Field
 *
 * @property int $id
 * @property string $key
 * @property string $type
 * @property string $desc
 * @property string|null $choices
 * @property int $requires_verification
 * @property string $lang
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field whereChoices($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field whereDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field whereRequiresVerification($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Field whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Sep12Field extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep12_fields';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'type',
        'desc',
        'choices',
        'requires_verification',
        'lang'
    ];
}
