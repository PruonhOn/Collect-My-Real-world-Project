<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A system setting: a keyed value an administrator can change from the
 * Settings screen. Values are stored as text and cast back to the declared
 * type on read, so a boolean setting arrives as `true`/`false` rather than
 * `"1"`/`"0"`.
 */
class Setting extends Model
{
    use HasFactory;

    #[Fillable(['key', 'value', 'type', 'group', 'description'])]
    protected $guarded = [];

    public $timestamps = true;

    /**
     * Cast the stored string back to the declared type.
     */
    protected function casts(): array
    {
        return [
            'type' => 'string',
            'group' => 'string',
        ];
    }

    /**
     * The value, cast to the setting's declared type.
     *
     * Boolean settings store `"true"`/`"false"` and come back as real booleans.
     * Numeric settings store a decimal string and come back as a number.
     * Everything else arrives as the string that was saved.
     */
    public function typedValue(): Attribute
    {
        return Attribute::make(
            get: function (): mixed {
                $raw = $this->value;
                $type = $this->type ?? 'string';

                return match ($type) {
                    'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                    'integer' => $raw === null ? null : (int) $raw,
                    'decimal' => $raw === null ? null : (float) $raw,
                    'json' => $raw === null ? null : json_decode($raw, true),
                    default => $raw,
                };
            },
        );
    }
}
