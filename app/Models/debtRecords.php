<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class debtRecords extends Model
{
    use HasFactory, Uuids;
    protected $guarded = [];

    public function clint_logs()
    {
        return $this->hasMany(ClientLog::class, 'debt_record_id');
    }
}