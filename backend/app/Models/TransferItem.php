<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItem extends Model
{
    use HasFactory;

    // La tabla transfer_items no define timestamps.
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'transfer_id',
        'batch_id',
        'quantity_sent',
        'quantity_received',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity_sent' => 'integer',
            'quantity_received' => 'integer',
        ];
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    // Diferencia calculada; no se persiste en la tabla.
    public function discrepancy(): ?int
    {
        if ($this->quantity_received === null) {
            return null;
        }

        return $this->quantity_sent - $this->quantity_received;
    }
}
