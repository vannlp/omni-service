<?php


namespace App\V1\Models;


use App\PostType;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\Taxonomy;
use App\TaxonomyPostType;
use App\TaxonomyWebsite;
use App\TM;
use App\Website;
use Illuminate\Support\Arr;

class TaxonomyModel extends AbstractModel
{
    public function __construct(Taxonomy $model = null)
    {
        parent::__construct($model);
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['parent_id'])) {
            $query->where('parent_id', $input['parent_id']);
        }
        if (!empty($input['post_type_code'])) {
            $postType = PostType::model()->where('code', "{$input['post_type_code']}")->first();
            if (empty($postType)){
              return []; 
		}
            $postId = $postType->id;
            $listIds = TaxonomyPostType::model()->where('post_type_id', $postId)->pluck('taxonomy_id')->toArray();
            $query->whereIn('id', $listIds);
        }
        if (!empty($input['website_id'])) {
            $query->where('website_id', $input['website_id']);
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
                if (isset($input['post_type_ids'])) {
                    $arrayPostTypeIds = $input['post_type_ids'];
                    $input['post_type_ids'] = implode(",", $input['post_type_ids']);
                }
                $taxonomies = Taxonomy::find($id);
                if (empty($taxonomies)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $taxonomies->name = $input['name'];
                $taxonomies->slug = Arr::get($input, 'slug', null);
                $taxonomies->website_id = $input['website_id'] ?? null;
                $taxonomies->post_type_ids = $input['post_type_ids'] ?? null;
                $taxonomies->parent_id = Arr::get($input, 'parent_id', null);
                $taxonomies->thumbnail_id = Arr::get($input, 'thumbnail_id', null);
                $taxonomies->save();

                if (isset($arrayPostTypeIds)) {
                    $allTaxonomyPostType = TaxonomyPostType::model()->where('taxonomy_id', $id)->get();
                    $allDeletedTaxonomyPostType = array_pluck($allTaxonomyPostType, 'id', 'post_type_id');
                    $allPostType = PostType::model()->pluck('id', 'id')->toArray();
                    foreach ($arrayPostTypeIds as $item) {
                        $taxonomyPostType = new TaxonomyPostType();
                        //Check Website
                        if (empty($allPostType[$item])) {
                            throw new \Exception(Message::get("V003", "ID PostType: #$item"));
                        }

                        if (!empty($allDeletedTaxonomyPostType[$item])) {
                            unset($allDeletedTaxonomyPostType[$item]);
                        } else {
                            $taxonomyPostType->taxonomy_id = $id;
                            $taxonomyPostType->post_type_id = $item;
                            $taxonomyPostType->save();
                        }
                    }
                    if ($allDeletedTaxonomyPostType) {
                        TaxonomyPostType::model()->whereIn('id', array_values($allDeletedTaxonomyPostType))->delete();
                    }
                }
                return $taxonomies;
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
    }
}
