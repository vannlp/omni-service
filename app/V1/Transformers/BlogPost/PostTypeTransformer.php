<?php


namespace App\V1\Transformers\BlogPost;


use App\PostType;
use League\Fractal\TransformerAbstract;

class PostTypeTransformer extends TransformerAbstract
{
    public function transform(PostType $model)
    {
        return [
            'id'                    => $model->id,
            'code'                  => $model->code,
            'name'                  => $model->name,
            'description'           => $model->description,
            'website_id '           => $model->website_id,
            'post_type_description' => object_get($model, 'post_type_description', null),
            'menu_name'             => object_get($model, 'menu_name', null),
            'all_items'             => object_get($model, 'all_items', null),
            'add_new'               => object_get($model, 'add_new', null),
            'add_new_item'          => object_get($model, 'add_new_item', null),
            'edit_item'             => object_get($model, 'edit_item', null),
            'new_item'              => object_get($model, 'new_item', null),
            'view_item'             => object_get($model, 'view_item', null),
            'search_item'           => object_get($model, 'search_item', null),
            'not_found'             => object_get($model, 'not_found', null),
            'not_found_in_trash'    => object_get($model, 'not_found_in_trash', null),
            'parent'                => object_get($model, 'parent', null),
            'featured_image'        => object_get($model, 'featured_image', null),
            'set_featured_image'    => object_get($model, 'set_featured_image', null),
            'remove_featured_image' => object_get($model, 'remove_featured_image', null),
            'use_featured_image'    => object_get($model, 'use_featured_image', null),
            'archives'              => object_get($model, 'archives', null),
            'insert_into_item'      => object_get($model, 'insert_into_item', null),
            'uploaded_to_this_item' => object_get($model, 'uploaded_to_this_item', null),
            'filter_items_list'     => object_get($model, 'filter_items_list', null),
            'items_list_navigation' => object_get($model, 'items_list_navigation', null),
            'items_list'            => object_get($model, 'items_list', null),
            'show_in_nav'           => $model->show_in_nav,
            'deleted'               => $model->deleted,
            'created_at'            => date('d-m-Y', strtotime($model->created_at)),
            'created_by'            => object_get($model, 'createdBy.profile.full_name',
                $model->created_by),
            'updated_at'            => date('d-m-Y', strtotime($model->updated_at)),
            'updated_by'            => object_get($model, 'updatedBy.profile.full_name',
                $model->updated_by),
        ];
    }
}