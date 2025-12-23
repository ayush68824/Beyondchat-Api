<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'link',
        'date',
        'source_url',
        'full_content',
        'is_updated',
        'original_article_id',
        'reference_articles',
        'updated_at'
    ];

    protected $casts = [
        'is_updated' => 'boolean',
        'reference_articles' => 'array',
        'date' => 'datetime',
    ];

    public function originalArticle()
    {
        return $this->belongsTo(Article::class, 'original_article_id');
    }

    public function updatedVersions()
    {
        return $this->hasMany(Article::class, 'original_article_id');
    }
}
