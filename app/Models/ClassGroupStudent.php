<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class ClassGroupStudent extends Model
{
    protected $fillable = ['class_group_id', 'index_number', 'index_number_hash', 'student_name'];

    protected static function booted(): void
    {
        static::saving(function (ClassGroupStudent $model): void {
            if (is_string($model->index_number) && trim($model->index_number) !== '') {
                $model->index_number_hash = Student::hashIndexNumber($model->index_number);
            }
        });
    }

    public static function findByIndexNumber(string $index): ?self
    {
        return self::allByIndexNumber($index)->first();
    }

    /**
     * All class-group rows for an index (student may belong to multiple groups).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function allByIndexNumber(string $index): \Illuminate\Database\Eloquent\Collection
    {
        $hash = Student::hashIndexNumber($index);
        $query = static::query()->with('classGroup.examiner');

        if ($hash !== hash('sha256', '') && Schema::hasColumn((new self)->getTable(), 'index_number_hash')) {
            $matches = (clone $query)->where('index_number_hash', $hash)->get();
            if ($matches->isNotEmpty()) {
                return $matches;
            }
        }

        return $query
            ->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($index))])
            ->get();
    }

    public static function existsInClassGroup(int $classGroupId, string $index): bool
    {
        $hash = Student::hashIndexNumber($index);
        if ($hash === hash('sha256', '')) {
            return false;
        }

        $query = static::where('class_group_id', $classGroupId);

        if (Schema::hasColumn((new self)->getTable(), 'index_number_hash')) {
            return $query->where('index_number_hash', $hash)->exists();
        }

        return $query->whereRaw('UPPER(TRIM(index_number)) = ?', [strtoupper(trim($index))])->exists();
    }

    public function classGroup(): BelongsTo
    {
        return $this->belongsTo(ClassGroup::class);
    }

    /** Linked student account (by index_number) if they have logged in and set phone. */
    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'index_number', 'index_number');
    }
}
