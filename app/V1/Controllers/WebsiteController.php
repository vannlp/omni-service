<?php


namespace App\V1\Controllers;


use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\WebsiteModel;
use App\V1\Transformers\Website\WebsiteTransformer;
use App\V1\Validators\Website\WebsiteCreateValidator;
use App\V1\Validators\Website\WebsiteUpdateValidator;
use App\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebsiteController extends BaseController
{
    /**
     * @var WebsiteModel
     */
    protected $model;

    /**
     * WebsiteController constructor.
     */
    public function __construct()
    {
        $this->model = new WebsiteModel();
    }

    /**
     * @param Request $request
     * @param WebsiteTransformer $transformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, WebsiteTransformer $transformer)
    {
//        var_dump(TM::getCurrentCompanyId());die();
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $input['company_id'] = ['=' => TM::getCurrentCompanyId()];
        $result = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($result, $transformer);
    }

    /**
     * @param $id
     * @param WebsiteTransformer $transformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detail($id, WebsiteTransformer $transformer)
    {
        $result = Website::find($id);
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $transformer);
    }

    /**
     * @param Request $request
     * @param WebsiteCreateValidator $validator
     * @return array|void
     */
    public function create(Request $request, WebsiteCreateValidator $validator)
    {
        $input = $request->all();
        $input['company_id'] = TM::getCurrentCompanyId();
        $validator->validate($input);
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
        return ['status' => Message::get("websites.create-success", $result->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param WebsiteUpdateValidator $validator
     * @return array|void
     */
    public function update($id, Request $request, WebsiteUpdateValidator $validator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $validator->validate($input);
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
        return ['status' => Message::get("websites.update-success", $result->name)];
    }

    public function delete($id)
    {
        $result = Website::find($id);
        if (empty($result)) {
            $this->response->errorBadRequest(Message::get("V003", Message::get("website")));
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
        return ['status' => Message::get("websites.delete-success", $result->name)];
    }

    public function getClientWebsite($domain, WebsiteTransformer $transformer)
    {
        $result = Website::model()->where('domain', $domain)->first();
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $transformer);
    }

    public function getClientWebsiteDomain($domain, WebsiteTransformer $transformer)
    {
        $result = Website::model()->where('domain', $domain)->first();
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $transformer);
    }
}