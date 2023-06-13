<?php
/**
 * User: kpistech2
 * Date: 2020-05-09
 * Time: 21:47
 */

namespace App\V1\Controllers;


use App\Company;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\UserCompany;
use App\V1\Models\CompanyModel;
use App\V1\Transformers\Company\CompanyTransformer;
use App\V1\Validators\CompanyCreateValidator;
use App\V1\Validators\CompanyUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends BaseController
{
    protected $model;

    /**
     * CompanyController constructor.
     */
    public function __construct()
    {
        $this->model = new CompanyModel();
    }

    public function search(Request $request, CompanyTransformer $companyTransformer)
    {
        $input = $request->all();

        try {
            $companies = $this->model->search($input, [], array_get($input, 'limit', 20));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return $this->response->paginator($companies, $companyTransformer);
    }

    /**
     * @param $id
     * @param CompanyTransformer $companyTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, CompanyTransformer $companyTransformer)
    {
        try {
            $company = $this->model->getFirstWhere(['id' => $id]);
            if (empty($company)) {
                $company = collect([]);
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($company, $companyTransformer);
    }

    public function store(
        Request $request,
        CompanyCreateValidator $companyCreateValidator,
        CompanyTransformer $companyTransformer
    ) {
        $input = $request->all();
        $companyCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $company = $this->model->upsert($input);
            Log::view($this->model->getTable());
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($company, $companyTransformer);
    }

    public function update(
        $id,
        Request $request,
        CompanyUpdateValidator $companyUpdateValidator,
        CompanyTransformer $companyTransformer
    ) {
        $input = $request->all();
        $input['id'] = $id;
        $companyUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $companyUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $company = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $company->id, null, $company->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($company, $companyTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $company = Company::find($id);
            if (empty($company)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Company
            $company->delete();
            Log::delete($this->model->getTable(), "#ID:" . $company->id . "-" . $company->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => 'OK', 'message' => "Delete Successful"];
    }

    ////////////////////////// MY COMPANY /////////////////////
    public function getMyCompany(Request $request, CompanyTransformer $companyTransformer)
    {
        $input = $request->all();

        try {
            $myCompanyId = UserCompany::model()->where('user_id', TM::getCurrentUserId())->get()
                ->pluck('company_id')->toArray();
            $input['id'] = ['in' => !empty($myCompanyId) ? $myCompanyId : ['-1']];
            $companies = $this->model->search($input, [], array_get($input, 'limit', 20));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return $this->response->paginator($companies, $companyTransformer);
    }

    /**
     * @param $id
     * @param CompanyTransformer $companyTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function viewDetailMyCompany($id, CompanyTransformer $companyTransformer)
    {
        try {
            $company = collect([]);
            $myCompanyId = UserCompany::model()->where('user_id', TM::getCurrentUserId())->get()
                ->pluck('company_id')->toArray();
            if (in_array($id, $myCompanyId)) {
                $company = $this->model->getFirstBy('id', $id);
            }

            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($company, $companyTransformer);
    }
}
