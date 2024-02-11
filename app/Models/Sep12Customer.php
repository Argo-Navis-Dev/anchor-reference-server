<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Sep12Customer
 *
 * @property string $id
 * @property string $account_id
 * @property int|null $memo
 * @property string $status
 * @property string $type
 * @property string|null $message
 * @property string $lang
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereLang($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Sep12Customer extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep12_customers';
}
