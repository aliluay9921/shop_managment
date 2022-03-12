<?php

namespace App\Models;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodInvoice extends Model
{
    use HasFactory, Uuids;
    protected $table = 'good_invoices';
    protected $guarded = [];
    protected $with = ['good'];


    public function good()
    {
        return $this->belongsTo(Good::class, 'good_id');
    }
}
