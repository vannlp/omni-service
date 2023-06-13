<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Models;

use App\Supports\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Class AbstractModel
 *
 * @package App\V1\Models
 */
abstract class AbstractModel
{
    protected        $model;
    protected static $_userInfo;
    protected        $modelName;

    public function __construct($model = null)
    {
        $modelName       = substr(get_called_class(), strrpos(get_called_class(), '\\') + 1);
        $this->modelName = str_replace('Model', '', $modelName);
        $modelName       = "\\App\\" . $this->modelName;

        $this->model = ($model) ?: new $modelName();
    }

    /**
     * Get empty model.
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return $this->modelName;
    }

    /**
     * Refresh model to be clean
     */
    public function refreshModel()
    {
        $this->model = $this->model->newInstance();
    }

    /**
     * Get table name.
     *  //show all data in table
     *
     * @return string
     */
    public function getTable()
    {
        return $this->model->getTable();
    }

    /**
     * Make a new instance of the entity to query on.
     *
     * @param array $with
     */
    public function make(array $with = [])
    {
        return $this->model->with($with);
    }

    /**
     * Find a single entity by key value.
     *
     * @param string $key
     * @param string $value
     * @param array $with
     */
    public function getFirstBy($key, $value, array $with = [])
    {
        $query = $this->make($with);

        //$this->model->filterData($query);

        return $query->where($key, '=', $value)->first();
    }

    /**
     * Retrieve model by id
     * regardless of status.
     *
     * @param int $id model ID
     * @param array $with
     *
     * @return Model
     */
    public function byId($id, array $with = [], $filter = '', $filterCus = false)
    {
        $query = $this->make($with)->where($this->model->getKeyName(), $id);
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }
        $model = $query->firstOrFail();

