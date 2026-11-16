<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DataTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'name',
        'columns',
        'rows_count',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'columns'    => 'array',
            'rows_count' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DataTable $table) {
            $table->public_id ??= Str::random(20);
            $table->columns ??= [];
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(DataTableRow::class);
    }
}
