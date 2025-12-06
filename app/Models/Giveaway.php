<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Giveaway extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    // ...

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->nonQueued()
            ->performOnCollections('images');
    }

    protected $fillable = [
        'title',
        'description',
        'images',
        'closes_at',
        'price',
        'ticketsTotal',
        'ticketsTotalHidden',
        'ticketsPerUser',
        'alternative_prize',
    ];

    protected $hidden = [
        'ticketsTotalHidden',
    ];

    protected $appends = ['ticketsSold', 'ticketsTotalDisplay'];


    protected function casts () {
         return [
            'closes_at' => 'datetime',
            'price' => 'decimal:2',
            'alternative_prize' => 'decimal:2',
        ];
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        // Override the images attribute with proper URLs for API responses
        $array['images'] = $this->getMedia('images')->map(function ($media) {
            $url = $media->getUrl();
            $path = parse_url($url, PHP_URL_PATH);
            $path = preg_replace('#^/storage/#', '', $path);
            return $path;
        })->toArray();
        
        return $array;
    }

    public function winningOrders()
    {
        return $this->belongsToMany(Order::class)
                       ->withPivot('numbers', 'is_winner', 'winning_ticket')
                       ->with('user') // include user relation on Order
                       ->wherePivot('is_winner', true);
    }


    public function getTicketsSoldAttribute()
    {
        // Only count tickets from completed orders
        $orders = $this->orders()
            ->where('orders.status', 'completed')
            ->get();

        $totalAssigned = 0;

        foreach ($orders as $order) {
            $numbers = $order->pivot->numbers;

            if ($numbers) {
                $decoded = json_decode($numbers, true);

                if (is_array($decoded)) {
                    $totalAssigned += count($decoded);
                }
            }
        }

        return $totalAssigned;
    }

public function getTicketsTotalDisplayAttribute()
{
    if ($this->ticketsTotalHidden) {
        return 0;
    }

    return $this->attributes['ticketsTotal'] ?? null;
}

   public static function closestToClosing(int $limit = 6): Collection
   {
       return self::query()
           ->where('closes_at', '>=', now())
           ->orderBy('closes_at', 'asc') // soonest first
           ->limit($limit)
           ->get();
   }

    public static function justLaunched(int $limit = 6): Collection
      {
          return self::query()
              ->where('closes_at', '>=', now())
              ->orderBy('created_at', 'asc')
              ->limit($limit)
              ->get();
      }

    public function orders()
      {
          return $this->belongsToMany(Order::class)
                            ->withPivot('numbers')
                            ->withTimestamps();
      }

    public function transactionSheets()
    {
        return $this->hasMany(TransactionSheet::class);
    }
}
