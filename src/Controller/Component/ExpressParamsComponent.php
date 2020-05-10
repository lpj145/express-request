<?php
declare(strict_types=1);

namespace ExpressRequest\Controller\Component;

use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Datasource\Paginator;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use Cake\Routing\Router;
use ExpressRequest\ExpressRepositoryInterface;
use ExpressRequest\FilterRepositoryService;
use ExpressRequest\Filters\FilterTypeInterface;
use ExpressRequest\FunctionalClosure;
use ExpressRequest\Types\ExpressParams;
use ExpressRequest\Types\FiltersCollection;
use Psr\Http\Message\ResponseInterface;


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
        'ssl' => true, //generate routes
        'cacheConfig' => 'default',
        'cache' => true,
        'reserved' => [
            'size' => 'size',
            'page' => 'page',
            'props' => 'props',
            'nested' => 'nested',
            'sort' => 'sort'
        ]
    ];

    public function getResponse(int $status = 200): ResponseInterface
    {
        return $this->getController()
            ->getResponse()
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody(json_encode(
                $this->processSearchFromInvoke()
            ));
    }

    public function search(
        ServerRequest $request,
        ExpressRepositoryInterface $repository,
        string $finder = null,
        $arg = null
    ): \Traversable
    {
        if (is_null($finder)) {
            $finder = 'query';
        }

        if (!method_exists($repository, $finder)) {
            throw new \ErrorException(
                sprintf('ExpressRequest: finder \'%s\' not exist on model: %s', $finder, get_class($repository))
            );
        }

        $md5UrlPath = md5($request->getUri()->getPath().'/'.$request->getUri()->getQuery());
        $paginator = new Paginator();
        $params = $request->getQueryParams();
        $filterableCollection = $repository->getFilterable();
        $repositoryQuery = call_user_func([$repository, $finder], $arg);

        if (!$repositoryQuery instanceof Query) {
            throw new \ErrorException('ExpressRequest: find: \''.$finder.'\' not return Cake\ORM\Query object.');
        }

        if (
            empty($params)
        ) {
            return $this->processPagination(
                $repositoryQuery,
                $paginator,
                1,
                $this->getConfig('size'),
                $this->getConfig('maxSize')
            );
        }

        $expressParams = $this->processComposedParams(
            $repository,
            $md5UrlPath,
            $params,
            $filterableCollection,
            $this->getConfig('cache')
        );

        $query = $this->processSearch(
            $repositoryQuery,
            $expressParams
        );

        $sizeOfPage = (bool)$expressParams->getLimit() ?
            $expressParams->getLimit() :
            $this->getConfig('size')
        ;

        $noPage = $params['noPage'] ?? false;
        $noPage = (bool)$noPage;

        return $this->processPagination(
            $query,
            $paginator,
            $expressParams->getPage(),
            $sizeOfPage,
            $this->getConfig('maxSize'),
            $noPage
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
     * @throws \ErrorException
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
            array_intersect(
                array_map('strtolower', $attributes),
                array_map('strtolower', $selectable)
            )
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
     * @throws \ErrorException
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
        Query $query,
        ExpressParams $expressParams
    ): Query
    {
        return (new FilterRepositoryService())($query, $expressParams);
    }

    protected function processPagination(
        Query $query,
        Paginator $paginator,
        int $page,
        int $limit,
        int $maxSize,
        bool $noPage = false
    )
    {
        if ($noPage) {
            return new Collection(
                $query
                    ->all()
            );
        }

        $data = $paginator->paginate(
            $query,
            [
                'page' => $page,
                'limit' => $limit,
            ],
            [
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

    protected function processComposedParams(
        ExpressRepositoryInterface $repository,
        string $urlPath,
        array $params,
        FiltersCollection $filterableCollection,
        bool $canCache
    )
    {
        $cachedExpressParams = null;

        if (Configure::read('debug')) {
            $canCache = false;
        }

        if ($canCache) {
            $configCache = $this->getConfig('cacheConfig');
            $cachedExpressParams = Cache::read('express.queries.'.$urlPath, $configCache);
        }

        if (!is_null($cachedExpressParams)) {
            return $cachedExpressParams;
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

        if ($canCache) {
            Cache::write('express.queries.'.$urlPath, $expressParams, $configCache);
        }

        return $expressParams;
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

    protected function processSearchFromInvoke()
    {
        return $this->search(
            $this->getController()->getRequest(),
            $this->getModel()
        );
    }

    protected function getModel(): ExpressRepositoryInterface
    {
        return $this->getController()->loadModel();
    }
}
