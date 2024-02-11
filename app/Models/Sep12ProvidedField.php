<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Sep12ProvidedField
 *
 * @property int $id
 * @property string $sep12_customer_id
 * @property int $sep12_field_id
 * @property string|null $status
 * @property string|null $error
 * @property string|null $string_value
 * @property int|null $number_value
 * @property mixed|null $binary_value
 * @property string|null $date_value
 * @property string|null $verification_code
 * @property int $verified
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereBinaryValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereDateValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereError($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereNumberValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereSep12CustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereSep12FieldId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereStringValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereVerificationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12ProvidedField whereVerified($value)
 * @mixin \Eloquent
 */
class Sep12ProvidedField extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep12_provided_fields';
}
