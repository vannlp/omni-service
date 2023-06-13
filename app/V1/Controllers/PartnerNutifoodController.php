<?php

namespace App\V1\Controllers;

use App\PartnerNutifood;
use App\V1\Traits\ControllerTrait;

use App\Supports\TM_Error;
use App\Supports\Message;

use App\V1\Models\PartnerNutifoodModel;
use Illuminate\Http\Request;
use App\V1\Validators\PartnerNutifoodCreateValidator;
use App\V1\Validators\PartnerNutifoodUpdateValidator;
use App\V1\Transformers\Partner_nutifood\PartnerNutifoodListTransformer;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PartnerExport;

class PartnerNutifoodController extends BaseController
{
    use ControllerTrait;

    protected $model;

    public function __construct()
    {
        $this->model = new PartnerNutifoodModel();
    }

    public function create(Request $request, PartnerNutifoodCreateValidator $partnerNutifoodCreateValidator){

        $partnerNutifoodCreateValidator->validate($request->all());
        try {
            $partner = $this->model->upsert($request->all());
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get('R001', $partner->id)];
    }

    public function update($id, Request $request, PartnerNutifoodUpdateValidator $partnerNutifoodUpdateValidator){

        $input = $request->all();
        $input['id'] = $id;

        $partnerNutifoodUpdateValidator->validate($input);
        return $this->model->upsert($input);
    }

    public function search(Request $request, PartnerNutifoodListTransformer $partnerNutifoodListTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
       
        $partnerList = $this->model->search($input, [], $limit);

        return $this->response->paginator($partnerList, $partnerNutifoodListTransformer);
    }

    public function detail($id, PartnerNutifoodListTransformer $partnerNutifoodListTransformer)
    {
  
        $partner = PartnerNutifood::find($id);

        if (empty($partner)) {
            return ['data' => []];
        }

        return $this->response->item($partner, $partnerNutifoodListTransformer);
    }

    public function delete($id)
    {
        $partner = PartnerNutifood::find($id);

        if(empty($partner)){
            throw new \Exception(Message::get("V003", "ID: #$id"));
        }

        $partner->delete();

        return ['status' => Message::get('R003', $partner->id)];
    }

    
    public function partnerExportExcel()
    {
        //ob_end_clean();
        $date = date('YmdHis', time());
        $partner = PartnerNutifood::model()->get();
        //ob_start();
        return Excel::download(new PartnerExport($partner), 'list_partner_' . $date . '.xlsx');
    }

   
}
