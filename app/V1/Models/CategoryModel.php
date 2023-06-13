<?php

namespace App\V1\Models;

use App\Category;
use App\CategoryStore;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\AbstractModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class CategoryModel
 * @package App\V1\CMS\Models
 */
class CategoryModel extends AbstractModel
{
    /**
     * CategoryModel constructor.
     * @param Category|null $model
     */
    public function __construct(Category $model = null)
    {
        parent::__construct($model);
    }

    public function getAll($input = [], $with = [])
    {
        $query = $this->make($with)
            ->orderBy('parent_id')
            ->orderBy('id');
        $query->whereHas('stores', function ($q) use ($input) {
            $q->where('store_id', $input['store_id']);
        });
        $data   = $query->get();
        $result = [];
        if (!empty($data)) {
            foreach ($data as $key => $root) {
                if (!empty($root->parent_id)) {
                    break;
                }
                unset($data[$key]);
                $file_code = Arr::get($root, 'file.code', null);
                $rootData  = [
                    'id'               => Arr::get($root, 'id', null),
                    'code'             => Arr::get($root, 'code', null),
                    'name'             => Arr::get($root, 'name', null),
                    'type'             => Arr::get($root, 'type', null),
                    'order'            => Arr::get($root, 'order', null),
                    'sort_order'       => Arr::get($root, 'sort_order', null),
                    'image_id'         => Arr::get($root, 'image_id', null),
                    'image_url'        => !empty($file_code) ? env('GET_FILE_URL') . $file_code : null,
                    'description'      => Arr::get($root, 'description', null),
                    'parent_id'        => Arr::get($root, 'parent_id', null),
                    'parent_code'      => Arr::get($root, 'parent.code', null),
                    'parent_name'      => Arr::get($root, 'parent.name', null),
                    'category_publish' => Arr::get($root, 'category_publish', null),
                    'product_publish'  => Arr::get($root, 'product_publish', null),
                    'is_active'        => Arr::get($root, 'is_active', null),
                    'created_at'       => date('d-m-Y', strtotime($root['created_at'])),
                    'updated_at'       => date('d-m-Y', strtotime($root['updated_at'])),
                    'store_details'    => Arr::get($root, 'stores', null)
                ];

                $sub['children'] = $this->loadChildren($data, $root->id);
                $result[]        = array_merge($rootData, $sub);
            }
        }
        return $result;
    }

    public function show($input, $id, $with = [])
    {
        $query = $this->make($with)
            ->orderBy('parent_id')
            ->orderBy('id');
        $query->whereHas('stores', function ($q) use ($input) {
            $q->where('store_id', $input['store_id']);
        });
        $data   = $query->get();
        $result = [];
        if (!empty($data)) {
            foreach ($data as $key => $root) {
                if ($root->id == $id) {
                    unset($data[$key]);
                    $file_code       = Arr::get($root, 'file.code', null);
                    $rootData        = [
                        'id'               => Arr::get($root, 'id', null),
                        'code'             => Arr::get($root, 'code', null),
                        'name'             => Arr::get($root, 'name', null),
                        'type'             => Arr::get($root, 'type', null),
                        'order'            => Arr::get($root, 'order', null),
                        'sort_order'       => Arr::get($root, 'sort_order', null),
                        'image_id'         => Arr::get($root, 'image_id', null),
                        'image_url'        => !empty($file_code) ? env('GET_FILE_URL') . $file_code : null,
                        'description'      => Arr::get($root, 'description', null),
                        'parent_id'        => Arr::get($root, 'parent_id', null),
                        'parent_code'      => Arr::get($root, 'parent.code', null),
                        'parent_name'      => Arr::get($root, 'parent.name', null),
                        'category_publish' => Arr::get($root, 'category_publish', null),
                        'product_publish'  => Arr::get($root, 'product_publish', null),
                        'is_active'        => Arr::get($root, 'is_active', null),
                        'created_at'       => date('d-m-Y', strtotime($root['created_at'])),
                        'updated_at'       => date('d-m-Y', strtotime($root['updated_at']))
                    ];
                    $sub['children'] = $this->loadChildren($data, $root['id']);
                    $result[]        = array_merge($rootData, $sub);
                }
            }
        }
        return $result;
    }

