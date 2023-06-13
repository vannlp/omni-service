<?php


namespace App\V1\Controllers;


use App\Batch;
use App\Exports\BatchExport;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\BatchModel;
use App\V1\Traits\ReportTrait;
use App\V1\Transformers\Batch\BatchTransformer;
use App\V1\Validators\BatchCreateValidator;
use App\V1\Validators\BatchUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class BatchController extends BaseController
{
    use ReportTrait;
    protected $model;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->model = new BatchModel();
    }

    /**
     * @return array
     */
    public function index()
    {
        return ['status' => '0k'];
    }

    /**
     * @param Request $request
     * @param BatchTransformer $batchTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, BatchTransformer $batchTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 999);
        $batch = $this->model->search($input, [], $limit);
        return $this->response->paginator($batch, $batchTransformer);
    }

    public function detail($id, BatchTransformer $batchDetailTransformer)
    {
        $batch = Batch::find($id);
        if (empty($batch)) {
            return ['data' => []];
        }
        return $this->response->item($batch, $batchDetailTransformer);
    }

    public function create(Request $request, BatchCreateValidator $batchCreateValidator)
    {
        $input = $request->all();
        $batchCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $batchCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $batch = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("batch.create-success", $batch->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param BatchUpdateValidator $batchUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, BatchUpdateValidator $batchUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $batchUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $batchUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $batch = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("batch.update-success", $batch->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $batch = Batch::find($id);
            if (empty($batch)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $batch->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("batch.delete-success", $batch->name)];
    }

    public function batchExportExcel()
    {
        //ob_end_clean();
        $batch = Batch::model()->get();
        //ob_start();
        return Excel::download(new BatchExport($batch), 'list_batch.xlsx');
    }
}