<?php

namespace Rogue\Http\Controllers\Traits;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;

trait TransformsRequests
{
    /**
     * @var League\Fractal\Manager
     */
    protected $manager;

    /**
     * @var \League\Fractal\TransformerAbstract
     */
    protected $transformer;

    /**
     * Format and return a collection response.
     *
     * @param  object  $data
     * @param  int  $code
     * @param  array  $meta
     * @param  null|object  $transformer
     * @return \Illuminate\Http\JsonResponse
     */
    public function collection($data, $code = 200, $meta = [], $transformer = null)
    {
        $collection = new Collection($data, $this->setTransformer($transformer));

        return $this->transform($collection, $code, $meta);
    }

    /**
     * Format and return a single item response.
     *
     * @param  object  $data
     * @param  int  $code
     * @param  array  $meta
     * @param  null|object  $transformer
     * @return \Illuminate\Http\JsonResponse
     */
    public function item($data, $code = 200, $meta = [], $transformer = null)
    {
        $item = new Item($data, $this->setTransformer($transformer));

        return $this->transform($item, $code, $meta);
    }

    /**
     * Manage and finalize the data transformation.
     *
     * @param  \League\Fractal\Resource\Item|\League\Fractal\Resource\Collection  $data
     * @param  int  $code
     * @param  array  $meta
     * @return \Illuminate\Http\JsonResponse
     */
    public function transform($data, $code = 200, $meta = [], $include = null)
    {
        $data->setMeta($meta);

        $manager = new Manager;

        $manager->setSerializer(new DataArraySerializer);

        if (isset($include)) {
            $manager->parseIncludes($include);
        }

        $response = $manager->createData($data)->toArray();

        return response()->json($response, $code, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Set the Transformer to use otherwise use resource controller default.
     *
     * @param League\Fractal\TransformerAbstract|null $transformer
     * @return League\Fractal\TransformerAbstract
     */
    private function setTransformer($transformer = null)
    {
        if (is_null($transformer)) {
            return $this->transformer;
        }

        return $transformer;
    }

    /**
     * Format & return a paginated collection response.
     *
     * @param $query - Eloquent query
     * @return \Illuminate\Http\Response
     */
    public function paginatedCollection($query, $request, $code = 200, $meta = [], $transformer = null)
    {
        if (is_null($transformer)) {
            $transformer = $this->transformer;
        }

        $pages = (int) $request->query('limit', 20);
        $paginator = $query->paginate(min($pages, 100));

        $queryParams = array_diff_key($request->query(), array_flip(['page']));
        $paginator->appends($queryParams);

        $resource = new Collection($paginator->getCollection(), $transformer);

        $resource->setMeta($meta);

        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        $includes = null;
        if ($request->query('include')) {
            $includes = explode(',', $request->query('include'));
        }

        return $this->transform($resource, $code, [], $includes);
    }
}