    private function loadChildren(&$data, $parent_id)
    {
        $result = [];
        foreach ($data as $key => $item) {
            if ($item->parent_id == $parent_id) {
                unset($data[$key]);
                $file_code       = Arr::get($item, 'file.code', null);
                $itemData        = [
                    'id'               => Arr::get($item, 'id', null),
                    'code'             => Arr::get($item, 'code', null),
                    'name'             => Arr::get($item, 'name', null),
                    'type'             => Arr::get($item, 'type', null),
                    'order'            => Arr::get($item, 'order', null),
                    'sort_order'       => Arr::get($item, 'sort_order', null),
                    'image_id'         => Arr::get($item, 'image_id', null),
                    'image_url'        => !empty($file_code) ? env('GET_FILE_URL') . $file_code : null,
                    'description'      => Arr::get($item, 'description', null),
                    'parent_id'        => Arr::get($item, 'parent_id', null),
                    'parent_code'      => Arr::get($item, 'parent.code', null),
                    'parent_name'      => Arr::get($item, 'parent.name', null),
                    'category_publish' => Arr::get($item, 'category_publish', null),
                    'product_publish'  => Arr::get($item, 'product_publish', null),
                    'is_active'        => Arr::get($item, 'is_active', null),
                    'created_at'       => date('d-m-Y', strtotime($item->created_at)),
                    'updated_at'       => date('d-m-Y', strtotime($item->updated_at))
                ];
                $sub['children'] = $this->loadChildren($data, $item->id);
                $result[]        = array_merge($itemData, $sub);
            }
        }
        return $result;
    }

    public function searchProductTopSale($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);

        if (!empty($input['store_id'])) {
            $query->whereHas('stores', function ($q) use ($input) {
                $q->where('store_id', $input['store_id']);
            });
        }

