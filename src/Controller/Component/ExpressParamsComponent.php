<?php
declare(strict_types=1);

namespace ExpressRequest\Controller\Component;

use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Datasource\Paginator;
use Cake\Datasource\RepositoryInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\Routing\Router;
use ExpressRequest\ExpressRepositoryInterface;
use ExpressRequest\FilterRepositoryService;
use ExpressRequest\Filters\FilterTypeInterface;
use ExpressRequest\FunctionalClosure;
use ExpressRequest\Types\ExpressParams;
use ExpressRequest\Types\FiltersCollection;


/**
 * ExpressParams component
 */
class ExpressParamsComponent extends Component
{
    use FunctionalClosure;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'pagination' => true,
        'maxSize' => 100,
        'size' => 20,
        'ssl' => true,
        'reserved' => [
            'size' => 'size',
            'page' => 'page',
            'props' => 'props',
            'nested' => 'nested',
            'sort' => 'sort'
        ]
    ];

    public function search(
        ServerRequest $request,
        ExpressRepositoryInterface $repository
    ): \Traversable
    {
        $paginator = new Paginator();
        $params = $request->getQueryParams();
        $filterableCollection = $repository->getFilterable();

        if (
            empty($params)
        ) {
            return $this->processPagination(
                $repository->getQuery(),
                $paginator,
                1,
                $this->getConfig('size'),
                $this->getConfig('maxSize')
            );
        }

        $expressParams = new ExpressParams();
        $expressParams->setAlias($repository->getAlias());

        $this->matchRun(
            $this->getReserved('props'),
            $params,
            'setSelectableFields',
            $repository->getSelectables(),
            $expressParams
        );

        $this->matchRun($this->getReserved('size'), $params, 'setSizeOfPage', $expressParams);
        $this->matchRun($this->getReserved('sort'), $params, 'setSortOfItems', $expressParams);
        $this->matchRun($this->getReserved('page'), $params,'setPage', $expressParams);
        $this->matchRun($this->getReserved('nested'), $params, 'setNestedData', $expressParams);

        /**
         * If not have any filters, simple don't try anything.
         */
        if (!$filterableCollection->isEmpty()) {
            $filterableFieldsFromParams = $this->filterArrayOutFromKeys(
                $params,
                $this->getReserved()
            );

            $this->setFilters(
                $filterableFieldsFromParams,
                $filterableCollection,
                $expressParams
            );
        }

        $query = $this->processSearch(
            $repository,
            $expressParams
        );

        $sizeOfPage = (bool)$expressParams->getLimit() ?
            $expressParams->getLimit() :
            $this->getConfig('size')
        ;

        return $this->processPagination(
            $query,
            $paginator,
            $expressParams->getPage(),
            $sizeOfPage,
            $this->getConfig('maxSize')
        );
    }

    /**
     * Get configured reserved keyword for prevent collision.
     * @param string|null $key
     * @return mixed
     */
    protected function getReserved(string $key = null)
    {
        if (is_null($key)) {
            return $this->getConfig('reserved');
        }
        return $this->getConfig('reserved')[$key];
    }

    /**
     * Try transform string like 'name,created'
     * to arrays can be forward for Query Builder
     * @param $attributesString
     * @param array $selectable
     * @param ExpressParams $expressParams
     */
    protected function setSelectableFields($attributesString, array $selectable, ExpressParams $expressParams)
    {
        if (!is_string($attributesString)) {
            return;
        }

        $attributes = explode(
            ',',
            $attributesString
        );

        if (empty($attributes)) {
            return;
        }

        $expressParams->setFields(
            array_intersect($attributes, $selectable)
        );
    }

    protected function setSizeOfPage($limit, ExpressParams $expressParams)
    {
        if (!is_numeric($limit)) {
            return;
        }
        $expressParams->setLimit((int)$limit);
    }

    /**
     * Try get all array on `sort` key
     * and organize all to define what is by ASC or DESC
     * `sort` param is like 'sort[name]=asc&sort[created]=desc'
     * @param $sortArray
     * @param ExpressParams $expressParams
     */
    protected function setSortOfItems($sortArray, ExpressParams $expressParams)
    {
        if (!is_array($sortArray)) {
            return;
        }

        $expressParams->setOrderAsc(
            $this->filterByValue($sortArray, 'asc')
        );

        $expressParams->setOrderDesc(
            $this->filterByValue($sortArray, 'desc')
        );
    }

    protected function setPage($page, ExpressParams $expressParams)
    {
        if (!is_numeric($page)) {
            return;
        }
        $expressParams->setPage((int)$page);
    }

    protected function setNestedData($containString, ExpressParams $expressParams)
    {
        if (!is_string($containString)) {
            return;
        }

        $contained = explode(',', $containString);

        if (empty($contained)) {
            return;
        }

        $expressParams->setNested($contained);
    }

    protected function setFilters(array $fieldsFromParams, FiltersCollection $filters, ExpressParams $expressParams)
    {
        // Filter by filter set value
        $filters->each(function(FilterTypeInterface $filterType) use($fieldsFromParams){
            if (!array_key_exists($filterType->getName(), $fieldsFromParams)) {
                return;
            }
            $filterType->setValue(
                $fieldsFromParams[$filterType->getName()]
            );
        });

        $expressParams->setFilters($filters);
    }

    protected function processSearch(
        ExpressRepositoryInterface $repository,
        ExpressParams $expressParams
    ): Query
    {
        return (new FilterRepositoryService())($repository->getQuery(), $expressParams);
    }

    protected function processPagination(
        Query $query,
        Paginator $paginator,
        int $page,
        int $limit,
        int $maxSize
    )
    {
        $data = $paginator->paginate(
            $query,
            [
                'page' => $page,
                'limit' => $limit,
                'maxLimit' => $maxSize
            ]
        );

        return new Collection([
            'data' => $data,
            'meta' => $this->organizeMetaPagination(
                $paginator->getPagingParams()[$query->getRepository()->getAlias()]
            )
        ]);
    }

    protected function organizeMetaPagination(array $paginationParams)
    {
        $meta = [
            'total' => $paginationParams['count'],
            'per_page' => $paginationParams['perPage'],
            'current_page' => $paginationParams['page'],
            'last_page' => $paginationParams['pageCount'],
            'first_page_url' => $this->composePageUrl(1),
            'next_page_url' => null,
            'last_page_url' => $this->composePageUrl($paginationParams['pageCount']),
            'prev_page_url' => null,
            'path' => $this->composePageUrl(0, false),
            'from' => $paginationParams['start'],
            'to' => $paginationParams['end'],
        ];

        if ($paginationParams['nextPage']) {
            $meta['next_page_url'] = $this->composePageUrl($meta['current_page'] + 1);
        }

        if ($paginationParams['prevPage']) {
            $meta['prev_page_url'] = $this->composePageUrl($meta['current_page'] - 1);
        }

        return $meta;
    }

    protected function composePageUrl(int $page = 0, bool $withParams = true)
    {
        $queries = null;
        if ($withParams) {
            $queries = $this->getController()->getRequest()->getQueryParams();
            $queries[$this->getReserved('page')] = $page;
        }
        return Router::url([
            'controller' => $this->getController()->getName(),
            '?' => $queries,
            '_ssl' => (bool)$this->getConfig('ssl'),
        ], true);
    }
}
