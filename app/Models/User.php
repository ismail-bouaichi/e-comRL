<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use MBarlow\Megaphone\HasMegaphone;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasMegaphone;
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
  

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
  protected $fillable = [
        'name',
        'email',
        'password',
        'role_id'
    ];
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole($roleName)
    {
        return $this->role->name === $roleName;
    }
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
        public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'delivery_worker_id');
    }
    public function carts()
    {
        return $this->hasMany(ProductCart::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favoriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites');
    }
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }
     public function scopeSearch($query,$value) {

        $query->where('name','like',"%{$value}%")->orWhere('email','like',"%{$value}%"); 
        
    }
    
    public static function findAvailableDeliveryWorker()
    {
        return self::whereHas('role', function ($query) {
            $query->where('name', 'delivery_worker');
        })
        ->whereDoesntHave('assignedOrders', function ($query) {
            $query->where('status', ['onProgress','paid']);
        })
        ->inRandomOrder()
        ->first();
    }
    public function scopeWithRoleAndDateRange($query, $roleName, $startDate, $endDate)
    {
        return $query->whereHas('role', function ($query) use ($roleName) {
            $query->where('name', $roleName);
        })->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'performed_by');
    }

    public function initiatedStockTransfers()
    {
        return $this->hasMany(StockTransfer::class, 'initiated_by');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'ordered_by');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'created_by');
    }

    public function approvedExpenses()
    {
        return $this->hasMany(Expense::class, 'approved_by');
    }

    public function createdExpenses()
    {
        return $this->hasMany(Expense::class, 'created_by');
    }
}
