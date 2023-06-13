<?php


namespace App;

/**
 * Class Category
 * @package App
 */
class Category extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'categories';
    protected $fillable
                     = [
            "id",
            "name",
            "code",
            "slug",
            "description",
            "type",
            "sort_order",
            "order",
            "image_id",
            "area_id",
            "store_id",
            "is_active",
            "parent_id",
            "sync_zalo",
            "property_ids",
            "category_publish",
            "product_publish",
            "data",
            "gift_item",
            "property",
            "meta_title",
            "meta_description",
            "meta_robot",
            "meta_keyword",
            "created_at",
            "created_by",
            "updated_at",
            "updated_by",
        ];

    public function file()
    {
        return $this->hasOne(__NAMESPACE__ . '\File', 'id', 'image_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'area_id', 'id')
            ->withTrashed();
    }
    public function properties()
    {
        return $this->belongsToMany(Property::class, 'category_properties');
    }

    public function CategoryStoreDetails()
    {
        return $this->hasMany(__NAMESPACE__ . '\CategoryStore', 'category_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id', 'id');
    }

    public function stores()
    {
        return $this->hasMany(CategoryStore::class, 'category_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function childrenPublish()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where([
                'category_publish' => 1,
                'product_publish'  => 1
            ]);
    }

    public function grandChildren()
    {
        return $this->childrenPublish()->with('grandChildren')->where([
            'category_publish' => 1,
            'product_publish'  => 1
        ]);
    }

    public function banner()
    {
        return $this->belongsToMany(Banner::class, 'banner_has_categories');
    }

    /**
     * Get ids of product
     *
     * @param $store_id
     * @param array $area_ids
     * @return mixed
     */
    public function getIdsOfProduct($store_id, $area_ids = [])
    {
        return $this->where(['category_publish' => 1, 'product_publish' => 1])
            ->whereHas('stores', function ($query) use ($store_id) {
                $query->where('store_id', $store_id);
            })
            ->where(function ($query) use ($area_ids) {
                if (!empty($area_ids)) {
                    $query->whereIn('area_id', $area_ids);
                }
            })
            ->get(['id'])->pluck('id')->toArray();
    }

    public function getClientIdsOfProduct($store_id, $area_ids = [])
    {
        return $this->where(['category_publish' => 1, 'product_publish' => 1])
            ->whereHas('stores', function ($query) use ($store_id) {
                $query->where('store_id', $store_id);
            })
            ->get(['id'])->pluck('id')->toArray();
    }

    public function categoryProperties()
    {
        return $this->belongsToMany(Property::class, 'category_properties');
    }
}
