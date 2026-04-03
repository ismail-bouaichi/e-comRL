<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function journalEntryLines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
