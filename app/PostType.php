<?php


namespace App;


class PostType extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'post_types';
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'code',
        'name',
        'website_id',
        'description',
        'post_type_description',
        'menu_name',
        'all_items',
        'add_new',
        'add_new_item',
        'edit_item',
        'new_item',
        'view_item',
        'search_item',
        'not_found',
        'not_found_in_trash',
        'parent',
        'featured_image',
        'set_featured_image',
        'remove_featured_image',
        'use_featured_image',
        'archives',
        'insert_into_item',
        'uploaded_to_this_item',
        'filter_items_list',
        'items_list_navigation',
        'items_list',
        'show_in_nav',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    public function createdBy()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function website()
    {
        return $this->hasOne(Website::class, 'id', 'website_id');
    }
}