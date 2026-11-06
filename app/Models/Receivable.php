<?php

namespace App\Models;

use App\Enums\ReceivableStatus;
use Database\Factories\ReceivableFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Receivable extends Model
{
    /** @use HasFactory<ReceivableFactory> */
    use HasFactory;

    protected $fillable = [
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'due_at' => 'datetime',
            'status' => ReceivableStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
