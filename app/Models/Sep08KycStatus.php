<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Sep08KycStatus
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus query()
 * @property string $stellar_address
 * @property int $approved
 * @property int $rejected
 * @property int $pending
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus whereApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus wherePending($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus whereRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus whereStellarAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus whereUpdatedAt($value)
 * @property int $id
 * @method static \Illuminate\Database\Eloquent\Builder|Sep08KycStatus whereId($value)
 * @mixin \Eloquent
 */
class Sep08KycStatus extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep08_kyc_status';

    protected $fillable = [
        'stellar_address',
        'approved',
        'rejected',
        'pending',
        'created_at',
        'updated_at',
    ];
}
