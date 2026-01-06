<?php

namespace App\Modules\Commerce\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogCategory extends Model
{
    use UUID, SoftDeletes;

    protected $table = 'blog_categories';
    protected $guarded = [];

    public function blogs()
    {
        return $this->hasMany(Blog::class, 'blog_category_id');
    }
}
