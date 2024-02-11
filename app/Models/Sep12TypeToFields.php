<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Sep12TypeToFields
 *
 * @property int $id
 * @property string $type
 * @property string|null $required_fields
 * @property string|null $optional_fields
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12TypeToFields newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12TypeToFields newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12TypeToFields query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12TypeToFields whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12TypeToFields whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12TypeToFields whereOptionalFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12TypeToFields whereRequiredFields($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12TypeToFields whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12TypeToFields whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Sep12TypeToFields extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep12_type_to_fields';
}
