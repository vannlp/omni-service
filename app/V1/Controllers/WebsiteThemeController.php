<?php


namespace App\V1\Controllers;


use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\WebsiteThemeModel;
use App\V1\Transformers\WebsiteTheme\WebsiteThemTransformer;
use App\V1\Validators\WebsiteTheme\WebsiteThemeCreateValidator;
use App\V1\Validators\WebsiteTheme\WebsiteThemeUpdateValidator;
use App\WebsiteTheme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteThemeController extends BaseController
{
    /**
     * @var WebsiteThemeModel
     */
    protected $model;

    /**
     * WebsiteThemeController constructor.
     */
    public function __construct()
    {
        $this->model = new WebsiteThemeModel();
    }

    public function search(Request $request, WebsiteThemTransformer $themTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $result = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, $themTransformer);
    }

    public function detail($id, Request $request, WebsiteThemTransformer $themTransformer)
    {
        $result = WebsiteTheme::find($id);
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $themTransformer);
    }

    public function create(Request $request, WebsiteThemeCreateValidator $websiteThemeCreateValidator)
    {
        $input = $request->all();
        $websiteThemeCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->create($input);
            Log::create($this->model->getTable(), "#ID:" . $result->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R001", $result->name)];
    }

    public function update($id, Request $request, WebsiteThemeUpdateValidator $websiteThemeUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $websiteThemeUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->update($input);
            Log::update($this->model->getTable(), "#ID:" . $result->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", $result->name)];
    }

    public function delete($id)
    {
        $result = WebsiteTheme::find($id);
        if (empty($result)) {
            $this->response->errorBadRequest(Message::get("V003", Message::get("website_theme")));
        }
        try {
            DB::beginTransaction();
            $result->delete();
            Log::delete($this->model->getTable(), "#ID:" . $result->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R003", $result->name)];
    }
}