<?php

namespace App\V1\Models;

use App\Attribute;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AttributeModel extends AbstractModel
{
    /**
     * AttributeModel constructor.
     *
     * @param Attribute|null $model
     */
    public function __construct(Attribute $model = null)
    {
        parent::__construct($model);
    }

    /**
     * Check slug
     *
     * @param $slug
     * @param $attributeGroupId
     * @param $id
     * @return bool
     */
    private function checkSlug($slug, $attributeGroupId, $id)
    {
        return $this->model->where('slug', $slug)
            ->where('attribute_group_id', $attributeGroupId)
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
     * @param $attributeGroupId
     * @param null $id
     * @return string
     */
    public function generateSlug($name, $attributeGroupId, $id = null)
    {
        $slug = Str::slug($name);
        if (!$this->checkSlug($slug, $attributeGroupId, $id)) {
            return $slug;
        }

        $newSlug = '';
        for ($i = 1; $i < 20; $i++) {
            $newSlug = $slug .= $i;
            if (!$this->checkSlug($newSlug, $attributeGroupId, $id)) {
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
            'attribute_group_id' => Arr::get($input, 'attribute_group_id'),
            'name'               => $input['name'],
            'description'        => Arr::get($input, 'description'),
            'value'              => Arr::get($input, 'value'),
            'slug'               => $input['slug'],
            'order'              => Arr::get($input, 'order')
        ];
    }
}