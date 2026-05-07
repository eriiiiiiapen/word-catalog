<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DictionaryLog extends Model
{
    protected $fillable = [
        'dictionary_entry_id',
        'action',
        'changes'
    ];

    protected $casts = [
        'changes' => 'array'
    ];

    public function dictionaryEntry()
    {
        $this->belongsTo(DictionaryEntry::class);
    }
}
