<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = ['name', 'parent_id'];

    // Hide columns deleted_at, created_at, updated_at
    protected $hidden = ['created_at', 'updated_at', 'deleted_at'];

    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Defines a recursive relationship to get all descendants (children, grandchildren, etc.).
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }
}