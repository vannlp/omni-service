<?php
/**
 * User: kpistech2
 * Date: 2020-06-08
 * Time: 22:49
 */

namespace App\V1\Controllers;


use App\Company;
use App\Feature;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\FeatureModel;
use App\V1\Transformers\Feature\FeatureTransformer;
use App\V1\Validators\Feature\FeatureCreateValidator;
use App\V1\Validators\Feature\FeatureUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeatureController extends BaseController
{

    protected $model;

    /**
     * FeatureController constructor.
     * @param FeatureModel $model
     */
    public function __construct(FeatureModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param Request $request
     * @param FeatureTransformer $featureTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, FeatureTransformer $featureTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }

        $feature = $this->model->search($input, [], $limit);
        return $this->response->paginator($feature, $featureTransformer);
    }

    /**
     * @param $id
     * @param FeatureTransformer $featureTransformer
     * @return array|\Dingo\Api\Http\Response
     */
    public function detail($id, FeatureTransformer $featureTransformer)
    {
        $feature = Feature::find($id);
        if (empty($feature)) {
            return ['data' => []];
        }
        return $this->response->item($feature, $featureTransformer);
    }

    /**
     * @param Request $request
     * @param FeatureCreateValidator $featureCreateValidator
     * @return array|void
     */
    public function create(Request $request, FeatureCreateValidator $featureCreateValidator)
    {
        $input = $request->all();
        $featureCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $feature = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", Message::get("features") . ": " . $feature->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param FeatureUpdateValidator $featureUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, FeatureUpdateValidator $featureUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $featureUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $feature = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", Message::get("features") . ": " . $feature->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $feature = Feature::find($id);
            if (empty($feature)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $feature->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R003", Message::get("features") . ": " . $feature->name)];
    }

    public function activate($id)
    {
        try {
            DB::beginTransaction();
            $feature = $this->model->activate($id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", Message::get("features") . ": " . $feature->name)];
    }

    public function inActivate($id)
    {
        try {
            DB::beginTransaction();
            $feature = $this->model->inActivate($id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", Message::get("features") . ": " . $feature->name)];
    }

    /**
     * @return array
     */
    public function listActivated()
    {
        $company = Company::model()->where('id', TM::getCurrentCompanyId())->first();
        $features = !empty($company->features) ? explode(",", $company->features) : [];
        $feature_codes = Feature::model()->whereIn('code', $features)->get()->toArray();

        return ['data' => $feature_codes];
    }
}
