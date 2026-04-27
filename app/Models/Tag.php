<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'color',
    ];
    
    public function dictionaryEntries()
    {
        return $this->belongsToMany(DictionaryEntry::class);
    }
}
