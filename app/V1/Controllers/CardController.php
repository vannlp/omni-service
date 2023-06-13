<?php


namespace App\V1\Controllers;


use App\Card;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\CardModel;
use App\V1\Transformers\Card\CardTransformer;
use App\V1\Validators\CardCreateValidator;
use App\V1\Validators\CardUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CardController extends BaseController
{
    protected $model;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->model = new CardModel();
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
     * @param CardTransformer $cardTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, CardTransformer $cardTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }
        if (!empty($input['type'])) {
            $input['type'] = ['like' => $input['type']];
        }
        Log::view($this->model->getTable());
        $card = $this->model->search($input, [], $limit);
        return $this->response->paginator($card, $cardTransformer);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function detail($id, CardTransformer $cardTransformer)
    {
        $card = Card::find($id);
        if (empty($card)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($card, $cardTransformer);
    }


    public function create(Request $request, CardCreateValidator $cardCreateValidator)
    {
        $input = $request->all();
        $cardCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $card = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $card->id . "-" . $card->code . "-" . $card->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("cards.create-success", $card->name)];
    }

    public function update($id, Request $request, CardUpdateValidator $cardUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $cardUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $card = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $card->id, null, $card->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("cards.update-success", $card->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $card = Card::find($id);
            if (empty($card)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $card->delete();
            Log::delete($this->model->getTable(), "#ID:" . $card->id . "-" . $card->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("cards.delete-success", $card->name)];
    }
}