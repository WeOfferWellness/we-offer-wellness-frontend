<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'product_categories';

    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'description',
        'image_path',
        'image_meta',
        'parent_id',
        'options',
        'meta_1',
        'meta_2',
        'meta_3',
    ];

    protected $casts = [
        'options' => 'array',
        'image_meta' => 'array',
    ];

    protected $appends = ['image_url'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function parent()
    {
        return $this->belongsTo(ProductCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductCategory::class, 'parent_id');
    }

    public function options()
    {
        return $this->hasMany(ProductCategoryOption::class, 'category_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }
}
