<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DictionaryEntry extends Model
{
    protected $fillable = [
        'table_name',
        'physical_name',
        'logical_name',
        'description',
        'public_token',
        'is_published',
    ];
}
