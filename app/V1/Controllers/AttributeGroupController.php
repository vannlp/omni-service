<?php

namespace App\V1\Controllers;

use App\Supports\Log;
use App\Supports\Message;
use App\V1\Models\AttributeGroupModel;
use App\V1\Transformers\AttributeGroup\AttributeGroupTransformer;
use App\V1\Validators\AttributeGroupCreateValidator;
use App\V1\Validators\AttributeGroupUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class AttributeGroupController extends BaseController
{
    /**
     * @var AttributeGroupModel $model
     */
    protected $model;

    /**
     * AttributeGroupController constructor.
     *
     * @param AttributeGroupModel|null $attributeGroupModel
     */
    public function __construct(AttributeGroupModel $attributeGroupModel = null)
    {
        $this->model = $attributeGroupModel ?: new AttributeGroupModel();
    }

    /**
     * Get type
     *
     * @return array
     */
    public function getType()
    {
        return ['data' => $this->model->getType()];
    }

    /**
     * Search
     *
     * @param Request $request
     * @param AttributeGroupTransformer $attributeGroupTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, AttributeGroupTransformer $attributeGroupTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        Log::view($this->model->getTable());
        $attributeGroups = $this->model->search($input, ['userCreated:id,name', 'userUpdated:id,name'], $limit);
        return $this->response->paginator($attributeGroups, $attributeGroupTransformer);
    }

    /**
     * Show
     *
     * @param $id
     * @param AttributeGroupTransformer $attributeGroupTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function show($id, AttributeGroupTransformer $attributeGroupTransformer)
    {
        $attributeGroup = $this->model->getFirstBy('id', $id, ['userCreated:id,name', 'userUpdated:id,name']);

        Log::view($this->model->getTable());
        return $this->response->item($attributeGroup, $attributeGroupTransformer);
    }

    /**
     * Store
     *
     * @param Request $request
     * @param AttributeGroupCreateValidator $attributeGroupCreateValidator
     * @return array
     */
    public function store(Request $request, AttributeGroupCreateValidator $attributeGroupCreateValidator)
    {
        $input = $request->all();
        $attributeGroupCreateValidator->validate($input);

        $input['slug'] = $this->model->generateSlug($input['name'], Arr::get($input, 'store_id'));

        $attribute = $this->model->create($this->model->fillData($input));

        Log::create($this->model->getTable(), "#ID:" . $attribute->id);
        return ['status' => Message::get("attribute_groups.create-success", $attribute->name)];
    }

    /**
     * Update
     *
     * @param $id
     * @param Request $request
     * @param AttributeGroupUpdateValidator $attributeGroupUpdateValidator
     * @return array
     */
    public function update($id, Request $request, AttributeGroupUpdateValidator $attributeGroupUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $attributeGroupUpdateValidator->validate($input);
        $input['slug'] = $this->model->generateSlug($input['name'], Arr::get($input, 'store_id'), $id);

        $model = $this->model->byId($id);

        $model->fill($this->model->fillData($input));
        $model->save();

        Log::update($this->model->getTable(), "#ID:" . $id);
        return ['status' => Message::get("attribute_groups.update-success", $model->name)];
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
        return ['status' => Message::get("attribute_groups.delete-success", $model->name)];
    }
}