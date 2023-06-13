<?php

namespace App\V1\Models;

use App\Brand;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BrandModel extends AbstractModel
{
    /**
     * BrandModel constructor.
     *
     * @param Brand|null $model
     */
    public function __construct(Brand $model = null)
    {
        parent::__construct($model);
    }

    /**
     * Check slug
     *
     * @param $slug
     * @param $id
     * @return bool
     */
    private function checkSlug($slug, $id)
    {
        return $this->model->where('slug', $slug)
            ->where(function ($query) use ($id) {
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
     * @param null $id
     * @return string
     */
    public function generateSlug($name, $id = null)
    {
        $slug = Str::slug($name);
        if (!$this->checkSlug($slug, $id)) {
            return $slug;
        }

        $newSlug = '';
        for ($i = 1; $i < 20; $i++) {
            $newSlug = $slug .= $i;
            if (!$this->checkSlug($newSlug, $id)) {
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
            'name'        => $input['name'],
            'description' => Arr::get($input, 'description'),
            'slug'        => $input['slug'],
            'company_id'  => $input['company_id'],
            'store_id'    => $input['store_id'],
            'parent_id'   => Arr::get($input, 'parent_id')
        ];
    }
}