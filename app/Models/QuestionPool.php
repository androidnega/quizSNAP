<?php

namespace App\Models;

use App\Support\SchemaColumnFilter;
use App\Casts\EncryptedNullable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionPool extends Model
{
    protected $table = 'question_pools';

    protected $fillable = [
        'quiz_id', 'question_text', 'type', 'options', 'correct_answer', 'topic', 'is_approved',
        'explanation_wrong', 'explanation_correct',
    ];

    /** @param array<string, mixed> $attributes */
    public static function createFromAttributes(array $attributes): self
    {
        return static::create(SchemaColumnFilter::forModel(static::class, $attributes));
    }

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_approved' => 'boolean',
            'question_text' => EncryptedNullable::class,
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
