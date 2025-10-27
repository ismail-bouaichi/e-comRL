<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name','logo_path','slug'];

    public function products() {
        return $this->hasMany(Product::class);
    }
    public function scopeSearch($query,$value) {

        $query->where('name','like',"%{$value}%"); 
        
    }

    public function getLogoUrlAttribute()
{
    return $this->logo_path ? asset('storage/' . $this->logo_path) : null;
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
