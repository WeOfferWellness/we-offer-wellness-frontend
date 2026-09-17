<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductSubcategory extends Model
{
    use HasFactory;

    protected $table = 'product_subcategories';

    protected $fillable = ['category_id', 'name', 'slug', 'tagline', 'description', 'image_path', 'image_meta', 'options', 'meta_1', 'meta_2', 'meta_3', 'status', 'requested_by_user_id', 'reviewed_by_user_id', 'review_notes', 'reviewed_at', 'advanced_meta_1', 'advanced_meta_2', 'advanced_meta_3'];

    protected $casts = [
        'image_meta' => 'array',
        'options' => 'array',
        'advanced_meta_1' => 'array',
        'advanced_meta_2' => 'array',
        'advanced_meta_3' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected $appends = ['image_url'];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'subcategory_id');
    }

    public function offerings()
    {
        return $this->hasMany(OfferingV3::class, 'subcategory_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
