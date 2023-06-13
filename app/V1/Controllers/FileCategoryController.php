<?php

namespace App\V1\Controllers;

use App\FileCategory;
use App\Supports\Log;
use App\Supports\TM_Error;
use App\Supports\Message;
use App\V1\Models\FileCategoryModel;
use App\V1\Transformers\FileCategory\FileCategoryTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FileCategoryController extends BaseController
{
    /**
     * FileCategoryController constructor.
     */
    public function __construct()
    {
        $this->model = new FileCategoryModel();
    }

    /**
     * @param Request $request
     * @param FileCategoryTransformer $transformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, FileCategoryTransformer $transformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['store_id'])) {
            $input['store_id'] = ['=' => "{$input['store_id']}"];
        }
        if (!empty($input['name'])) {
            $input['category_name'] = ['like' => "%{$input['name']}%"];
        }
        $result = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($result, $transformer);
    }

    public function detail($id, FileCategoryTransformer $transformer)
    {
        $result = FileCategory::find($id);
        if (empty($result)) {
            return ['data' => null];
        }
        Log::view($this->model->getTable());
        return $this->response->item($result, $transformer);
    }

    /**
     * @param Request $request
     * @return array|void
     */
    public function store(Request $request)
    {
        $input = $request->all();

        try {
            $category = $this->model->getFirstBy('category_name', $input['name']);
            if ($category) {
                return $this->response->errorBadRequest("[" . $input['name'] . "]" . ' already exist');
            }

            DB::beginTransaction();
            $result = $this->model->create($input);
            Log::create($this->model->getTable(), $result->category_name);
            DB::commit();

            return ['status' => Message::get("folders.create-success", $result->category_name)];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    /**
     * @param $id
     * @param Request $request
     * @return array|void
     */
    public function update($id, Request $request)
    {
        $input = $request->all();
        $input['id'] = $id;

        try {
            $category = $this->model->getFirstBy('id', $id);
            if (!$category) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $categoryName = $this->model->getFirstBy('category_name', $input['name']);
            if ($categoryName && $category->category_name != $input['name']) {
                return $this->response->errorBadRequest("[" . $input['name'] . "]" . ' already exist');
            }

            DB::beginTransaction();
            $result = $this->model->update($input);
            Log::update($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();

            return ['status' => Message::get("folders.update-success", $result->category_name)];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            $category = $this->model->getFirstBy('id', $id);
            if (!$category) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            DB::beginTransaction();
            $category->delete();
            Log::delete($this->model->getTable(), "#ID:" . $id);
            DB::commit();

            return ['status' => Message::get("department.delete-success", $category->category_name)];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }
}