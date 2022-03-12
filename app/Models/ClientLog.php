<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientLog extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];

    public function debt_record()
    {
        return $this->belongsTo(debtRecords::class, 'debt_record_id');
    }
}