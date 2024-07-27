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
 * @property string|null $callback_url
 * @property string $lang
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep12Customer whereCallbackUrl($value)
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

    protected $appends = ['name', 'email', 'id_number', 'id_type', 'idTypeWithNumber'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep12_customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'account_id',
        'memo',
        'status',
        'type',
        'message',
        'callback_url',
        'lang'
    ];

    public function getNameAttribute(): string
    {
        //TODO check performance issues
        $firstNameField = Sep12Field::where('key', 'first_name')->first();
        $lastNameField = Sep12Field::where('key', 'last_name')->first();

        $firstName = Sep12ProvidedField::where('sep12_customer_id', $this->id)
            ->where('sep12_field_id', $firstNameField->id)
            ->first();

        $lastName = Sep12ProvidedField::where('sep12_customer_id', $this->id)
            ->where('sep12_field_id', $lastNameField->id)
            ->first();
        return "{$firstName->string_value} {$lastName->string_value}";
    }

    public function getEmailAttribute(): string {
        return $this->getProvidedFieldValue('email_address');
    }

    public function getIdNumberAttribute(): string {
        return $this->getProvidedFieldValue('id_number');
    }
    public function getIdTypeAttribute(): string {
        $value = $this->getProvidedFieldValue('id_type');

        return __("sep12_lang.kyc.id_type.{$value}");
    }

    public function getIdTypeWithNumberAttribute(): string {
        return "{$this->getIdTypeAttribute()}: {$this->getIdNumberAttribute()}";
    }

    private function getProvidedFieldValue(string $fieldName): string {
        $field = Sep12Field::where('key', $fieldName)->first();
        $providedField = Sep12ProvidedField::where('sep12_customer_id', $this->id)
            ->where('sep12_field_id', $field->id)
            ->first();
        return $providedField->string_value;
    }
}
