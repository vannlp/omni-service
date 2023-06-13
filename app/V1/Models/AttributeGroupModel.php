<?php

namespace App\V1\Models;

use App\Attribute;
use App\AttributeGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AttributeGroupModel extends AbstractModel
{
    /**
     * AttributeGroupModel constructor.
     *
     * @param AttributeGroup|null $model
     */
    public function __construct(AttributeGroup $model = null)
    {
        parent::__construct($model);
    }

    /**
     * Get type
     *
     * @return array
     */
    public function getType()
    {
        return AttributeGroup::TYPE;
    }

    /**
     * Check slug
     *
     * @param $slug
     * @param $storeId
     * @param $id
     * @return bool
     */
    private function checkSlug($slug, $storeId, $id)
    {
        return $this->model->where('slug', $slug)
            ->where('store_id', $storeId)
            ->where(function($query) use ($id) {
                if (!empty($id)) {
                    $query->where('id', '!=', $id);
                }
            })
            ->exists();
    }

    /**
     * GenerateSlug
     *
     * @param $name
     * @param $storeId
     * @param null $id
     * @return string
     */
    public function generateSlug($name, $storeId, $id = null)
    {
        $slug = Str::slug($name);
        if (!$this->checkSlug($slug, $storeId, $id)) {
            return $slug;
        }

        $newSlug = '';
        for ($i = 1; $i < 20; $i++) {
            $newSlug = $slug .= $i;
            if (!$this->checkSlug($newSlug, $storeId, $id)) {
                break;
            }
        }

        return $newSlug;
    }

    /**
     * Fill data
     *
     * @param $input
     * @return array
     */
    public function fillData($input)
    {
        return [
            'type'        => Arr::get($input, 'type'),
            'store_id'    => Arr::get($input, 'store_id'),
            'name'        => $input['name'],
            'description' => Arr::get($input, 'description'),
            'slug'        => $input['slug']
        ];
    }
}