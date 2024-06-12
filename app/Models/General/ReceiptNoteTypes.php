<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptNoteTypes extends Model
{
    use SoftDeletes;

    protected $connection = "connection_first";
    protected $table = "ap_general.receipt_note_types";
    protected $guarded = ['id'];
    protected $hidden = [
        'deleted_at',
        'created_at',
        'updated_at'
    ];
}
