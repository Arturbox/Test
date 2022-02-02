<?php


namespace App\Repositories;

use App\Constants\RepositorySettings;
use App\Exceptions\RepositoryException;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;


/**
 * Class Repository
 * @package App\Repositories
 */
abstract class Repository
{
    /**
     * @var array $meta
     */
    protected array $meta = [
        'perPage' => RepositorySettings::PAGE_SIZE,
        'columns' => RepositorySettings::COLUMNS,
        'orderType' => RepositorySettings::DESC,
        'orderBy' => RepositorySettings::DEFAULT_ORDER,
    ];

    /**
     * @var array $fillable
     */
    protected array $fillable = [];

    /**
     * @var Builder $query
     */
    public Builder $query;

    /**
     * Repository constructor.
     *
     * @throws RepositoryException
     */
    public function __construct()
    {
        $this->query();
    }

    /**
     * Specify Model class name
     *
     * @return String
     */
    abstract protected function model(): string;

    /**
     * @param array $data
     * @return Builder
     * @throws RepositoryException
     */
    protected function setMeta(array $data): Builder
    {
        $this->meta = array_merge($this->meta, $data);

        return  $this->query();
    }

    /**
     * Make query
     *
     * @return Builder
     * @throws RepositoryException
     */
    public function query(): Builder
    {
        return $this->query = $this->makeModel()->newQuery();
    }

    /**
     * Make model
     *
     * @return Model
     * @throws RepositoryException
     */
    public function makeModel(): Model
    {
        $model = app($this->model());

        if (!$model instanceof Model) {
            throw new RepositoryException(
                "Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model",
            );
        }

        return $model;
    }

    /**
     * @param array $data
     * @param Model $model
     * @param array $fillable
     * @return Model
     */
    public function fill(array $data, Model $model, array $fillable = []): Model
    {
        if (empty($fillable)) {
            $fillable = $this->fillable;
        }
        if (!empty($fillable)) {
            $model->fillable($fillable)->fill($data);
        }

        return $model;
    }

    /**
     * Return all rows from table.
     *
     * @param array $relations
     * @param array|string[] $columns
     * @return Collection
     * @throws RepositoryException
     */
    public function all(array $relations =  [], array $columns = ['*']): Collection
    {
        return $this->query()->with($relations)->get($columns);
    }

    /**
     * Save values in table.
     *
     * @param array $data
     * @param array $fillable
     * @return mixed
     * @throws RepositoryException
     */
    public function create(array $data, array $fillable = []): Model
    {
        $object = $this->fill($data, $this->makeModel(), $fillable);
        $object->save();

        return $object->fresh();
    }

    /**
     * Update values in table.
     *
     * @param array $data
     * @param Model|int|array $target
     * @param array $fillable
     * @return Model
     * @throws RepositoryException
     */
    public function update(array $data, $target, array $fillable = []): Model
    {
        try {
            $object = $this->fetch($target);
            $object = $this->fill($data, $object, $fillable);
            $object->save();

            return $object;
        }
        catch (\Exception $e){
            Storage::disk('local')->put('hubstaff.time',Carbon::now());
            return $object;
        }
    }

    /**
     * Delete row from table.
     *
     * @param Model|array|int $target
     * @@return bool|null
     * @throws \Exception
     */
    public function delete($target)
    {
        return $this->fetch($target)->delete();
    }

    /**
     * fetch object by given params
     *
     * @param Model|array|int $target
     * @return Model
     * @throws RepositoryException
     */
    public function fetch($target): Model
    {
        if (!($target instanceof Model) && is_numeric($target)) {
            $target = $this->find($target);
        }
        if (is_array($target)) {
            $array_key = array_key_first($target);
            $target = $this->findBy([$array_key => $target[$array_key]]);
        }

        return $target;
    }

    /**
     * find object by id
     *
     * @param int $id
     * @param array|string[] $columns
     * @param array $relations
     * @param bool $throwException
     * @return mixed
     * @throws RepositoryException
     */
    public function find(
        int $id,
        array $columns = ['*'],
        array $relations = [],
        bool $throwException = true
    ) {
        return $throwException
            ? $this->query()->with($relations)->findOrFail($id, $columns)
            : $this->query()->with($relations)->find($id, $columns);
    }

    /**
     * Find by column and value from table.
     *
     * @param array $credentials
     * @param array|string[] $columns
     * @param array $relations
     * @param bool $throwException
     * @return Builder|Model|object|null
     * @throws RepositoryException
     */
    public function findBy(
        array $credentials,
        array $columns = ['*'],
        array $relations = [],
        bool $throwException = true
    ) {
        return $throwException
            ? $this->query()->with($relations)->where($credentials)->firstOrFail($columns)
            : $this->query()->with($relations)->where($credentials)->first($columns);
    }

    /**
     * @param int $limit
     * @return Builder
     * @throws RepositoryException
     */
    public function limit(int $limit) : Builder
    {
        return $this->query()->limit($limit);
    }

    /**
     * check if object exists in db
     *
     * @param array $credentials
     * @return bool
     * @throws RepositoryException
     */
    public function existBy(array $credentials): bool
    {
        return $this->query()->where($credentials)->exists();
    }

    /**
     * return paginated collection sorted by given attribute using scopes
     *
     * @param array $filters
     * @param array $meta
     * @param array $relations
     * @return LengthAwarePaginator|mixed
     * @throws RepositoryException
     */
    public function sortPaginate(array $filters = [], array $relations = [], array $meta = []): LengthAwarePaginator
    {
        return $this->setMeta($meta)
            ->setFilters($filters)
            ->setTrashed($meta['trashed'] ?? false)
            ->setSearch(getVal($meta, 'search'))
            ->with(array_values($relations))
            ->orderBy($this->meta['orderBy'], $this->meta['orderType'])
            ->paginate($this->meta['perPage'], $this->meta['columns']);
    }

    /**
     * @param array $filters
     * @param array $relations
     * @param array $meta
     * @return mixed
     * @throws RepositoryException
     */
    public function get(array $filters = [], array $relations = [], array $meta = []): Collection
    {
        return $this->setMeta($meta)
            ->setFilters($filters)
            ->setTrashed($meta['trashed'] ?? false)
            ->setSearch(getVal($meta, 'search'))
            ->with(array_values($relations))
            ->get($this->meta['columns']);
    }

    /**
     * attach scope to query
     *
     * @param Builder $query
     * @param string $scope
     * @param string|array $value
     * @return mixed
     */
    public function attachScope(Builder $query, string $scope, $value): Builder
    {
        $scope = str_replace("_", "", ucwords($scope, " /_"));
        $query = $query->{$scope}($value);

        return $query;
    }

    /**
     * @param $query
     * @param $filters
     * @return mixed
     */
    public function filters(Builder $query, array $filters): Builder
    {
        array_walk($filters, function ($value, $scope) use ($query) {
            return $this->attachScope($query, $scope, $value);
        });

        return $query;
    }
}