        if (TM::getMyUserType() != USER_TYPE_USER) {
            $query->where('category_publish', 1);
        }
        $query->whereIn('id', $input['array_category']);
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->paginate();
        }
    }

    public function upsert($input)
    {
        if (!empty($input['parent_id'])) {
            if (empty($input['sort_order'])) {
                throw new \Exception(Message::get('V009', Message::get('sort_order')));
            }
        } else {
            if (empty($input['order'])) {
                throw new \Exception(Message::get('V009', Message::get('sort_order')));
            }
        }
        try {
            $id            = !empty($input['id']) ? $input['id'] : 0;
            $input['code'] = clean($input['code']);
            if ($id) {
                $category = Category::find($id);
                if (empty($category)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $category->name             = array_get($input, 'name', $category->name);
                $category->code             = array_get($input, 'code', $category->code);
                $category->sort_order       = array_get($input, 'sort_order', null);
                $category->slug             = !empty($input['slug']) ? $input['slug'] : Str::slug($input['name']);
                $category->parent_id        = array_get($input, 'parent_id', null);
                $category->order            = array_get($input, 'order', null);
                $category->sort_order       = array_get($input, 'sort_order', null);
                $category->is_nutizen       = array_get($input, 'is_nutizen', $category->is_nutizen);
                $category->type             = array_get($input, 'type', $category->type);
                $category->description      = array_get($input, 'description', null);
                $category->image_id         = !empty($input['image_id']) ? $input['image_id'] : null;
                $category->area_id          = !empty($input['area_id']) ? $input['area_id'] : null;
                $category->category_publish = !empty($input['category_publish']) ? 1 : 0;
                $category->product_publish  = (empty($input['category_publish']) ? 0 : !empty($input['product_publish'])) ? 1 : 0;
                $category->data             = !empty($input['data']) ? json_encode($input['data']) : null;
                $category->gift_item        = !empty($input['gift_item']) ? json_encode(array_get($input, 'gift_item')) : null;
                $category->property         = !empty($input['property']) ? json_encode(array_get($input, 'property')) : null;
                $category->property_ids     = !empty($input['property_ids']) ? array_get($input, 'property_ids') : null;
                $category->updated_at       = date("Y-m-d H:i:s", time());
                $category->updated_by       = TM::getCurrentUserId();
                $category->meta_title               = array_get($input, 'meta_title', $category->meta_title);
                $category->meta_description         = array_get($input, 'meta_description', $category->meta_description);
                $category->meta_robot               = array_get($input, 'meta_robot', $category->meta_robot);
                $category->meta_keyword             = array_get($input, 'meta_keyword', $category->meta_keyword);
                $category->save();
            } else {
                $param                     = [
                    'code'                 => $input['code'],
                    'name'                 => $input['name'],
                    'slug'                 => !empty($input['slug']) ? $input['slug'] : Str::slug($input['name']),
                    'description'          => array_get($input, 'description'),
                    'type'                 => array_get($input, 'type', 'PRODUCT'),
                    'sort_order'           => array_get($input, 'sort_order') ?? null,
                    'is_nutizen'           => array_get($input, 'is_nutizen') ?? 0,
                    'order'                => array_get($input, 'order') ?? null,
                    'parent_id'            => !empty($input['parent_id']) ? $input['parent_id'] : null,
                    'image_id'             => !empty($input['image_id']) ? $input['image_id'] : null,
                    'area_id'              => !empty($input['area_id']) ? $input['area_id'] : null,
                    'data'                 => !empty($input['data']) ? json_encode($input['data']) : null,
                    'gift_item'            => !empty($input['gift_item']) ? json_encode(array_get($input, 'gift_item')) : null,
                    'meta_title'           => array_get($input, 'meta_title',null),
                    'meta_description'     => array_get($input, 'meta_description', null),
                    'meta_robot'           => array_get($input, 'meta_robot', null),
                    'meta_keyword'         => array_get($input, 'meta_keyword', null),
                    'is_active'            => 1,
                ];
                $param['category_publish'] = !empty($input['category_publish']) ? 1 : 0;
                $param['product_publish']  = (empty($input['category_publish']) ? 0 : !empty($input['product_publish'])) ? 1 : 0;

                $category = $this->create($param);
            }
            $categoryId = $category->id;
            if (!empty($input['store_details'])) {
                //Create Category Store
                $allCategoryStore       = CategoryStore::model()->where('category_id', $category->id)->get()->toArray();
                $allCategoryStore       = array_pluck($allCategoryStore, 'id', 'id');
                $allCategoryStoreDelete = $allCategoryStore;
                foreach ($input['store_details'] as $key => $item) {
                    $id = $item['id'] ?? null;
                    if (!empty($allCategoryStoreDelete[$id])) {
                        unset($allCategoryStoreDelete[$id]);
                    }
                    $categoryStore = CategoryStore::find($id);
                    if (empty($categoryStore)) {
                        CategoryStore::model()->create([
                            'category_id' => $categoryId,
                            'store_id'    => $item['store_id'],
                            'store_code'  => $item['store_code'],
                            'store_name'  => $item['store_name'],
                        ]);
                    } else {
                        $categoryStore->category_id = $item['category_id'];
                        $categoryStore->store_id    = $item['store_id'];
                        $categoryStore->store_code  = $item['store_code'];
                        $categoryStore->store_name  = $item['store_name'];
                        $categoryStore->save();
                    }
                }
                if (!empty($allCategoryStoreDelete)) {
                    CategoryStore::model()->whereIn('id', array_values($allCategoryStoreDelete))->delete();
                }
            }
            if (!empty($input['property_ids'])) {
                $category->property_ids = $input['property_ids'];
                $category->categoryProperties()->sync(explode(",", $input['property_ids']));
            }

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
        return $category;
    }

    public function hierarchy($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
//        $this->sortBuilder($query, $input);
        $query->whereHas('CategoryStoreDetails', function ($q) use ($input) {
            $q->where('store_id', TM::getCurrentStoreId());
        });
        if (!empty($input['type'])) {
            $query = $query->where('type', $input['type']);
        }
        if (!empty($input['in_ids'])) {
            $query->whereIn('id', $input['in_ids']);
        }
        if (!empty($input['id'])) {
            $query->where('id', $input['id']);
        }
        $query->whereNull('parent_id')->orderBy('order', 'ASC');
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->paginate();
        }
    }

    public function clientHierarchy($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
//        $this->sortBuilder($query, $input);
        if (!empty($input['type'])) {
            $query = $query->where('type', $input['type']);
        }
        if (!empty($input['in_ids'])) {
            $query->whereIn('id', $input['in_ids']);
        }
        if (!empty($input['id'])) {
            $query->where('id', $input['id']);
        }
        if (!empty($input['store_id'])) {
            $query->whereHas('CategoryStoreDetails', function ($q) use ($input) {
                $q->where('store_id', $input['store_id']);
            });
        }
        $query->whereNull('parent_id')->orderBy('order', 'ASC');
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->paginate();
        }
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);

        if (!empty($input['store_id'])) {
            $query->whereHas('stores', function ($q) use ($input) {
                $q->where('store_id', $input['store_id']);
            });
        }
        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
        }
        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (!empty($input['type'])) {
            $query->where('type', 'like', "%{$input['type']}%");
        }

        if (!empty($input['level']) && $input['level'] == 1) {
            $query->whereNull('parent_id');
        }

        if (!empty($input['in_ids'])) {
            $query->whereIn('id', $input['in_ids']);
        }

        if (!empty($input['parent_id'])) {
            $query->where('parent_id', $input['parent_id']);
        }

        if (TM::getMyUserType() != USER_TYPE_USER) {
            $query->where('category_publish', 1);
        }

        if (!empty($input['area_ids'])) {
            $query->whereIn('area_id', $input['area_ids']);
        }

        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->paginate();
        }
    }
}