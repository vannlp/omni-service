<?php


namespace App\V1\Controllers;


use App\RentGround;
use App\V1\Validators\RentGround\RentGroundCreateValidator;
use App\V1\Validators\RentGround\RentGroundUpdateValidator;
use App\V1\Transformers\RentGround\RentGroundTransformer;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\RentGroundModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GentGroundExport;

class RentGroundController extends BaseController
{
    protected $model;

    function __construct()
    {
        $this->rentGroundModel = new RentGroundModel;

    }

    function search(Request $request, RentGroundTransformer $rentSpaceTransformer){
        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result =  $this->rentGroundModel->search($input, [], $limit);
      
        return $this->response->paginator($result, $rentSpaceTransformer);
        
    }

    public function detail($id, RentGroundTransformer $rentSpaceTransformer)
    {
  
        $rentground = RentGround::find($id);

        if (empty($rentground)) {
            return ['data' => []];
        }

        return $this->response->item($rentground, $rentSpaceTransformer);
    }
    
    function store( Request $request, RentGroundCreateValidator $rentGroundCreateValidator){
        $input = $request->all();
        $rentGroundCreateValidator->validate($input);
        try{
            DB::beginTransaction();
            $result = $this->rentGroundModel->upsert($input);
            DB::commit();
        }catch(\Exception $ex){
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
         return ['status' => Message::get('R001', $result->name)];
       
      
    }
    public function update($id,Request $request, RentGroundUpdateValidator $rentGroundUpdateValidator){
      
        $input = $request->all();
        $input['id'] = $id;
        $rentGroundUpdateValidator->validate($input);
       
        try{
            DB::beginTransaction();
            $result= $this->rentGroundModel->upsert($input);
          
            DB::commit();
        }catch(\Exception $ex){
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get('R002', $result->id)];
    }
    public function delete($id){
        try {
            DB::beginTransaction();
            $result = RentGround::find($id);
            if (empty($result)) {
                return $this->responseError(Message::get('V003',$id));
            }
            $result->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get('R003',$result->id)];
    }
    public function rentGroundExportExcel()
    {
        //ob_end_clean();
        $rentGrounds = RentGround::model()->get();
        //ob_start();
        
        return Excel::download(new GentGroundExport($rentGrounds), 'list_rent_ground.xlsx');
    }

}