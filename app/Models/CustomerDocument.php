<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class CustomerDocument extends Model
{
    use LogsActivity;

    protected $fillable = [
        'customer_detail_id',
        'document_name',
        'file_path',
        'file_original_name',
        'file_type',
    ];

    public function customerDetail()
    {
        return $this->belongsTo(CustomerDetail::class, 'customer_detail_id');
    }
}
