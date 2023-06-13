<?php


namespace App;


use Illuminate\Database\Eloquent\Model;

class PostTag extends Model
{
    /**
     * @var string
     */
    protected $table = 'post_tags';

    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'website_id'
    ];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_has_tags');
    }
}