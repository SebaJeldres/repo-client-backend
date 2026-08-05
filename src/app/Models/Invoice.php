<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'invoice_number',
        'supplier_name',
        'total_amount',
        'issue_date',
        'file_path',
        'vector_status',
        'vector_id',
        'vector_error',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}