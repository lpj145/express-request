<?php
declare(strict_types=1);

namespace ExpressRequest\Controller\Component;


use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Datasource\Paginator;
use Cake\Http\ServerRequest;
use Cake\ORM\Query;
use ExpressRequest\ExpressCollection;
use ExpressRequest\ExpressRepositoryInterface;
use ExpressRequest\FilterRepositoryService;
use ExpressRequest\Filters\FilterTypeInterface;
use ExpressRequest\FunctionalClosure;
use ExpressRequest\Types\ExpressConfig;
use ExpressRequest\Types\ExpressParams;
use ExpressRequest\Types\FiltersCollection;
use Psr\Http\Message\ResponseInterface;


/**
 * ExpressParams component
 */
class ExpressRequestComponent extends Component
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
        'fullUrl' => true,
        'cache' => null,
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
    ): ExpressCollection
    {
        if (is_null($finder)) {
            $finder = 'query';
        }

        if (!method_exists($repository, $finder)) {
            throw new \ErrorException(
                sprintf('ExpressRequest: finder \'%s\' not exist on model: %s', $finder, get_class($repository))
            );
        }

        // force controller uses current request context
        $this->getController()->setRequest($request);

        $expressConfig = ExpressConfig::factory($this);
        $md5UrlPath = md5($request->getUri()->getPath().'/'.$request->getUri()->getQuery());
        $paginator = new Paginator();
        $params = $request->getQueryParams();
        $filterableCollection = $repository->getFilterable();
        $repositoryQuery = call_user_func([$repository, $finder], $arg);

        if (!$repositoryQuery instanceof Query) {
            throw new \ErrorException('ExpressRequest: find: \''.$finder.'\' not return Cake\ORM\Query object.');
        }

        // Reconfigure some config by request query expression.
        $expressConfig->reconfigure($params);

        if (empty($params)) {
            return new ExpressCollection(
                $repositoryQuery,
                $expressConfig,
                $this->getController(),
                $paginator
            );
        }

        $expressParams = $this->composeExpressParams(
            $repository,
            $md5UrlPath,
            $params,
            $filterableCollection,
            $expressConfig
        );

        $query = $this->processSearch(
            $repositoryQuery,
            $expressParams
        );

        return new ExpressCollection(
            $query,
            $expressConfig,
            $this->getController(),
            $paginator
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

    protected function composeExpressParams(
        ExpressRepositoryInterface $repository,
        string $urlPath,
        array $params,
        FiltersCollection $filterableCollection,
        ExpressConfig $config
    )
    {
        $cachedExpressParams = null;
        if ($config->canCache()) {
            $cachedExpressParams = Cache::read('express.queries.'.$urlPath, $config->getCacheConfig());
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

        $this->matchRun($this->getReserved('sort'), $params, 'setSortOfItems', $expressParams);
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

        $config->canCache() && Cache::write('express.queries.'.$urlPath, $expressParams, $config->getCacheConfig());

        return $expressParams;
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
