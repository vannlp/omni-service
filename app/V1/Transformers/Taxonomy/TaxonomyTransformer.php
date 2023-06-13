<?php


namespace App\V1\Transformers\Taxonomy;


use App\Supports\TM_Error;
use App\Taxonomy;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class TaxonomyTransformer extends TransformerAbstract
{
    public function transform(Taxonomy $taxonomy)
    {
        $folder_path = object_get($taxonomy, 'file.folder.folder_path');
        if (!empty($folder_path)) {
            $folder_path = str_replace("/", ",", $folder_path);
        } else {
            $folder_path = "uploads";
        }
        $folder_path = url('/v0') . "/img/" . $folder_path;
        try {
            $taxonomyPostType = !empty($taxonomy->post_type_ids) ? explode(",", $taxonomy->post_type_ids) : [];
            return [
                'id'            => $taxonomy->id,
                'name'          => $taxonomy->name,
                'slug'          => $taxonomy->slug,
                //                'blog_id'       => $taxonomy->blog_id,
                'thumbnail_url'     => !empty($taxonomy->thumbnail_id) ? !empty($folder_path) ? $folder_path . ',' . Arr::get($taxonomy, "file.file_name") : null : null,
                'thumbnail_id'  => $taxonomy->thumbnail_id,
                //                'blog_name'     => object_get($taxonomy, 'getBlog.name'),
                'parent_id'     => object_get($taxonomy, 'parent_id', null),
                'website_id'    => Arr::get($taxonomy, 'website_id', null),
                'post_type_ids' => $taxonomyPostType,
                'is_active'     => $taxonomy->is_active,
                'deleted'       => $taxonomy->deleted,
                'created_at'    => date('d-m-Y', strtotime($taxonomy->created_at)),
                'created_by'    => object_get($taxonomy, 'createdBy.profile.full_name',
                    $taxonomy->created_by),
                'updated_at'    => date('d-m-Y', strtotime($taxonomy->updated_at)),
                'updated_by'    => object_get($taxonomy, 'updatedBy.profile.full_name',
                    $taxonomy->updated_by),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}