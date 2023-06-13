<?php


namespace App\V1\Models;


use App\PostType;
use App\PostTypeWebsite;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TaxonomyPostType;
use App\TM;
use App\Website;
use Illuminate\Support\Arr;

class PostTypeModel extends AbstractModel
{
    public function __construct(PostType $model = null)
    {
        parent::__construct($model);
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
        }
        if (isset($input['show_in_nav'])) {
            $query->where('show_in_nav', "{$input['show_in_nav']}");
        }
        // search
        if (!empty($input['website_id'])) {
            $query->where('website_id', "{$input['website_id']}");
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    public function update(array $input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                $postType = PostType::find($id);
                if (empty($postType)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $postType->code = $input['code'];
                $postType->name = $input['name'];
                $postType->website_id = Arr::get($input, 'website_id', null);
                $postType->description = Arr::get($input, 'description', null);
                $postType->post_type_description = Arr::get($input, 'post_type_description', null);
                $postType->menu_name = Arr::get($input, 'menu_name', null);
                $postType->all_items = Arr::get($input, 'all_items', null);
                $postType->add_new = Arr::get($input, 'add_new', null);
                $postType->add_new_item = Arr::get($input, 'add_new_item', null);
                $postType->edit_item = Arr::get($input, 'edit_item', null);
                $postType->new_item = Arr::get($input, 'new_item', null);
                $postType->view_item = Arr::get($input, 'view_item', null);
                $postType->search_item = Arr::get($input, 'search_item', null);
                $postType->not_found = Arr::get($input, 'not_found', null);
                $postType->not_found_in_trash = Arr::get($input, 'not_found_in_trash', null);
                $postType->parent = Arr::get($input, 'parent', null);
                $postType->featured_image = Arr::get($input, 'featured_image', null);
                $postType->set_featured_image = Arr::get($input, 'set_featured_image', null);
                $postType->remove_featured_image = Arr::get($input, 'remove_featured_image', null);
                $postType->use_featured_image = Arr::get($input, 'use_featured_image', null);
                $postType->archives = Arr::get($input, 'archives', null);
                $postType->insert_into_item = Arr::get($input, 'insert_into_item', null);
                $postType->uploaded_to_this_item = Arr::get($input, 'uploaded_to_this_item', null);
                $postType->filter_items_list = Arr::get($input, 'filter_items_list', null);
                $postType->items_list_navigation = Arr::get($input, 'items_list_navigation', null);
                $postType->items_list = Arr::get($input, 'items_list', null);
                $postType->show_in_nav = Arr::get($input, 'show_in_nav', null);
                $postType->save();
                return $postType;
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
    }
}