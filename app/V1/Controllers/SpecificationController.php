<?php


namespace App\V1\Controllers;


use App\Exports\SpecificationExport;
use App\Specification;
use App\Supports\Message;
use App\V1\Transformers\Specification\SpecificationTransformer;
use App\V1\Validators\Specification\SpecificationCreateValidator;
use App\V1\Validators\Specification\SpecificationUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;
use App\V1\Models\SpecificationModel;

class SpecificationController extends BaseController
{
    /**
     * @var Specification
     */
    protected $model;

    /**
     * SpecificationController constructor.
     */
    public function __construct()
    {
        $this->model = new Specification();
    }

    /**
     * @param Request $request
     * @return \Dingo\Api\Http\Response|object
     */
    public function search(Request $request)
    {
        $result = $this->model->search($request)->paginate($request->get('limit', 20));
        return $this->response->paginator($result, new SpecificationTransformer());
    }

    /**
     * @param $id
     * @return \Dingo\Api\Http\Response
     */
    public function view($id)
    {
        $result = $this->model->findOrFail($id);
        return $this->response->item($result, new SpecificationTransformer());
    }

    /**
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        $input = $request->all();
        (new SpecificationCreateValidator())->validate($input);
        $input['value'] = str_clean_special_characters($input['value']);
        $input['code'] = str_clean_special_characters($input['code']);
        (new SpecificationCreateValidator())->validate($input);
        $check = Specification::where('code',$input['code'])->where('value',$input['value'])->where('store_id',$input['store_id'])->exists();
        if($check){
            return $this->responseError(Message::get("unique",Message::get("specification_id")));
        }
        $result = $this->model->create($request->all());
        return ['data' => Message::get('R001', $result->value)];
    }

    /**
     * @param $id
     * @param Request $request
     * @return array
     */
    public function update($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        (new SpecificationUpdateValidator())->validate($input);
        $input['value'] = str_clean_special_characters($input['value']);
        $input['code'] = str_clean_special_characters($input['code']);
        (new SpecificationUpdateValidator())->validate($input);
        $result = Specification::where('code',$input['code'])->where('value',$input['value'])->where('store_id',$input['store_id'])->first();
        if($result && $id != $result->id){
            return $this->responseError(Message::get("unique",Message::get("specification_id")));
        }
        $param  = [
            'value' => Arr::get($input, 'value', $result->value),
            'code'  => Arr::get($input, 'code', $result->code)
        ];
        $result->update($param);
        return ['data' => Message::get('R002', $result->value)];
    }

    /**
     * @param $id
     * @return array
     */
    public function delete($id)
    {
        $result = $this->model->findOrFail($id);
        $result->delete();
        return ['data' => Message::get('R003', $result->value)];
    }

    public function specificationExportExcel(){
        //ob_end_clean(); // this
        $specifications = Specification::model()->get();
        //ob_start(); // and this
        return Excel::download(new SpecificationExport($specifications), 'list_specification.xlsx');
    }
}