<?php

namespace Longtnt\MakeRepository\Repositories\Eloquent;

use Closure;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Longtnt\MakeRepository\Repositories\Contracts\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;

abstract class EloquentBaseRepository implements BaseRepository
{
    protected $model;

    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * Specify Model class name
     *
     * @return string
     */
    abstract public function model();

    /**
     * @return Model
     * @throws Exception
     */
    public function makeModel()
    {
        $model = app($this->model());

        if (!$model instanceof Model) {
            throw new Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Select by column
     * @param string[] $columns
     * @return mixed
     */
    public function select($columns = ['*'])
    {
        return $this->model->select($columns);
    }

    /**
     * @return mixed
     */
    public function count()
    {
        return $this->select()->count();
    }

    /**
     * @param string[] $columns
     * @return mixed
     */
    public function all($columns = ['*'])
    {
        return $this->model->all($columns);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        try {
            return $this->model->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return false;
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function findTrash($id)
    {
        return $this->model->withTrashed()->find($id);
    }

    /**
     * @param $id
     * @return bool|mixed
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            if ($id instanceof $this->model) {
                $obj = $id;
            } else {
                $obj = $this->find($id);
            }

            $response = $obj->forceDelete();

            DB::commit();

            return $response;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return false;
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        DB::beginTransaction();

        try {
            if ($id instanceof $this->model) {
                $obj = $id;
            } else {
                $obj = $this->find($id);
            }

            $response = $obj->delete();

            DB::commit();

            return $response;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return false;
        }
    }

    /**
     * @param bool $count
     * @return mixed
     */
    public function trashed($count = false)
    {
        $query = $this->model->onlyTrashed();

        return $count ? $query->count() : $query->get();
    }

    /**
     * @param $id
     * @return bool
     */
    public function restore($id)
    {
        DB::beginTransaction();

        try {
            if ($id instanceof $this->model) {
                $obj = $id;
            } else {
                $obj = $this->find($id);
            }

            $obj->restore();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return false;
        }
    }

    /**
     * Find data by field and value
     * @param $field
     * @param $value
     * @param array $columns
     * @return mixed
     */
    public function findByField($field, $value = null, $columns = ['*'])
    {
        return $this->model->where($field, '=', $value)->get($columns);
    }

    /**
     * Find data by multiple fields
     *
     * @param array $where
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhere(array $where, $columns = ['*'])
    {
        $this->applyConditions($where);

        return $this->model->get($columns);
    }

    /**
     * Find data by multiple values in one field
     *
     * @param $field
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhereIn($field, array $values, $columns = ['*'])
    {
        return $this->model->whereIn($field, $values)->get($columns);
    }

    /**
     * Find data by excluding multiple values in one field
     *
     * @param $field
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhereNotIn($field, array $values, $columns = ['*'])
    {
        return $this->model->whereNotIn($field, $values)->get($columns);
    }

    /**
     * Find data by between values in one field
     *
     * @param $field
     * @param array $values
     * @param array $columns
     *
     * @return mixed
     */
    public function findWhereBetween($field, array $values, $columns = ['*'])
    {
        return $this->model->whereBetween($field, $values)->get($columns);
    }

    /**
     * Save a new entity in repository
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function create(array $attributes)
    {
        DB::beginTransaction();

        try {
            $obj = $this->model->create($attributes);

            DB::commit();

            return $obj;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return false;
        }
    }

    public function update($id, array $attributes)
    {
        DB::beginTransaction();

        try {
            $obj = $this->model->find($id);

            $obj->fill($attributes)->save();

            DB::commit();

            return $obj;
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);

            return false;
        }
    }

    /**
     * Update or Create an entity in repository
     * first indicates the conditions for a match
     * and second is used to specify which fields to update
     * @param array $attributes
     * @param array $values
     * @return mixed
     */
    public function updateOrCreate(array $attributes, array $values = [])
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * Set the "orderBy" value of the query.
     *
     * @param mixed $column
     * @param string $direction
     *
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $this->model = $this->model->orderBy($column, $direction);

        return $this;
    }

    /**
     * Set the "limit" value of the query.
     * @param int $limit
     * @return $this
     */
    public function take($limit)
    {
        // Internally `take` is an alias to `limit`
        $this->model = $this->model->limit($limit);

        return $this;
    }

    /**
     * Set visible fields
     *
     * @param array $fields
     *
     * @return $this
     */
    public function visible(array $fields)
    {
        $this->model->setVisible($fields);

        return $this;
    }

    /**
     * Load relations
     *
     * @param array|string $relations
     *
     * @return $this
     */
    public function with($relations)
    {
        $this->model = $this->model->with($relations);

        return $this;
    }

    /**
     * Add sub-select queries to count the relations.
     *
     * @param mixed $relations
     * @return $this
     */
    public function withCount($relations)
    {
        $this->model = $this->model->withCount($relations);
        return $this;
    }

    /**
     * Load relation with closure
     *
     * @param string $relation
     * @param closure $closure
     *
     * @return $this
     */
    public function whereHas($relation, $closure)
    {
        $this->model = $this->model->whereHas($relation, $closure);

        return $this;
    }

    /**
     * Set hidden fields
     *
     * @param array $fields
     *
     * @return $this
     */
    public function hidden(array $fields)
    {
        $this->model->setHidden($fields);

        return $this;
    }

    /**
     * Retrieve first data of repository, or return new Entity
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function firstOrNew(array $attributes = [])
    {
        return $this->model->firstOrNew($attributes);
    }

    /**
     * Retrieve first data of repository, or create new Entity
     *
     * @param array $attributes
     *
     * @return mixed
     */
    public function firstOrCreate(array $attributes = [])
    {
        return $this->model->firstOrCreate($attributes);
    }

    /**
     * Retrieve all data of repository, paginated
     * @param int $limit
     * @param string[] $columns
     * @param string $method
     * @return mixed
     */
    public function paginate($limit = null, $columns = ['*'], $method = "paginate")
    {
        $limit = is_null($limit) ? 10 : $limit;
        return $this->model->{$method}($limit, $columns);
    }

    /**
     * Retrieve all data of repository, simple paginated
     *
     * @param null|int $limit
     * @param array $columns
     *
     * @return mixed
     */
    public function simplePaginate($limit = null, $columns = ['*'])
    {
        return $this->paginate($limit, $columns, "simplePaginate");
    }

    /**
     * Applies the given where conditions to the model.
     *
     * @param array $where
     * @return void
     */
    protected function applyConditions(array $where)
    {
        foreach ($where as $field => $value) {
            if (is_array($value)) {
                list($field, $condition, $val) = $value;
                $this->model = $this->model->where($field, $condition, $val);
            } else {
                $this->model = $this->model->where($field, '=', $value);
            }
        }
    }
}
