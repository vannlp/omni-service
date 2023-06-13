<?php


namespace App\V1\Transformers\BlogPost;


use App\Blog;
use App\BlogCategory;
use App\Post;
use App\Taxonomy;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class PostTransformer extends TransformerAbstract
{
    public function transform(Post $post)
    {
        return [
            'id'               => $post->id,
            'title'            => $post->title,
            'slug'             => $post->slug,
            'short_desc'       => $post->short_desc,
            'content'          => $post->content,
            'thumbnail_url'    => $post->thumbnail_url,
            'website_id'       => Arr::get($post, 'website_id', null),
            'taxonomy_ids'     => !empty($post->taxonomy_ids) ? explode(",", $post->taxonomy_ids) : [],
            'post_categories'  => !empty($post->post_categories) ? explode(",", $post->post_categories) : [],
            'taxonomies'       => !empty($post->taxonomy_ids) ? $this->getTaxonomy($post->taxonomy_ids) : null,
            'status'           => $post->status,
            'status_name'      => BLOG_POST_STATUS_NAME[$post->status] ?? null,
            'publish_date'     => $post->publish_date,
            'author'           => $post->author,
            'is_approved'      => !empty($post->approved_by) ? true : false,
            'approved_by'      => $post->approved_by,
            'approved_by_name' => object_get($post, 'approvedBy.profile.full_name', $post->approved_by),
            'view'             => $post->view,
            'is_featured'      => $post->is_featured,
            'keyword'          => $post->keyword,
            'post_type'        => $post->post_type,
            //            'password'         => $post->password,
            'created_at'       => date('d-m-Y', strtotime($post->created_at)),
            'created_by'       => object_get($post, 'createdBy.profile.full_name', $post->created_by),
            'updated_at'       => date('d-m-Y', strtotime($post->updated_at)),
            'updated_by'       => object_get($post, 'updatedBy.profile.full_name', $post->updated_by),
        ];
    }

    private function getTaxonomy($ids)
    {
        $ids = explode(",", $ids);
        if (empty($ids)) {
            return [];
        }
        $data           = [];
        $blogTaxonomies = Taxonomy::model()->whereIn('id', $ids)->get();
        foreach ($blogTaxonomies as $blogTaxonomy) {
            $data[] = [
                'category_id'   => $blogTaxonomy->id,
                'category_name' => $blogTaxonomy->name,
                'category_slug' => $blogTaxonomy->slug,
            ];
        }
        return $data;
    }
}