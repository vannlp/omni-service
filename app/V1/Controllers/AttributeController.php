<?php

namespace App\V1\Controllers;

use App\Supports\Log;
use App\Supports\Message;
use App\V1\Models\AttributeModel;
use App\V1\Transformers\Attribute\AttributeTransformer;
use App\V1\Validators\AttributeCreateValidator;
use App\V1\Validators\AttributeUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AttributeController extends BaseController
{
    /**
     * @var AttributeModel $model
     */
    protected $model;

    /**
     * AttributeController constructor.
     *
     * @param AttributeModel|null $attributeModel
     */
    public function __construct(AttributeModel $attributeModel = null)
    {
        $this->model = $attributeModel ?: new AttributeModel();
    }

    /**
     * Search
     *
     * @param Request $request
     * @param AttributeTransformer $attributeTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, AttributeTransformer $attributeTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        Log::view($this->model->getTable());
        $attributes = $this->model->search($input, ['attributeGroup:id,name,type,slug', 'userCreated:id,name', 'userUpdated:id,name'], $limit);
        return $this->response->paginator($attributes, $attributeTransformer);
    }

    /**
     * Show
     *
     * @param $id
     * @param AttributeTransformer $attributeTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function show($id, AttributeTransformer $attributeTransformer)
    {
        $attribute = $this->model->getFirstBy('id', $id, ['attributeGroup:id,name,type,slug', 'userCreated:id,name', 'userUpdated:id,name']);

        Log::view($this->model->getTable());
        return $this->response->item($attribute, $attributeTransformer);
    }

    /**
     * Store
     *
     * @param Request $request
     * @param AttributeCreateValidator $attributeCreateValidator
     * @return array
     */
    public function store(Request $request, AttributeCreateValidator $attributeCreateValidator)
    {
        $input = $request->all();
        $attributeCreateValidator->validate($input);

        $input['slug'] = $this->model->generateSlug($input['name'], Arr::get($input, 'attribute_group_id'));

        $attribute = $this->model->create($this->model->fillData($input));

        Log::create($this->model->getTable(), "#ID:" . $attribute->id);
        return ['status' => Message::get("attributes.create-success", $attribute->name)];
    }

    /**
     * Update
     *
     * @param $id
     * @param Request $request
     * @param AttributeUpdateValidator $attributeUpdateValidator
     * @return array
     */
    public function update($id, Request $request, AttributeUpdateValidator $attributeUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $attributeUpdateValidator->validate($input);
        $input['slug'] = $this->model->generateSlug($input['name'], Arr::get($input, 'attribute_group_id'), $id);

        $model = $this->model->byId($id);

        $model->fill($this->model->fillData($input));
        $model->save();

        Log::update($this->model->getTable(), "#ID:" . $id);
        return ['status' => Message::get("attributes.update-success", $model->name)];
    }

    /**
     * Delete
     *
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        $model = $this->model->byId($id);

        if (empty($model)) {
            return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
        }

        $model->delete();

        Log::delete($this->model->getTable(), "#ID:" . $id);
        return ['status' => Message::get("attributes.delete-success", $model->name)];
    }
}