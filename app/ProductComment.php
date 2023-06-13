<?php


namespace App;


class ProductComment extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'product_comments';

    protected $fillable
        = [
            "type",
            "product_id",
            "product_code",
            "product_name",
            "user_id",
            "user_name",
            "content",
            "rate",
            "rate_name",
            "hashtag_rates",
            "parent_id",
            "company_id",
            "store_id",
            "images",
            "like",
            "user_like_id",
            "order_id",
            "order_code",
            "replied",
            "is_active",
            "deleted",
            "created_at",
            "created_by",
            "updated_at",
            "updated_by",
        ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function avatar()
    {
        return $this->hasOne(Profile::class, 'user_id', 'user_id');
    }


    public function parent()
    {
        return $this->belongsTo(ProductComment::class, 'parent_id', 'id');
    }

    public function childs()
    {
        return $this->hasMany(ProductComment::class, 'parent_id', 'id')->where('type', PRODUCT_COMMENT_TYPE_REPLY);
    }

    public function comments()
    {
        return $this->hasMany(ProductComment::class, 'parent_id', 'id')->where('type', PRODUCT_COMMENT_TYPE_COMMENT);
    }

    public function reply_comments()
    {
        return $this->hasMany(ProductComment::class, 'parent_id', 'id')->where('type', PRODUCT_COMMENT_TYPE_REPLY_COMMENT);
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function children()
    {
        return $this->hasMany(ProductComment::class, 'parent_id', 'id');
    }
}