<?php


namespace App\V1\Controllers;


use App\Blog;
use App\Post;
use App\PostCategory;
use App\PostCategoryDetail;
use App\PostComment;
use App\PostSearchHistory;
use App\PostTag;
use App\ReportComment;
use App\PostType;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\BlogCategory;
use App\Taxonomy;
use App\TaxonomyPostType;
use App\TM;
use App\V1\Models\BlogCategoryModel;
use App\V1\Models\BlogModel;
use App\V1\Models\PostCategoryModel;
use App\V1\Models\PostCommentModel;
use App\V1\Models\PostModel;
use App\V1\Models\PostSearchHistoryModel;
use App\V1\Models\ReportCommentModel;
use App\V1\Models\PostTypeModel;
use App\V1\Models\TaxonomyModel;
use App\V1\Traits\ControllerTrait;
use App\V1\Traits\FullTextSearch;
use App\V1\Transformers\BlogPost\BlogCategoryTransformer;
use App\V1\Transformers\BlogPost\PostCategoryTransformer;
use App\V1\Transformers\BlogPost\PostCommentTransformer;
use App\V1\Transformers\BlogPost\PostTransformer;
use App\V1\Transformers\BlogPost\BlogTransformer;
use App\V1\Transformers\BlogPost\PostTypeTransformer;
use App\V1\Transformers\BlogPost\TagTransformer;
use App\V1\Transformers\Taxonomy\TaxonomyTransformer;
use App\V1\Transformers\BlogPost\ReportCommentTransformer;
use App\V1\Validators\BlogPost\BlogCreateValidator;
use App\V1\Validators\BlogPost\BlogUpdateValidator;
use App\V1\Validators\BlogCategoryCreateValidator;
use App\V1\Validators\BlogCategoryUpdateValidator;
use App\V1\Validators\BlogPost\PostCategoryUpsertValidator;
use App\V1\Validators\BlogPost\PostCommentCreateValidator;
use App\V1\Validators\BlogPost\PostCommentUpdateValidator;
use App\V1\Validators\BlogPost\PostCreateValidator;
use App\V1\Validators\BlogPost\PostTypeCreateValidator;
use App\V1\Validators\BlogPost\PostTypeUpdateValidator;
use App\V1\Validators\BlogPost\PostUpdateValidator;
use App\V1\Validators\BlogPost\TaxonomyCreateValidator;
use App\V1\Validators\BlogPost\TaxonomyUpdateValidator;
use App\V1\Validators\BlogPost\ReportCommentCreateValidator;
use App\V1\Validators\BlogPost\ReportCommentUpdateValidator;
use App\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogPostController extends BaseController
{
    use ControllerTrait;
    use FullTextSearch;

    /**
     * @var BlogModel
     */
    protected $blogModel;
    /**
     * @var BlogCategoryModel
     */
    protected $blogCategoryModel;
    /**
     * @var PostModel
     */
    protected $postModel;
    /**
     * @var PostCommentModel
     */
    protected $postCommentModel;
    /**
     * @var PostSearchHistoryModel
     */
    protected $postSearchHistoryModel;
    /**
     * @var TaxonomyModel
     */
    protected $taxonomyModel;
    /**
     * @var PostTypeModel
     */
    protected $postTypeModel;

    /**
     * @var ReportCommentModel
     */
    protected $reportCommentModel;

    /**
     * @var PostCategoryModel
     */
    protected $postCategoryModel;

    /**
     * BlogPostController constructor.
     */
    public function __construct()
    {
        $this->blogModel              = new BlogModel();
        $this->blogCategoryModel      = new BlogCategoryModel();
        $this->postModel              = new PostModel();
        $this->postSearchHistoryModel = new PostSearchHistoryModel();
        $this->taxonomyModel          = new TaxonomyModel();
        $this->postTypeModel          = new PostTypeModel();
        $this->postCommentModel       = new PostCommentModel();
        $this->reportCommentModel     = new ReportCommentModel();
        $this->postCategoryModel      = new PostCategoryModel();
    }

    /**
     * @param Request $request
     * @param BlogTransformer $blogTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function searchBlog(Request $request, BlogTransformer $blogTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $limit               = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => "%{$input['name']}%"];
        }
        if (!empty($input['keyword'])) {
            $input['keyword'] = ['like' => "%{$input['keyword']}%"];
        }
        if (isset($input['is_active'])) {
            $input['is_active'] = ['=' => "{$input['is_active']}"];
        }
        if (isset($input['website_id'])) {
            $input['website_id'] = ['=' => "{$input['website_id']}"];
        }
        $result = $this->blogModel->search($input, [], $limit);
        return $this->response->paginator($result, $blogTransformer);
    }

    /**
     * @param $id
     * @param Request $request
     * @param BlogTransformer $blogTransformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detailBlog($id, Request $request, BlogTransformer $blogTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $result              = Blog::model()->where([
            'website_id' => $input['website_id'],
            'id'         => $id
        ])->first();
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $blogTransformer);
    }

    /**
     * @param Request $request
     * @param BlogCreateValidator $blogCreateValidator
     * @return array|void
     */
    public function createBlog(Request $request, BlogCreateValidator $blogCreateValidator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $blogCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->blogModel->create($input);
            Log::create($this->blogModel->getTable(), "#ID:" . $result->id . "-" . $result->name);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R001", $result->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param BlogUpdateValidator $blogUpdateValidator
     * @return array|void
     */
    public function updateBlog($id, Request $request, BlogUpdateValidator $blogUpdateValidator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $input['id']         = $id;
        $blogUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->blogModel->update($input);
            Log::update($this->blogModel->getTable(), "#ID:" . $result->id . "-" . $result->name);
            DB::commit();
        } catch
        (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", $result->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function deleteBlog($id)
    {
        try {
            $result = Blog::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get('V003', "ID #$id"));
            }
            //Check fk
            $this->checkForeignTable($id, config("constants.FT.{$this->blogModel->getTable()}", []));
            DB::beginTransaction();
            //Delete Blog
            $result->delete();
            Log::delete($this->blogModel->getTable(), "#ID:" . $result->id . "-" . $result->name);
            DB::commit();
        } catch
        (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R003", $result->name)];
    }

    /**
     * @param Request $request
     * @param BlogCategoryTransformer $blogCategoryTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function searchBlogCategory(Request $request, BlogCategoryTransformer $blogCategoryTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $limit               = array_get($input, 'limit', 20);
        $result              = $this->blogCategoryModel->search($input, [], $limit);
        return $this->response->paginator($result, $blogCategoryTransformer);
    }

    /**
     * @param $id
     * @param BlogCategoryTransformer $blogCategoryTransformer
     * @return array[]|\Dingo\Api\Http\Response
     */
    public function detailBlogCategory($id, Request $request, BlogCategoryTransformer $blogCategoryTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $blogCategory        = BlogCategory::model()->where([
            'website_id' => $input['website_id'],
            'id'         => $id,
        ])->first();
        if (empty($blogCategory)) {
            return ['data' => []];
        }
        return $this->response->item($blogCategory, $blogCategoryTransformer);
    }

    /**
     * @param Request $request
     * @param BlogCategoryCreateValidator $blogCategoryCreateValidator
     * @return array|void
     */
    public function createBlogCategory(Request $request, BlogCategoryCreateValidator $blogCategoryCreateValidator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $blogCategoryCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $blogCategory = $this->blogCategoryModel->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R001", $blogCategory->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param BlogCategoryUpdateValidator $blogCategoryUpdateValidator
     * @return array|void
     */
    public function updateBlogCategory($id, Request $request, BlogCategoryUpdateValidator $blogCategoryUpdateValidator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $input['id']         = $id;
        $blogCategoryUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $blogCategory = $this->blogCategoryModel->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", $blogCategory->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function deleteBlogCategory($id)
    {
        try {
            DB::beginTransaction();
            $blogCategory = BlogCategory::find($id);
            if (empty($blogCategory)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $blogCategory->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("blog_categories.delete-success", $blogCategory->name)];
    }

    /**
     * @param Request $request
     * @param PostTransformer $postTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function searchBlogPost(Request $request, PostTransformer $postTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $limit               = array_get($input, 'limit', 20);
//        // Save Search Histories
//        if (!empty($input)) {
//            $postSearchHistory = new PostSearchHistoryModel();
//            $searchBy          = !empty($input) ? implode(",", array_keys($input)) : null;
//            $keyword           = !empty($input) ? implode(",", array_values($input)) : null;
//            //Create PostSearchHistory
//            DB::beginTransaction();
//            if (!empty($input['website_id'])) {
//                $param = [
//                        'search_by'  => $searchBy,
//                        'keyword'    => $keyword,
//                        'website_id' => $input['website_id'],
//                ];
//                $postSearchHistory->create($param);
//            }
//
//            DB::commit();
//        }
        $result = $this->postModel->search($input, [], $limit);
        return $this->response->paginator($result, $postTransformer);
    }

    /**
     * Search tag post
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function searchTagPosts(Request $request)
    {
        $input['website_id'] = $request->header('signature');
        $tags                = PostTag::where(function ($query) use ($request) {
            if (!empty($request->input('name'))) {
                $query->where('name', 'LIKE', '%' . $request->input('name') . '%');
            }
        })->where('website_id', $input['website_id'])->paginate($request->input('limit', 20));
        return $this->response->paginator($tags, new TagTransformer());
    }

    /**
     * @param $id
     * @param PostTransformer $postTransformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detailBlogPost($id, Request $request, PostTransformer $postTransformer)
    {
        try {
            $input     = $request->all();
            $result    = Post::find($id);
            $createdBy = $result->created_by;
            $updatedBy = $result->updated_by;
            if (empty($result)) {
                return ['data' => null];
            }
            //Check password
            $passwordPost = $result->password;
            if (!empty($passwordPost)) {
                if (empty($input['password'])) {
                    return $this->response->errorBadRequest(Message::get("V001", Message::get("password")));
                }
                if (!password_verify($input['password'], $passwordPost)) {
                    return $this->response->errorBadRequest(Message::get("V002", Message::get("password")));
                }
            }
            //Update View
            $result->view       += 1;
            $result->created_by = $createdBy;
            $result->updated_by = $updatedBy;
            $result->save();

        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($result, $postTransformer);
    }

    public function createBlogPost(Request $request, PostCreateValidator $postCreateValidator)
    {
        $input               = $request->all();
//        $input['website_id'] = $request->header('signature');
        $postCreateValidator->validate($input);
        if (!empty($input['slug'])) {
            $checkSlug = Post::model()->where('slug', $input['slug'])->first();
            if (!empty($checkSlug)) {
                $input['slug'] = $input['slug'] . rand(0, 999);
            }
        }
        try {
            DB::beginTransaction();
            $result = $this->postModel->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("posts.create-success", $result->title)];
    }

    public function updateBlogPost($id, Request $request, PostUpdateValidator $postUpdateValidator)
    {
        $input               = $request->all();
//        $input['website_id'] = $request->header('signature');
        $input['id']         = $id;
        $postUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->postModel->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("posts.update-success", $result->title)];
    }

    public function deleteBlogPost($id)
    {
        try {
            DB::beginTransaction();
            $result = Post::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            //Delete All PostTaxonomy
            $result->getPostTaxonomy->each->delete();
            //Delete Post
            $result->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("posts.delete-success", $result->title)];
    }

    public function approveBlogPost($id)
    {
        try {
            $result = Post::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            //Update View
            DB::beginTransaction();
            $result->approved_by = TM::getCurrentUserId();
            $result->save();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("posts.approve-success", $result->title)];
    }

    public function searchBlogPostStatistic(Request $request, PostTransformer $postTransformer)
    {
        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = $this->postModel->searchBlogPostStatistic($input, [], $limit);
        return $this->response->paginator($result, $postTransformer);
    }

    public function topSearch(Request $request, PostTransformer $postTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $limit               = array_get($input, 'limit', 20);
        $postSearchHistory   = PostSearchHistory::model()
            ->select('keyword', DB::raw('COUNT(keyword) as keyword_count'))
            ->where('website_id', $input['website_id'])
            ->groupBy('keyword')
            ->orderBy('keyword_count', 'desc')
            ->limit($limit)->get();
        return response()->json(['data' => $postSearchHistory]);
    }

    ############ Taxonomy ################

    /**
     * @param Request $request
     * @param TaxonomyTransformer $taxonomyTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function searchTaxonomy(Request $request, TaxonomyTransformer $taxonomyTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $limit               = array_get($input, 'limit', 20);
        $result              = $this->taxonomyModel->search($input, [], $limit);
        if (empty($result)) {
            return ['data' => []];
        }
        return $this->response->paginator($result, $taxonomyTransformer);
    }

    /**
     * @param $id
     * @param TaxonomyTransformer $taxonomyTransformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detailTaxonomy($id, TaxonomyTransformer $taxonomyTransformer)
    {
        $result = Taxonomy::model()->where([
            'id' => $id,
        ])->first();
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $taxonomyTransformer);
    }

    /**
     * @param Request $request
     * @param TaxonomyCreateValidator $validator
     * @return array|void
     */
    public function createTaxonomy(Request $request, TaxonomyCreateValidator $validator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $validator->validate($input);
        try {
            DB::beginTransaction();
            if (!empty($input['name'])) {
                $isCheck = Taxonomy::model()
                    ->where('name', $input['name'])
                    ->where('website_id', $input['website_id'])
                    ->exists();
                if ($isCheck) {
                    $this->response->errorBadRequest(Message::get("V007", "[{$input['name']}]"));
                }
            }
            $input['slug'] = Str::slug($input['name']);

            if (isset($input['post_type_ids'])) {
                $checkPostType = PostType::model()->pluck('id', 'id')->toArray();
                foreach ($input['post_type_ids'] as $item) {
                    if (empty($checkPostType[$item])) {
                        $this->response->errorBadRequest(Message::get('V003', 'ID PostType #' . $item));
                    }
                }
                $arrayPostIds           = $input['post_type_ids'];
                $input['post_type_ids'] = implode(",", $input['post_type_ids']);
            }
            $result = $this->taxonomyModel->create($input);
            if (isset($arrayPostIds)) {
                foreach ($arrayPostIds as $item) {
                    $param = [
                        'taxonomy_id'  => $result->id,
                        'post_type_id' => $item,
                        'created_at'   => date('Y-m-d', time()),
                        'created_by'   => TM::getCurrentUserId()
                    ];
                    TaxonomyPostType::insert($param);
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R001", $result->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param TaxonomyUpdateValidator $validator
     * @return array|void
     */
    public function updateTaxonomy($id, Request $request, TaxonomyUpdateValidator $validator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $input['id']         = $id;
        $validator->validate($input);
        try {
            DB::beginTransaction();
            if (!empty($input['name'])) {
                $taxonomy = Taxonomy::model()
                    ->where('name', $input['name'])
                    ->where('website_id', $input['website_id'])
                    ->first();
                if (!empty($taxonomy) && $taxonomy->id != $id) {
                    $this->response->errorBadRequest(Message::get("V007", "[{$input['name']}]"));
                }
            }
            $input['slug'] = Str::slug($input['name']);
            $result        = $this->taxonomyModel->update($input);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", $result->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function deleteTaxonomy($id)
    {
        try {
            DB::beginTransaction();
            $result = Taxonomy::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get('V003', 'ID #' . $id));
            }
            $result->delete();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R003", $result->name)];
    }

    ############ Post Type ################

    /**
     * @param Request $request
     * @param PostTypeTransformer $transformer
     * @return \Dingo\Api\Http\Response
     */
    public function searchPostType(Request $request, PostTypeTransformer $transformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $limit               = array_get($input, 'limit', 20);
        $result              = $this->postTypeModel->search($input, [], $limit);
        if (empty($result)) {
            return ['data' => []];
        }
        return $this->response->paginator($result, $transformer);
    }

    /**
     * @param $id
     * @param PostTypeTransformer $transformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detailPostType($id, PostTypeTransformer $transformer)
    {
        $result = PostType::find($id);
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $transformer);
    }

    /**
     * @param $post_type_code
     * @param PostTypeTransformer $transformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function searchDetailCodePostType($post_type_code, PostTypeTransformer $transformer)
    {
        $result = PostType::model()->where('code', $post_type_code)->first();
        return $this->response->item($result, $transformer);
    }


    /**
     * @param Request $request
     * @param PostTypeCreateValidator $validator
     * @return array|void
     */
    public function createPostType(Request $request, PostTypeCreateValidator $validator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->postTypeModel->create($input);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R001", $result->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param PostTypeUpdateValidator $validator
     * @return array|void
     */
    public function updatePostType($id, Request $request, PostTypeUpdateValidator $validator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $input['id']         = $id;
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->postTypeModel->update($input);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", $result->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function deletePostType($id)
    {
        try {
            DB::beginTransaction();
            $result = PostType::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get('V003', 'ID #' . $id));
            }
            $result->delete();
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R003", $result->name)];
    }

    /**
     * @param Request $request
     * @param PostCommentTransformer $postCommentTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function searchPostComment(Request $request, PostCommentTransformer $postCommentTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $limit               = array_get($input, 'limit', 20);
        if (isset($input['website_id'])) {
            $input['website_id'] = ['=' => "{$input['website_id']}"];
        }
        $result = $this->postCommentModel->search($input, [], $limit);
        return $this->response->paginator($result, $postCommentTransformer);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse|void
     */
    public function searchPostCommentLike($id)
    {
        try {
            DB::beginTransaction();
            $postComment  = PostComment::find($id);
            $postComments = $postComment->like;
            if (!empty($postComments)) {
                $like       = explode(",", $postComments);
                $postId     = TM::getCurrentUserId();
                $searchLike = array_flip($like);
                if (isset($searchLike[$postId])) {
                    unset($searchLike[$postId]);
                    $postComment->count_like--;
                    $postComment->like = implode(",", array_flip($searchLike));
                    $postComment->save();
                } else {
                    $postComment->count_like++;
                    $like[]            = $postId;
                    $postComment->like = implode(",", $like);
                    $postComment->save();
                }
            }
            if (empty($postComments)) {
                $postComment->like = TM::getCurrentUserId();
                $postComment->count_like++;
                $postComment->save();
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return response()->json(['data' => $postComment->count_like]);
    }

    /**
     * @param $id
     * @param Request $request
     * @param PostCommentTransformer $postCommentTransformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detailPostComment($id, Request $request, PostCommentTransformer $postCommentTransformer)
    {
        $result = PostComment::find($id);
        if (empty($result)) {
            return ['data' => null];
        }
        Log::view($this->postCommentModel->getTable(), "#ID:" . $result->id);
        return $this->response->item($result, $postCommentTransformer);
    }

    /**
     * @param Request $request
     * @param PostCommentCreateValidator $postCommentCreateValidator
     * @param PostCommentTransformer $postCommentTransformer
     * @return array|void
     */
    public function createPostComment(Request $request, PostCommentCreateValidator $postCommentCreateValidator, PostCommentTransformer $postCommentTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $postCommentCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result   = $this->postCommentModel->upsert($input);
            $postName = Post::find($result->post_id);
            Log::create($this->postCommentModel->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("post_comments.create-success", $postName->title)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param PostCommentUpdateValidator $postCommentUpdateValidator
     * @param PostCommentTransformer $postCommentTransformer
     * @return array|void
     */
    public function updatePostComment($id, Request $request, PostCommentUpdateValidator $postCommentUpdateValidator, PostCommentTransformer $postCommentTransformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $input['id']         = $id;
        $postCommentUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result   = $this->postCommentModel->upsert($input);
            $postName = Post::find($result->post_id);
            Log::update($this->postCommentModel->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch
        (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("post_comments.update-success", $result->id, $postName->title)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function deletePostComment($id)
    {
        try {
            $result = PostComment::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get('V003', "ID #$id"));
            }
            DB::beginTransaction();
            //Delete Post Comment
            $result->delete();
            Log::delete($this->postCommentModel->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch
        (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("post_comments.delete-success", $result->id)];
    }

    /**
     * @param Request $request
     * @param ReportCommentTransformer $reportCommentTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function searchReportComment(Request $request, ReportCommentTransformer $reportCommentTransformer)
    {
        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = $this->reportCommentModel->search($input, [], $limit);
        Log::view($this->reportCommentModel->getTable());
        return $this->response->paginator($result, $reportCommentTransformer);
    }

    /**
     * @param $id
     * @param Request $request
     * @param ReportCommentTransformer $reportCommentTransformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detailReportComment($id, Request $request, ReportCommentTransformer $reportCommentTransformer)
    {
        $result = ReportComment::find($id);
        if (empty($result)) {
            return ['data' => null];
        }
        Log::view($this->reportCommentModel->getTable(), "#ID:" . $result->id);
        return $this->response->item($result, $reportCommentTransformer);
    }

    /**
     * @param Request $request
     * @param ReportCommentCreateValidator $reportCommentCreateValidator
     * @return array|void
     */
    public function createReportComment(Request $request, ReportCommentCreateValidator $reportCommentCreateValidator)
    {
        $input = $request->all();
        $reportCommentCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->reportCommentModel->upsert($input);
            Log::create($this->reportCommentModel->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get('R001', $result->id)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param ReportCommentUpdateValidator $reportCommentUpdateValidator
     * @return array|void
     */
    public function updateReportComment($id, Request $request, ReportCommentUpdateValidator $reportCommentUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $reportCommentUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->reportCommentModel->upsert($input);
            Log::update($this->reportCommentModel->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch
        (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get('R002', $result->id)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function deleteReportComment($id)
    {
        try {
            $result = ReportComment::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get('V003', "ID #$id"));
            }
            DB::beginTransaction();
            //Delete Blog
            $result->delete();
            Log::delete($this->reportCommentModel->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch
        (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get('R003', $result->id)];
    }

    /**
     * @param Request $request
     * @param PostCategoryTransformer $transformer
     * @return \Dingo\Api\Http\Response
     */
    public function searchPostCategory(Request $request, PostCategoryTransformer $transformer)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $limit               = Arr::get($input, 'limit', 20);
        $result              = $this->postCategoryModel->search($input, ['thumbnail', 'parent'], $limit);
        Log::view($this->postCategoryModel->getTable());
        return $this->response->paginator($result, $transformer);
    }

    /**
     * @param $id
     * @param PostCategoryTransformer $transformer
     * @return \Dingo\Api\Http\Response
     */
    public function detailPostCategory($id, PostCategoryTransformer $transformer)
    {
        $result = PostCategory::findOrFail($id);
        Log::view($this->postCategoryModel->getTable(), $result->name);
        return $this->response->item($result, $transformer);
    }

    /**
     * @param Request $request
     * @param PostCategoryUpsertValidator $validator
     * @return array|void
     */
    public function createPostCategory(Request $request, PostCategoryUpsertValidator $validator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $validator->validate($input);
        try {
            DB::beginTransaction();
            if (empty($input['slug'])) {
                $input['slug'] = $this->convert_vi_to_en_to_slug($input['name']);
            }
            $result = $this->postCategoryModel->create($input);
            Log::create($this->postCategoryModel->getTable(), $result->name);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get('R001', $result->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param PostCategoryUpsertValidator $validator
     * @return array|void
     */
    public function updatePostCategory($id, Request $request, PostCategoryUpsertValidator $validator)
    {
        $input               = $request->all();
        $input['website_id'] = $request->header('signature');
        $input['id']         = $id;
        $validator->validate($input);
        try {
            DB::beginTransaction();
            if (empty($input['slug'])) {
                $input['slug'] = $this->convert_vi_to_en_to_slug($input['name']);
            }
            $result = $this->postCategoryModel->update($input);
            Log::update($this->postCategoryModel->getTable(), $result->name);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get('R002', $result->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function deletePostCategory($id)
    {
        $result    = PostCategory::find($id);
        $checkPost = PostCategoryDetail::model()->where('post_category_id', $result->id)->count();
        if (!empty($checkPost)) {
            return $this->response->errorBadRequest("Danh mục đang chứa $result->name bài viết. Nên bạn không thể xóa danh mục này");
        }
        try {
            DB::beginTransaction();
            $result->delete();
            $result->save();
            Log::delete($this->postCategoryModel->getTable(), $result->name);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get('R003', $result->name)];
    }


    ########################################## Client ###########################################
//    public function getClientPost(Request $request, PostTransformer $transformer)
//    {
//        $input = $request->all();
//        $limit = array_get($input, 'limit', 20);
//        $store_id = null;
//        if (TM::getCurrentUserId()) {
//            $store_id = TM::getCurrentStoreId();
//        } else {
//            $headers = $request->headers->all();
//            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
//                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
//                if ($store_token_input && strlen($store_token_input) == 64) {
//                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
//                    if (!$store) {
//                        return ['data' => []];
//                    }
//                    $store_id = $store->id;
//                }
//            }
//        }
//
//        $store = Store::find($store_id);
//        $company_id = Arr::get($store, 'company_id', null);
//        $input['company_id'] = ['=' => $company_id];
//        $result = $this->postModel->search($input, [], $limit);
//        return $this->response->paginator($result, $transformer);
//    }
    public function getRelatedPost($id, Request $request, PostTransformer $postTransformer)
    {
        $input        = $request->all();
        $limit        = Arr::get($input, 'limit', '20');
        $posts        = Post::findOrFail($id);
        $tags         = Arr::get($posts, 'tags', null);
        $category_ids = Arr::get($posts, 'post_categories', null);
        $title_search = Str::ascii($posts->title);
        $result       = Post::with('tags', 'getPostTaxonomy')->where('id', '!=', $id)
            ->where('status', 'published');

        if (!empty($category_ids)) {
            $category_ids = explode(",", $category_ids);
            $result->where(function ($q) use ($category_ids) {
                foreach ($category_ids as $category_id) {
                    $q->orWhere(DB::raw("CONCAT(',',post_categories,',')"), 'like', "%,$category_id,%");
                }
            });
        }

        if (!empty($tags)) {
            $tags = $tags->pluck('name')->toArray();
            $result->whereHas('tags', function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhere(DB::raw('name', 'like', "%{$tag}%"));
                }
            });
        }

        $this->scopeSearchOrWhere($result, 'title_search', $title_search);

        $result = $result->paginate($limit);
        return $this->response->paginator($result, $postTransformer);
    }

    //CLien
    public function detailBlogPostBySlug($post_by_slug, Request $request, PostTransformer $postTransformer)
    {
        try {
            $input     = $request->all();
            $result    = Post::model()->where('slug', $post_by_slug)->first();
            $createdBy = $result->created_by;
            $updatedBy = $result->updated_by;
            if (empty($result)) {
                return ['data' => null];
            }
            //Check password
            $passwordPost = $result->password;
            if (!empty($passwordPost)) {
                if (empty($input['password'])) {
                    return $this->response->errorBadRequest(Message::get("V001", Message::get("password")));
                }
                if (!password_verify($input['password'], $passwordPost)) {
                    return $this->response->errorBadRequest(Message::get("V002", Message::get("password")));
                }
            }
            //Update View
            $result->view       += 1;
            $result->created_by = $createdBy;
            $result->updated_by = $updatedBy;
            $result->save();

        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($result, $postTransformer);
    }
}
