<?php


namespace App\V1\Controllers;


use App\Banner;
use App\Category;
use App\Store;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\BannerModel;
use App\V1\Transformers\Banner\BannerClientTransformer;
use App\V1\Transformers\Banner\BannerTransformer;
use App\V1\Validators\BannerCreateValidator;
use App\V1\Validators\BannerUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BannerController extends BaseController
{
    /**
     * @var BannerModel
     */
    protected $model;

    /**
     * BannerController constructor.
     */
    public function __construct()
    {
        $this->model = new BannerModel();
    }

    /**
     * @param Request $request
     * @param BannerTransformer $bannerTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, BannerTransformer $bannerTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        Log::view($this->model->getTable());
        if (!empty($input['store_id'])) {
            $input['store_id'] = ['=' => $input['store_id']];
        }
        $banner = $this->model->search($input, ['category:id,name'], $limit);
        return $this->response->paginator($banner, $bannerTransformer);
    }

    /**
     * @param $id
     * @param BannerTransformer $bannerTransformer
     * @return array|\Dingo\Api\Http\Response
     */
    public function detail($id, BannerTransformer $bannerTransformer, Request $request)
    {
        $input  = $request->all();
        $banner = Banner::with(['details', 'details.file:id,code,type', 'details.categoryBanner:id,name', 'category', 'createdBy.profile', 'updatedBy.profile'])->where('id', $id)->where('store_id', TM::getCurrentStoreId())->first();
        if (empty($banner)) {
            return ["data" => []];
        }

        Log::view($this->model->getTable());
        return $this->response->item($banner, $bannerTransformer);
    }

    public function view($code, BannerTransformer $bannerTransformer, Request $request)
    {
        $input = $request->all();
        if (empty($input['store_id'])) {
            return ["data" => []];
        }
        $banner = Banner::with(['details', 'details.file:id,code,type', 'details.categoryBanner:id,name', 'category', 'createdBy.profile', 'updatedBy.profile'])->where('code', $code)->where('store_id', $input['store_id'])->first();
        if (empty($banner)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($banner, $bannerTransformer);
    }

    /**
     * @param Request $request
     * @param BannerCreateValidator $cardCreateValidator
     * @return array|void
     */
    public function create(Request $request, BannerCreateValidator $bannerCreateValidator)
    {
        $input = $request->all();
        $bannerCreateValidator->validate($input);
        try {
            $checkBanner = Banner::model()->where('code', $input['code'])->where('store_id', $input['store_id'])->first();
            if ($checkBanner) {
                return $this->response->errorBadRequest(Message::get('V008', Message::get('banners')));
            }
            DB::beginTransaction();
            $banner = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $banner->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("banners.create-success", $banner->title)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param BannerUpdateValidator $bannerUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, BannerUpdateValidator $bannerUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $bannerUpdateValidator->validate($input);

        try {
            $checkBanner = Banner::model()->where('code', $input['code'])->where('store_id', TM::getCurrentStoreId())->first();
            if ($checkBanner && $checkBanner->id != $id) {
                return $this->response->errorBadRequest(Message::get('V008', Message::get('banners')));
            }
            DB::beginTransaction();
            $banner = $this->model->upsert($input);
            $this->delCache($banner->code);
            // Log::update($this->model->getTable(), "#ID:" . $banner->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("banners.update-success", $banner->title)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $banner = Banner::find($id);
            if (empty($banner)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $this->delCache($banner->code);
            $banner->delete();
            Log::delete($this->model->getTable(), "#ID:" . $banner->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("banners.delete-success", $banner->title)];
    }

    public function getClientBanner($code, BannerClientTransformer $bannerTransformer, Request $request)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                }
            }
        }
        $sysConfig = DB::table('system_configs')->where('code', 'BANNER-CACHE')->value('value');
        if ($this->chkCache($code)) {
            if ($sysConfig) {
                return $this->response->item($this->getCache($code), $bannerTransformer)->header('Cache-Control', 'max-age=300');
            }
            return $this->response->item($this->getCache($code), $bannerTransformer)->header('Cache-Control', 'no-cache, max-age=0');
        }
        $banner = Banner::with(['details', 'details.file:id,code,type', 'details.categoryBanner:id,name', 'category', 'createdBy.profile', 'updatedBy.profile'])
            ->where('code', $code)
            ->where('store_id', $store_id)
            ->first();
        if (empty($banner)) {
            return ["data" => null];
        }
        $this->setCache($code, $banner, 300);
        $banner = $this->getCache($code);
        // Log::view($this->model->getTable());
        if ($sysConfig) {
            return $this->response->item($banner, $bannerTransformer)->header('Cache-Control', 'max-age=300');
        }
        return $this->response->item($banner, $bannerTransformer)->header('Cache-Control', 'no-cache, max-age=0');
    }

    public function getClientBannerCategory($id, Request $request, BannerClientTransformer $bannerTransformer)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                }
            }
        }
        $category = Category::with('banner')->where('id', $id)->first();
        $category->whereHas('stores', function ($q) use ($store_id) {
            $q->where('store_id', $store_id);
        });
        if (empty($category)) {
            return ['data' => null];
        }
        $banners = $category->banner;

        Log::view($this->model->getTable());
        return $this->response->collection($banners, $bannerTransformer);
    }
}
