<?php


namespace App\V1\Controllers;


use App\Product;
use App\ProductComment;
use App\Profile;
use App\Supports\DataUser;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Models\ProductCommentModel;
use App\V1\Transformers\ProductComment\ProductCommentTransformer;
use App\V1\Validators\ProductComment\ProductCommentCreateValidator;
use App\V1\Validators\ProductComment\ProductCommentQuestionAnswerCreateValidator;
use App\V1\Validators\ProductComment\ProductCommentQuestionAnswerUpdateValidator;
use App\V1\Validators\ProductComment\ProductCommentUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportComments;

class ProductCommentController extends BaseController
{
    /**
     * @var ProductCommentModel
     */
    protected $model;

    /**
     * ProductCommentController constructor.
     */
    public function __construct()
    {
        $this->model = new ProductCommentModel();
    }

    /**
     * @param Request $request
     * @param ProductCommentTransformer $productCommentTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, ProductCommentTransformer $productCommentTransformer)
    {
        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = ProductComment::with(['user', 'product', 'parent', 'childs'])->whereNull('parent_id');
        if (isset($input['is_active'])) {
            $result->where('is_active', $input['is_active']);
        }
        if (isset($input['user_id'])) {
            $result->where('user_id', $input['user_id']);
        }
        if (isset($input['content'])) {
            $result->where('content', 'like', "%{$input['content']}%");
        }
        if (!empty($input['product_id'])) {
            $result->where('product_id', "{$input['product_id']}");
        }
        if (!empty($input['order_code'])) {
            $result->where('order_code', "{$input['order_code']}");
        }
        if (!empty($input['order_id'])) {
            $result->where('order_id', "{$input['order_id']}");
        }
        if (!empty($input['product_code'])) {
            $result->where('product_code', "{$input['product_code']}");
        }
        if (!empty($input['is_reply'])) {
            $result->where('replied', "{$input['is_reply']}");
        }
        if (!empty($input['from']) && !empty($input['to'])) {
            $from = date('Y-m-d H:i:s', strtotime($input['from']));
            $to   = date('Y-m-d H:i:s', strtotime($input['to']));
            $result->whereRaw("created_at BETWEEN '$from' AND '$to'");
        }
        if (!empty($input['from']) && empty($input['to'])) {
            $from = date('Y-m-d H:i:s', strtotime($input['from']));
            $result->whereRaw("created_at >= '$from'");
        }
        if (empty($input['from']) && !empty($input['to'])) {
            $to = date('Y-m-d H:i:s', strtotime($input['to']));
            $result->whereRaw("created_at <= '$to'");
        }
        if (!empty($input['type'])) {
            $result->where('type', "{$input['type']}");
        } else {
            $result->whereIn('type', [PRODUCT_COMMENT_TYPE_RATE, PRODUCT_COMMENT_TYPE_QUESTION, PRODUCT_COMMENT_TYPE_RATESHIPPING]);
        }
        if (!empty($input['sort'])) {
            $this->sort($input, ProductComment::class, $result);
        }

        $result = $result->paginate($limit);
        return $this->response->paginator($result, $productCommentTransformer);
    }

    /**
     * @param $id
     * @param ProductCommentTransformer $productCommentTransformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detail($id, ProductCommentTransformer $productCommentTransformer)
    {
        $result = ProductComment::find($id);
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $productCommentTransformer);
    }

    /**
     * @param Request $request
     * @param ProductCommentCreateValidator $productCommentCreateValidator
     * @return array|void
     */
    public function create(Request $request, ProductCommentCreateValidator $productCommentCreateValidator)
    {
        $input = $request->all();
        $productCommentCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("comment_successful")];
    }

    /**
     * @param $id
     * @param Request $request
     * @param ProductCommentUpdateValidator $productCommentUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, ProductCommentUpdateValidator $productCommentUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $productCommentUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("product_comments.update-success", Message::get("product_comments") . " ID #" . $result->id)];
    }

    public function like($id, Request $request)
    {
        $productComment = ProductComment::find($id);
        $userlike       = json_decode($productComment->user_id_like);
        if (in_array(TM::getCurrentUserId() ?? 0, $userlike ?? [])) {
            $search = array_search(TM::getCurrentUserId(), $userlike);
            unset($userlike[$search]);
            $productComment->like         -= 1;
            $productComment->user_id_like = array_values($userlike);
            $productComment->save();
        } else {
            if (!empty($productComment->user_id_like)) {
                $userlike = json_decode($productComment->user_id_like);
                array_push($userlike, TM::getCurrentUserId());
                $productComment->like         += 1;
                $productComment->user_id_like = $userlike;
                $productComment->save();
            } else {
                $productComment->like         += 1;
                $productComment->user_id_like = [TM::getCurrentUserId()];
                $productComment->save();
            }
        }
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        $result = ProductComment::find($id);
        if (empty($result)) {
            $this->response->errorBadRequest(Message::get("V003", "ID: #$id"));
        }
        try {
            DB::beginTransaction();
            //Delete chil
            ProductComment::model()->where('parent_id', $id)->delete();
            //Delete ProductComment
            $result->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("product_comments.delete-success", Message::get("product_comments") . " #ID " . $result->id)];
    }


############################### Question and Answer ###############################

    /**
     * @param Request $request
     * @param ProductCommentTransformer $productCommentTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function searchQuestionAnswer(Request $request, ProductCommentTransformer $productCommentTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);

        $result = ProductComment::with(['user', 'product', 'parent', 'childs'])->whereNull('parent_id');
        if (isset($input['is_active'])) {
            $result->where('is_active', $input['is_active']);
        }
        if (isset($input['user_id'])) {
            $result->where('user_id', $input['user_id']);
        }
        if (isset($input['content'])) {
            $result->where('content', 'like', "%{$input['content']}%");
        }
        if (!empty($input['product_id'])) {
            $result->where('product_id', "{$input['product_id']}");
        }
        $result->where('type', PRODUCT_COMMENT_TYPE_QAA);
        $result = $result->paginate($limit);
        return $this->response->paginator($result, $productCommentTransformer);
    }

    /**
     * @param $id
     * @param ProductCommentTransformer $productCommentTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function detailQuestionAnswer($id, ProductCommentTransformer $productCommentTransformer)
    {
        $result = ProductComment::findOrFail($id);
        return $this->response->item($result, $productCommentTransformer);
    }

    public function createQuestionAnswer(Request $request, ProductCommentQuestionAnswerCreateValidator $validator)
    {
        $input = $request->all();
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $product     = Product::findOrFail($input['product_id']);
            $allProfiles = Profile::model()->pluck('full_name', 'user_id');
            $this->model->create([
                'type'         => PRODUCT_COMMENT_TYPE_QAA,
                'product_id'   => $input['product_id'],
                'product_code' => $product->code,
                'product_name' => $product->name,
                'user_id'      => TM::getCurrentUserId(),
                'user_name'    => $allProfiles[TM::getCurrentUserId()] ?? null,
                'content'      => $input['content'],
                'parent_id'    => $input['parent_id'] ?? null,
                'is_active'    => $input['is_active'] ?? 0
            ]);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("comment_question_successful")];
    }

    public function updateQuestionAnswer($id, Request $request, ProductCommentQuestionAnswerUpdateValidator $validator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $product     = Product::findOrFail($input['product_id']);
            $allProfiles = Profile::model()->pluck('full_name', 'user_id');
            $result      = $this->model->update([
                'id'           => $input['id'],
                'type'         => PRODUCT_COMMENT_TYPE_QAA,
                'product_id'   => $input['product_id'],
                'product_code' => $product->code,
                'product_name' => $product->name,
                'user_id'      => TM::getCurrentUserId(),
                'user_name'    => $allProfiles[TM::getCurrentUserId()] ?? null,
                'content'      => $input['content'],
                'parent_id'    => $input['parent_id'] ?? null,
                'is_active'    => $input['is_active'] ?? 0
            ]);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("product_comments.update-success", Message::get("product_comments") . " ID #" . $result->id)];
    }

    /*    ===Client===    */
    public function searchClient(Request $request, ProductCommentTransformer $productCommentTransformer)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = ProductComment::with(['user', 'product', 'parent', 'childs'])
            ->where('company_id', $company_id)
            ->where('store_id', $store_id)
            ->whereNull('parent_id');
        if (!empty($input['category_id'])) {
            $result->whereHas('product', function ($q) use ($input) {
                $q->where('category_ids', 'like', "%{$input['category_id']}%");
            });
        }
        if (isset($input['is_active'])) {
            $result->where('is_active', $input['is_active']);
        }
        if (isset($input['user_id'])) {
            $result->where('user_id', $input['user_id']);
        }
        if (isset($input['content'])) {
            $result->where('content', 'like', "%{$input['content']}%");
        }
        if (!empty($input['product_id'])) {
            $result->where('product_id', "{$input['product_id']}");
        }

        if (!empty($input['category_id'])) {
            $param['category_id'] = $input['category_id'] ?? null;
            $result->whereHas('product', function ($q) use ($param) {
                $q->where('category_ids', 'like', "%{$param['category_id']}%");
            });
        }

        if (!empty($input['sort'])) {
            $this->sort($input, ProductComment::class, $result);
        }
        $result->where('type', PRODUCT_COMMENT_TYPE_RATE)->where('is_active', 1)->orderBy('created_at', 'desc');
        $result = $result->paginate($limit);
        return $this->response->paginator($result, $productCommentTransformer);
    }

    public function getClientProductComment($id, Request $request, ProductCommentTransformer $productCommentTransformer)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $result = ProductComment::find($id);
        if (!$result) {
            return ['data' => []];
        }
        return $this->response->item($result, $productCommentTransformer);
    }

    public function report(Request $request)
    {
        //ob_end_clean();
        $input    = $request->all();
        $date     = date('YmdHis', time());
        $time     = date('Y-m-d', time());
        $comments = ProductComment::with(['product', 'user'])
            ->where('store_id', TM::getCurrentStoreId())
            ->where(function ($q) use ($input) {
                if (!empty($input['from']) && !empty($input['to'])) {
                    $q->whereDate('updated_at', '>=', $input['from'])->whereDate('updated_at', '<=', $input['to']);
                }
            })
            ->get();
        $i        = 0;
        foreach ($comments as $cmt) {
            $hashtags  = [];
            $user_code = [];
            $user_name = [];
            foreach (json_decode($cmt->hashtag_rates) as $hashtag) {
                array_push($hashtags, $hashtag->value);
            }
            $user_id = json_decode($cmt->user_id_like);
            if (!empty($cmt->user_id_like)) {
                foreach ($user_id as $i => $u) {
                    $user = User::model()->where('id', $u)->first();
                    array_push($user_code, $user->code);
                    array_push($user_name, $user->name);
                }
            }
            $dataCmt [] = [
                "stt"            => ++$i,
                "product_code"   => $cmt->product_code,
                "product_name"   => $cmt->product_name,
                "user_name"      => $cmt->user_name,
                "content"        => $cmt->content,
                "rate"           => $cmt->rate,
                "rate_name"      => $cmt->rate_name,
                "hashtag"        => implode(", ", $hashtags),
                "like_cmt"       => $cmt->like,
                "user_code_like" => implode(", ", $user_code),
                "user_name_like" => implode(", ", $user_name),
                "order_code"     => $cmt->order_code,
                "replied"        => $cmt->replied,
            ];
        }
        // die;
        if (empty($input['from'])) {
            $input['from'] = '';
        }
        if (empty($input['to'])) {
            $input['to'] = '';
        }
        //ob_start(); // and this
        return Excel::download(new ExportComments($dataCmt ?? [], $input['from'], $input['to']), 'list_comments_' . $date . '.xlsx');
    }
}