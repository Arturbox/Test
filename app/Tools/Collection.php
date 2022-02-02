<?php


namespace App\Tools;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Class Collection
 * @package App\Common\Tools
 */
class Collection extends ResourceCollection
{
    /**
     * @var array
     */
    public array $pagination;

    /**
     * @var $resource
     */
    public $resource;

    /**
     * Collection constructor.
     * @param $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
        $this->pagination  = [];
        if (get_class($this->resource) === LengthAwarePaginator::class) {
            $this->pagination = $this->constructPaginate();
            $resource = $resource->getCollection();
        }
        parent::__construct($resource);
    }

    /**
     * @return array
     */
    public function constructPaginate()
    {
        return [
            'total' => $this->resource->total(),
            'count' => $this->resource->count(),
            'per_page' => $this->resource->perPage(),
            'current_page' => $this->resource->currentPage(),
            'total_pages' => $this->resource->lastPage()
        ];
    }

    /**
     * @return array
     */
    public function makeResponse(): array
    {
        return [
            'data' => $this->collection,
            'pagination' => $this->pagination
        ];
    }
}