        return $model;
    }

    /**
     * Get next model.
     *
     * @param Model $model
     * @param array $with
     *
     * @return Model|null
     */
    public function next($model, array $with = [])
    {
        return $this->adjacent(1, $model, $with);
    }

    /**
     * Get prev model.
     *
     * @param Model $model
     * @param array $with
     *
     * @return Model|null
     */
    public function prev($model, array $with = [])
    {
        return $this->adjacent(-1, $model, $with);
    }

    /**
     * Get prev model.
     *
     * @param int $direction
     * @param Model $model
     * @param array $with
     *
     * @return Model|null
     */
    public function adjacent($direction, $model, array $with = [])
    {
        $currentModel = $model;
        $models       = $this->all($with);

        foreach ($models as $key => $model) {
            if ($currentModel->{$this->model->getKeyName()} == $model->{$this->model->getKeyName()}) {
                $adjacentKey = $key + $direction;

                return isset($models[$adjacentKey]) ? $models[$adjacentKey] : null;
            }
        }
    }

    /**
     * Get paginated models.
     *
     * @param int $page Number of models per page
     * @param int $limit Results per page
     * @param array $with Eager load related models
     *
     * @return stdClass Object with $items && $totalItems for pagination
     */
    public function byPage($page = 1, $limit = 10, array $with = [])
    {
        $result             = new stdClass();
        $result->page       = $page;
        $result->limit      = $limit;
        $result->totalItems = 0;
        $result->items      = [];
        $query              = $this->make($with);
        $this->model->filterData($query);
        $totalItems = $query->count();
        $query->skip($limit * ($page - 1))
            ->take($limit);
        $models = $query->get();
        // Put items and totalItems in stdClass
        $result->totalItems = $totalItems;
        $result->items      = $models->all();

        return $result;
    }

    /**
     * Get all models.
     *
     * @param array $with Eager load related models
     *
     * @return Collection
     */
    public function all(array $with = [], $filter = '', $filterCus = false)
    {
        $query = $this->make($with);
        $this->model->filterData($query);

        // Get
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }

        return $query->get();
    }

    /**
     * Get all models by key/value.
     *
     * @param string $key
     * @param string $value
     * @param array $with
     *
     * @return Collection
     */
    public function allBy($key, $value, array $with = [], $filter = '', $filterCus = false)
    {
        $query = $this->make($with);

        $query->where($key, $value);
        $this->model->filterDataIn($query);
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }

        // Get
        $models = $query->get();

        return $models;
    }

    /**
     * Get latest models.
     *
     * @param int $number number of items to take
     * @param array $with array of related items
     *
     * @return Collection
     */
    public function latest($number = 10, array $with = [], $filter = '', $filterCus = false)
    {
        $query = $this->make($with);
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }

        return $query->take($number)->get();
    }

    /**
     * Get single model by Slug.
     *
     * @param string $slug slug
     * @param array $with related tables
     *
     * @return mixed
     */
    public function bySlug(
        $slug,
        array $with = [],
        $filter = '',
        $filterCus = false
    )
    {
        $query = $this->make($with)
            ->where('slug', '=', $slug);
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }
        $model = $query->firstOrFail();

        return $model;
    }

    /**
     * Return all results that have a required relationship.
     *
     * @param string $relation
     * @param array $with
     *
     * @return Collection
     */
    public function has($relation, array $with = [])
    {
        $entity = $this->make($with);

        return $entity->has($relation)->get();
    }

    /**
     * Create a new model.
     *
     * @param array $data
     *
     * @return mixed Model or false on error during save
     */
    public function create(array $data)
    {
        // Create the model
        $model = $this->model->fill($data);

        if ($model->save()) {

            return $model;
        }

        return false;
    }

    /**
     * Update an existing model.
     *
     * @param array $data
     *
     * @return mixed Model or false on error during save
     */
    public function update(array $data)
    {
        $model = $this->model->findOrFail($data[$this->model->getKeyName()]);
        $model->fill($data);

        if ($model->save()) {
            return $model;
        }

        return false;
    }

    public function updateWhere($dataUpdated, $where)
    {
        $query = $this->make();
        foreach ($where as $field => $value) {
            if ($value instanceof \Closure) {
                $query->where($value);
            } elseif (is_array($value)) {
                if (count($value) === 3) {
                    list($field, $operator, $search) = $value;
                    $query->where($field, $operator, $search);
                } elseif (count($value) === 2) {
                    list($field, $search) = $value;
                    $query->where($field, '=', $search);
                }
            } else {
                //                switch (strtoupper($operator)) {
                //                    case "IN":
                //                        if (!is_array($search)) {
                //                            $search = [$search];
                //                        }
                //                        $query->whereIn($field, $search);
                //                        break;
                //                    case "NOT IN":
                //                        if (!is_array($search)) {
                //                            $search = [$search];
                //                        }
                //                        $query->whereNotIn($field, $search);
                //                        break;
                //                    case "NULL":
                //                        $query->whereNull($field, $search);
                //                        break;
                //                    case "NOT NULL":
                //                        $query->whereNotNull($field, $search);
                //                        break;
                //                    default:
                $query->where($field, $value);
                //                }
            }
        }

        return $query->update($dataUpdated);
    }

    /**
     * Get One collection of models by the given query conditions.
     *
     * @param array $where
     * @param array $with
     * @param array $orderBy
     * @param array $columns
     * @param bool $or
     *
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function getFirstWhere(
        $where,
        array $with = [],
        array $orderBy = [],
        $columns = ['*'],
        $or = false,
        $filter = '',
        $filterCus = false
    )
    {
        $query     = $this->make($with);
        $funcWhere = ($or) ? 'orWhere' : 'where';
        foreach ($where as $field => $value) {

            if ($value instanceof \Closure) {
                $query = $query->{$funcWhere}($value);
            } elseif (is_array($value)) {
                if (count($value) === 3) {
                    list($field, $operator, $search) = $value;
                    $query = $query->{$funcWhere}($field, $operator, $search);
                } elseif (count($value) === 2) {
                    list($field, $search) = $value;
                    $query = $query->{$funcWhere}($field, '=', $search);
                } else {
                    $k = array_keys($value)[0];
                    $v = $value[$k];
                    switch (strtolower($k)) {
                        case "IN":
                            $query = $query->whereIn(DB::raw($field), $v);
                            break;
                        case "NOT IN":
                            $query = $query->whereNotIn(DB::raw($field), $v);
                            break;
                        case "NULL":
                            $query = $query->whereNull(DB::raw($field));
                            break;
                        case "NOT NULL":
                            $query = $query->whereNotNull(DB::raw($field));
                            break;
                        case "LIKE":
                            $query = $query->where(DB::raw($field), "like", $v);
                            break;
                        case "NOT LIKE":
                            $query = $query->where(DB::raw($field), "not like", $v);
                            break;
                        default:
                            $query = $query->where(DB::raw($field), $k, $v);
                            break;
                    }
                }
            } else {

                $query = $query->{$funcWhere}($field, '=', $value);
            }
        }
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }
        foreach ($orderBy as $column => $sortType) {
            $query->orderBy($column, $sortType);
        }

        return $query->first($columns);
    }

    /**
     * Find a collection of models by the given query conditions.
     *
     * @param array $where
     * @param array $with
     * @param array $orderBy
     * @param array $columns
     * @param bool $or
     *
     * @return \Illuminate\Database\Eloquent\Collection|null
     */
    public function findWhere(
        $where,
        array $with = [],
        array $orderBy = [],
        $columns = ['*'],
        $or = false,
        $filter = '',
        $filterCus = false
    )
    {
        $query     = $this->make($with);
        $funcWhere = ($or) ? 'orWhere' : 'where';
        foreach ($where as $field => $value) {
            if ($value instanceof \Closure) {
                $query = $query->{$funcWhere}($value);
            } elseif (is_array($value)) {
                if (count($value) === 3) {
                    list($field, $operator, $search) = $value;
                    $query = $query->{$funcWhere}($field, $operator, $search);
                } elseif (count($value) === 2) {
                    list($field, $search) = $value;
                    $query = $query->{$funcWhere}($field, '=', $search);
                }
            } else {
                $query = $query->{$funcWhere}($field, '=', $value);
            }
        }

        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }

        foreach ($orderBy as $column => $sortType) {
            $query->orderBy($column, $sortType);
        }


        return $query->get($columns);
    }

    /**
     * @param array $where
     * @param bool $or
     *
     * @return mixed
     */
    public function checkWhere($where, $or = false, $filter = '', $filterCus = false)
    {
        $query     = $this->make([]);
        $funcWhere = ($or) ? 'orWhere' : 'where';
        foreach ($where as $field => $value) {
            if ($value instanceof \Closure) {
                $query = $query->{$funcWhere}($value);
            } elseif (is_array($value)) {
                if (count($value) === 3) {
                    list($field, $operator, $search) = $value;
                    $query = $query->{$funcWhere}($field, $operator, $search);
                } elseif (count($value) === 2) {
                    list($field, $search) = $value;
                    $query = $query->{$funcWhere}($field, '=', $search);
                }
            } else {
                $query = $query->{$funcWhere}($field, '=', $value);
            }
        }
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }

        return $query->count();
    }

    /**
     * Sort models.
     *
     * @param array $data updated data
     *
     * @return null
     */
    public function sort(array $data)
    {
        foreach ($data['item'] as $position => $item) {
            $page     = $this->model->find($item[$this->model->getKeyName()]);
            $sortData = $this->getSortData($position + 1);
            $page->update($sortData);
        }
    }

    /**
     * Get sort data.
     *
     * @param int $position
     *
     * @return array
     */
    protected function getSortData($position)
    {
        return [
            'position' => $position,
        ];
    }

    /**
     * Delete model.
     *
     * @param Model $model
     *
     * @return bool
     */
    public function delete($model)
    {
        return $model->delete();
    }

    /**
     * Delete model By Ids
     *
     * @param array|int $ids
     *
     * @return bool
     */
    public function deleteById($ids)
    {
        $ids = is_array($ids) ? $ids : [$ids];

        return $this->model->destroy($ids);
    }

    /**
     * Sync related items for model.
     *
     * @param Model $model
     * @param array $data
     * @param string $table
     *
     * @return null
     */
    public function syncRelation($model, array $data, $table = null)
    {
        if (!method_exists($model, $table)) {
            return false;
        }
        if (!isset($data[$table])) {
            return false;
        }
        // add related items
        $pivotData = [];
        $position  = 0;
        if (is_array($data[$table])) {
            foreach ($data[$table] as $id) {
                $pivotData[$id] = ['position' => $position++];
            }
        }
        // Sync related items
        $model->$table()->sync($pivotData);
    }

    /**
     * Get location by $where and return columns arrays
     *
     * @param string $where
     * @param array $columns
     * @param array $params
     * @param bool $upperCase
     * @param bool $hashTbl
     *
     * @return array
     */
    public function fetchColumns($columns = [], $where = "", $params = [], $upperCase = false, $hashTbl = false)
    {
        //Hash table have to key , value
        if (count($columns) < 2 && $hashTbl) {
            return false;
        }

        $tblName  = $this->getTable();
        $colNames = implode(', ', $columns);

        $query = "
            SELECT $colNames
            FROM $tblName
        ";
        if (!empty($where)) {
            $query .= " WHERE $where";
        }

        $result = [];

        $db = $this->model->getConnection()->getPdo();

        $statement = $db->prepare($query);
        $statement->execute($params);

        // process return hash table
        if ($hashTbl) {
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $key = $row[$columns[0]];
                $val = $row[$columns[1]];
                if (count($row) > 2) {
                    $val = $row;
                }
                if ($upperCase) {
                    $key = strtoupper($key);
                }

                $result[$key] = $val;
            }
        } else {
            //Init result columns array
            foreach ($columns as $colName) {
                $result[$colName] = [];
            }
            while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
                foreach ($columns as $colName) {
                    if ($upperCase) {
                        $val = strtoupper($row[$colName]);
                    } else {
                        $val = $row[$colName];
                    }

                    $result[$colName][] = $val;
                }
            }
        }

        return $result;
    }

    /**
     * @param       $query
     * @param array $attributes
     *
     * @return bool
     */
    public function sortBuilder(&$query, $attributes = [])
    {
        $validConditions = ['asc', 'desc'];
        $validColumn     = DB::getSchemaBuilder()->getColumnListing($this->getTable());

        if (empty($attributes['sort'])) {
            $attributes['sort'] = ['updated_at' => 'desc'];
        }
        foreach ($attributes['sort'] as $key => $value) {

            if (!$value) {
                $value = 'asc';
            }

            if (!in_array($value, $validConditions)) {
                continue;
            }

            if (!in_array($key, $validColumn)) {
                continue;
            }
            $query->orderBy($key, $value);
        }
    }

    /**
     * @param array $params
     * @param array $with
     *
     * @return mixed
     */
    public function loadBy($params = [], $with = [], $filter = '', $filterCus = false)
    {
        $query = $this->make($with);

        if (!empty($params) && is_array($params)) {
            foreach ($params as $key => $value) {
                $query->where($key, $value);
            }
        }
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }
        // Get
        $models = $query->get();

        return $models;
    }

    /**
     * @param array $params
     * @param array $with
     *
     * @return mixed
     */
    public function loadByMany($params = [], $with = [], $filter = '', $filterCus = false)
    {
        $query = $this->make($with);

        if (!empty($params) && is_array($params)) {
            foreach ($params as $key => $value) {
                if (!is_array($value)) {
                    $value = [$value];
                }
                $query->whereIn($key, $value);
            }
        }
        // Get
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }
        $models = $query->get();

        return $models;
    }

    protected function _search($attributes, $with, $limit, $keys = [], $filter = '', $filterCus = false)
    {
        $likeKeys  = $keys['like'] ?: [];
        $equalKeys = $keys['equal'] ?: [];

        $query      = $this->make($with);
        $attributes = SelArr::removeNullOrEmptyString($attributes);
        if (!empty($attributes)) {
            foreach ($attributes as $key => $value) {
                if (in_array($key, $likeKeys)) {
                    $query->where($key, 'like', "%" . SelStr::escapeLike($value) . "%");
                } elseif (in_array($key, $equalKeys)) {
                    $query->where($key, $value);
                }
            }
        }
        if ($filter == 'current') {
            $this->model->filterData($query, $filterCus);
        } elseif ($filter == 'filterIn') {
            $this->model->filterDataIn($query, $filterCus);
        }
        $this->sortBuilder($query, $attributes);

        // Get
        $models = $query->paginate($limit);

        return $models;
    }

    public function naturalExec($query, $params = [], $fetchMode = \PDO::FETCH_ASSOC)
    {
        $tblName = $this->getTable();
        $result  = [];

        $db = $this->model->getConnection()->getPdo();

        $statement = $db->prepare($query);
        $statement->execute($params);

        $result = $statement->fetchAll($fetchMode);

        return $result;
    }

    /**
     * @return mixed
     * @author Dai Ho
     */
    public static function getCurrentUserInfo()
    {

    }

    /**
     * @return mixed
     * @author Dai Ho
     */
    public static function getCurrentCompany()
    {
        $userInfo = self::getCurrentUserInfo();

        return $userInfo['company_ids'];
    }

    /**
     * @param $id
     *
     * @return mixed
     * @author Dai Ho
     *
     */
    public function changeStatus($id)
    {
        $this->model->where('id', $id)->update([
            'is_active' => DB::raw("(CASE WHEN is_active = 1 THEN 0 ELSE 1 END)"),
        ]);

        return $this->model->where('id', $id)->first();
    }

    /**
     * @param array $input
     * @param array $with
     * @param null $limit
     *
     * @return mixed
     * @author Dai Ho
     *
     */
    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);

        $this->sortBuilder($query, $input);
        $full_columns = DB::getSchemaBuilder()->getColumnListing($this->getTable());

        $input = array_intersect_key($input, array_flip($full_columns));

        foreach ($input as $field => $value) {
            if ($value === "") {
                continue;
            }
            if (is_array($value)) {
                $query->where(function ($q) use ($field, $value) {
                    foreach ($value as $action => $data) {
                        $action = strtoupper($action);
                        if ($data === "") {
                            continue;
                        }
                        switch ($action) {
                            case "LIKE":
                                $q->orWhere(DB::raw($field), "like", "%$data%");
                                break;
                            case "IN":
                                $q->orWhereIn(DB::raw($field), $data);
                                break;
                            case "NOT IN":
                                $q->orWhereNotIn(DB::raw($field), $data);
                                break;
                            case "NULL":
                                $q->orWhereNull(DB::raw($field));
                                break;
                            case "NOT NULL":
                                $q->orWhereNotNull(DB::raw($field));
                                break;
                            case "BETWEEN":
                                $q->orWhereBetween(DB::raw($field), $value);
                                break;
                            default:
                                $q->orWhere($field, $action, $data);
                                break;
                        }
                    }
                });
            } else {
                $query->where(DB::raw($field), $value);
            }
        }

        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    /**
     * @param     $attributes
     * @param int $id
     *
     * @return mixed
     * @throws \Exception
     * @author Dai Ho
     *
     */
    public function checkUnique($attributes, $id = 0)
    {
        $attributes['deleted_at'] = null;
        $data                     = $this->getFirstWhere($attributes);

        reset($attributes);
        $attribute = key($attributes);
        if ($id) {
            if ($data && $data->id != $id) {
                throw new \Exception(Message::get("V007", Message::get($attribute)));
            }
        } else {
            if ($data) {
                throw new \Exception(Message::get("V007", Message::get($attribute)));
            }
        }
    }

    /**
     * @param int|array $primaryKey
     * @param array $input
     *
     * @return bool
     * @author Dai Ho
     *
     */
    public function deleteBy($primaryKey, $input = [])
    {
        if (!empty($input)) {
            $query = $this->model->select($primaryKey);
            if (!empty($input)) {
                foreach ($input as $field => $value) {
                    if (is_array($value)) {
                        $query->where(function ($q) use ($field, $value) {
                            foreach ($value as $action => $data) {
                                $action = strtoupper($action);
                                switch ($action) {
                                    case "IN":
                                        $q->orWhereIn(DB::raw($field), $data);
                                        break;
                                    case "NOT IN":
                                        $q->orWhereNotIn(DB::raw($field), $data);
                                        break;
                                    case "NULL":
                                        $q->orWhereNotIn(DB::raw($field), $data);
                                        break;
                                    case "NOT NULL":
                                        $q->orWhereNotIn(DB::raw($field), $data);
                                        break;
                                        defaults:
                                        $q->orWhere(DB::raw($field), $action, $data);
                                        break;
                                }
                            }
                        });
                    } else {
                        $query->where(DB::raw($field), $value);
                    }
                }
            }
            // Write Log
            $temp = $query;
            $temp = $temp->get()->toArray();
            // Log::delete($this->getTable(), $temp);

            $query->delete();
        }

        return false;
    }

    /**
     * @param        $date
     * @param string $output_format
     *
     * @return array
     */
    public function getWeekDays($date, $output_format = 'Y-m-d')
    {
        $d = explode("-W", $date);
        if (count($d) == 2) {
            $week_number = (int)$d[1];
            $year        = (int)$d[0];
        } else {
            $week_number = date("W", strtotime($date));
            $year        = date("Y", strtotime($date));
        }

        $dates = [];
        for ($day = 1; $day <= 7; $day++) {
            $dates[$day] = date($output_format, strtotime($year . "W" . $week_number . $day));
        }

        return $dates;
    }

    /**
     * @param        $date
     * @param string $output_format
     *
     * @return array
     */
    public function getQuarterMonths($date, $output_format = 'Y-m')
    {
        $time        = strtotime($date);
        $year        = date('Y', $time);
        $lastQuarter = ceil((date('m', $time)) / 3) * 3;

        return [
            1 => date($output_format, strtotime($year . "/" . ($lastQuarter - 2) . "/01")),
            2 => date($output_format, strtotime($year . "/" . ($lastQuarter - 1) . "/01")),
            3 => date($output_format, strtotime($year . "/" . ($lastQuarter) . "/01")),
        ];
    }

    /**
     * @param $code
     *
     * @return bool
     */
    public function formulaUsed($code, $option = [])
    {

        $result = $this->model->select('id')
            ->where('formula', 'like', "%[$code]%");

        if (!empty($option)) {
            $result = $result->where($option);
        }

        $result = $result->first();

        return !empty($result->id) ? true : false;
    }

    public function getFdFromDate($date, $frequency = "DAILY")
    {
        $time = strtotime($date);
        $fd   = date("Y-m-d", $time);
        switch ($frequency) {
            case "WEEKLY":
                $fd = date("Y", $time) . "-W" . date("W", $time);
                break;
            case "QUARTERLY":
                $months  = $this->getQuarterMonths($fd);
                $month   = explode("-", $months[3]);
                $quarter = $month[1] / 3;
                $fd      = $month[0] . "-Q$quarter";
                break;
            case "MONTHLY":
                $fd = date("Y-m", $time);
                break;
            case "YEARLY":
                $fd = date("Y", $time);
                break;
        }
        return $fd;
    }

    public function deleteRelated($ids)
    {
        if (!is_array($ids)) {
            $ids = [$ids];
        }
//        SELECT
//  `REFERENCED_TABLE_NAME`,                        -- Foreign key schema
//  `TABLE_NAME`,                            -- Foreign key table
//  `COLUMN_NAME`
//FROM
//  `INFORMATION_SCHEMA`.`KEY_COLUMN_USAGE`  -- Will fail if user don't have privilege
//WHERE
//  `TABLE_SCHEMA` = SCHEMA()                -- Detect current schema in USE
//  AND `REFERENCED_TABLE_NAME` = 'settings' -- Only tables with foreign keys
//ORDER BY `REFERENCED_TABLE_NAME`

        $relatedTables = DB::table('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
            ->select(['REFERENCED_TABLE_NAME', 'TABLE_NAME', 'COLUMN_NAME'])
            ->whereRaw('TABLE_SCHEMA = SCHEMA()')
            ->where('REFERENCED_TABLE_NAME', $this->getTable())
            ->orderBy('REFERENCED_TABLE_NAME')
            ->get()
            ->toArray();

        foreach ($relatedTables as $relatedTable) {
            $delTableName = $relatedTable->TABLE_NAME;
            $delFk        = $relatedTable->COLUMN_NAME;
            $dataFk       = DB::table($delTableName)->whereIn($delFk, $ids)->first();
            if (!empty($dataFk)) {
                $currentData = DB::table($this->getTable())->where('id', $ids[0])->first();
                $currentName = object_get($currentData, 'name', $this->getTable());
                $message     = Message::get("$delTableName.fk", $currentName);
                if (empty($message)) {
                    $message = Message::get("R004", "Table: '$delTableName'");
                }
                throw new \Exception($message);
            }
        }

        $this->deleteById($ids);
    }

    public function getRelatedTable()
    {
        $relatedTables = DB::table('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
            ->select(['REFERENCED_TABLE_NAME', 'TABLE_NAME', 'COLUMN_NAME'])
            ->whereRaw('TABLE_SCHEMA = SCHEMA()')
            ->where('REFERENCED_TABLE_NAME', $this->getTable())
            ->orderBy('REFERENCED_TABLE_NAME')
            ->get()
            ->toArray();

        return $relatedTables;
    }
}
