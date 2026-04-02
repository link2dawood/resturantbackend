<?php

namespace App\Models;

use App\Constants\OwnerCcStatementCardPlatform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OwnerCcStatementImport extends Model
{
    protected $fillable = [
        'imported_by',
        'store_id',
        'card_platform',
        'file_name',
        'file_hash',
        'file_path',
        'card_last4',
        'rows_imported',
        'rows_skipped',
        'import_exceptions',
    ];

    protected $casts = [
        'rows_imported' => 'integer',
        'rows_skipped' => 'integer',
        'import_exceptions' => 'array',
    ];

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OwnerCcStatementLine::class, 'owner_cc_statement_import_id');
    }

    public function cardPlatformLabel(): ?string
    {
        return OwnerCcStatementCardPlatform::label($this->card_platform);
    }
}
