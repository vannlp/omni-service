<?php

namespace App\V1\Controllers\NNDD;

use App\Category;
use App\Foundation\PromotionHandle;
use App\NnddLogs;
use App\Product;
use App\Supports\DataUser;
use App\TM;
use App\V1\Models\ProductModel;
use App\V1\Transformers\NNDD\NNDDPProductListTransformer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class NNDDController extends NNDDBaseController
{
    protected $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
    }
    # Định nghĩa các hàm xử lý logic
    public function listProduct(Request $request)
    {
        try {
            list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
            $input             = $request->all();
            $limit             = array_get($input, 'limit', 20);
            $input['store_id'] = $store_id;
            $input['area_ids'] = $area_ids ?? [];
            $input['group_id'] = $group_id;

            $request->merge($input);
            $products          = $this->productModel->searchClient($input, [
                'brand:id,name',
                'area:id,name',
                'stores:id,name',
                'storeOrigin:id,name',
                'category_code:slug'
            ], $limit);
            $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
                PromotionHandle::TYPE_USING_PRODUCT,
                $company_id
            );
            return $this->response->paginator($products, new NNDDPProductListTransformer($promotionPrograms))
                ->header('Cache-Control', 'max-age=300, public');
        } catch (\Exception $exception) {
            return $this->responseError($exception->getMessage(), 400);
        }
    }

    public function detaiProductBySlug($slug, Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();

        $request->merge(['group_id' => $group_id]);

        $product = Product::with(['variants', 'brand:id,name'])
            ->withCount('favorites')
            ->where('slug', $slug)
            ->whereHas('stores', function ($query) use ($store_id) {
                $query->where('store_id', $store_id);
            });

        $category_ids = (new Category())->getIdsOfProduct($store_id, $area_ids);

        $product = $product->where(function ($q) use ($category_ids) {
            foreach ($category_ids as $item) {
                $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
            }
        });

        $product = $product->with('priceDetail')->first();
        if (empty($product)) {
            return ['data' => []];
        }
        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );
        return $this->response->item($product, new NNDDPProductListTransformer($promotionPrograms))->header('Cache-Control', 'max-age=300, public');
    }

    public static function writeLogNNDD($type, $params, $param_request, $res, $content, $code, $status, $function_request, $log_find){
        try{
            if(!empty($log_find)){
                $log_find->param            = json_encode($params);
                $log_find->param_request    = json_encode($param_request);
                $log_find->response         = $res;
                $log_find->content          = $content;
                $log_find->code             = $code;
                $log_find->status           = $status;
                $log_find->function_request = $function_request;
                $log_find->count_repost     = $log_find->count_repost + 1;
                $log_find->save();
            }
            else{
                NnddLogs::create([
                    'param'             => !empty($params) ? json_encode($params) : null,
                    'params_request'     => json_encode($param_request),
                    'response'          => $res,
                    'content'           => $content,
                    'sync_type'         => $type,
                    'code'              => $code,
                    'status'            => $status,
                    'function_request'  => $function_request,
                ]);
            }
        }
        catch (Exception $e) {
            TM::sendMessage('NNDD Exception: ', $e);
            // throw new HttpException(500, $e->getMessage());
        }
        return true;
    }
}
