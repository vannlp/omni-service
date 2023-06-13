<?php


namespace App\V1\Controllers;


use App\AppMenu;
use App\Store;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\AppMenuModel;
use App\V1\Transformers\AppMenu\AppMenuTransformers;
use App\V1\Validators\AppMenuCreateValidator;
use App\V1\Validators\AppMenuUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppMenuController extends BaseController
{
    /**
     * @var AppMenuModel
     */
    protected $model;

    /**
     * AppMenuController constructor.
     */
    public function __construct()
    {
        $this->model = new AppMenuModel();
    }

    /**
     * @param Request $request
     * @param AppMenuTransformers $appMenuTransformers
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, AppMenuTransformers $appMenuTransformers)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        Log::view($this->model->getTable());
        $result = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, $appMenuTransformers);
    }

    /**
     * @param $id
     * @param AppMenuTransformers $appMenuTransformers
     * @return array|\Dingo\Api\Http\Response
     */
    public function detail($id, AppMenuTransformers $appMenuTransformers)
    {
        $result = AppMenu::find($id);
        if (empty($result)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($result, $appMenuTransformers);
    }

    public function view($code, AppMenuTransformers $appMenuTransformers)
    {
        $result = AppMenu::model()->where('code', $code)->first();
        if (empty($result)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($result, $appMenuTransformers);
    }

    public function create(Request $request, AppMenuCreateValidator $appMenuCreateValidator)
    {
        $input = $request->all();
        $appMenuCreateValidator->validate($input);
        $checkCode = AppMenu::model()->where([
            'store_id' => $input['store_id'],
            'code'     => $input['code']
        ])->first();
        if (!empty($checkCode)) {
            return $this->response()->errorBadRequest(Message::get('V008', $input['code']));
        }
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("app-menus.create-success", $result->code)];
    }

    public function update($id, Request $request, AppMenuUpdateValidator $appMenuUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $appMenuUpdateValidator->validate($input);
        $checkCode = AppMenu::model()->where([
            'store_id' => $input['store_id'],
            'code'     => $input['code'],
        ])->first();
        if (!empty($checkCode) && $checkCode->id != $id) {
            return $this->response()->errorBadRequest(Message::get('V008', $input['code']));
        }
        try {
            DB::beginTransaction();
            $banner = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $banner->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("app-menus.update-success", $banner->code)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = AppMenu::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $result->delete();
            Log::delete($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("app-menus.delete-success", $result->code)];
    }

    public function getMenu($menu_code, Request $request, AppMenuTransformers $appMenuTransformers)
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
        $sysConfig = DB::table('system_configs')->where('code', 'APPMENU-CACHE')->value('value');
        if ($this->chkCache($menu_code)) {
            if ($sysConfig) {
                return $this->response->item($this->getCache($menu_code), $appMenuTransformers)->header('Cache-Control', 'public, max-age=1800');
            }
            return $this->response->item($this->getCache($menu_code), $appMenuTransformers)->header('Cache-Control', 'no-cache, max-age=0');
        }
        $result = AppMenu::model()->where('code', $menu_code)->where('store_id', $store_id)->first();
        if (empty($result)) {
            return ['data' => []];
        }
        $this->setCache($menu_code, $result, 1800);
        $result = $this->getCache($menu_code);
        Log::view($this->model->getTable());
        if ($sysConfig) {
            return $this->response->item($result, $appMenuTransformers)->header('Cache-Control', 'public, max-age=1800');
        }
        return $this->response->item($result, $appMenuTransformers)->header('Cache-Control', 'no-cache, max-age=0');
    }
}