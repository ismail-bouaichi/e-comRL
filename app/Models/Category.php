<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Category extends Model
{
    use HasFactory;

    protected $table = 'categories'; // Name of the categories table
    protected $fillable = ['name','icon','slug'];

    public function scopeSearch($query,$value) {

        $query->where('name','like',"%{$value}%"); 
        
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getIconUrlAttribute()
{
    return $this->icon ? asset('storage/' . $this->icon) : null;
}


protected static function boot()
{
    parent::boot();
    
    static::creating(function ($model) {
        $model->slug = Str::slug($model->name);
    });
    
    static::updating(function ($model) {
        if ($model->isDirty('name')) {
            $model->slug = Str::slug($model->name);
        }
    });
}
   
}
