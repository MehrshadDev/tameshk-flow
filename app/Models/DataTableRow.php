<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataTableRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_table_id',
        'data',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'data'       => 'array',
            'size_bytes' => 'integer',
        ];
    }

    public function dataTable(): BelongsTo
    {
        return $this->belongsTo(DataTable::class);
    }

    public function toFormattedRecord(): array
    {
        $payload = is_array($this->data) ? $this->data : [];

        return [
            'id' => $this->id,
            ...$payload,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
