<?php
declare(strict_types=1);
namespace ExpressRequest;

use Cake\Controller\Controller;
use Cake\Datasource\Paginator;
use Cake\ORM\Query;
use Cake\Routing\Router;
use ExpressRequest\Types\ExpressConfig;

class ExpressCollection implements \IteratorAggregate, \JsonSerializable
{
    private Query $query;
    private ?Paginator $paginator;
    private ExpressConfig $config;
    private Controller $controller;

    public function __construct(
        Query $query,
        ExpressConfig $config,
        Controller $controller,
        ?Paginator $paginator = null
    )
    {
        $this->query = $query;
        $this->paginator = $paginator;
        $this->config = $config;
        $this->controller = $controller;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }

    public function getItems(): \Cake\Datasource\ResultSetInterface
    {
        if ($this->config->isToLimit()) {
            $this->query->limit(
                $this->config->getSize()
            );
        }
        return $this->query->all();
    }

    public function toArray(): array
    {
        if (!$this->config->getPagination() || is_null($this->paginator)) {
            return $this->getItems()->toArray();
        }

        $data = $this->paginator->paginate(
            $this->query,
            [
                'page' => $this->config->getCurrentPage(),
                'limit' => $this->config->getSize(),
            ],
            [
                'maxLimit' => $this->config->getMaxSize()
            ]
        );

        return [
            'data' => $data,
            'meta' => $this->makeMetaPagination()
        ];
    }

    public function getRequest(): \Cake\Http\ServerRequest
    {
        return $this->controller->getRequest();
    }

    public function getController(): Controller
    {
        return $this->controller;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    protected function composePageUrl(
        int $page = 0,
        bool $withParams = true
    )
    {
        $queries = null;
        if ($withParams) {
            $queries = $this->getRequest()->getQueryParams();
            $queries[$this->config->getReserved('page')] = $page;
        }

        return Router::url([
            'controller' => $this->getController()->getName(),
            '?' => $queries,
            '_ssl' => $this->config->isSsl(),
        ], $this->config->isFullUrl());
    }

    protected function makeMetaPagination()
    {
        if (is_null($this->paginator)) {
            return [];
        }

        $paginationParams = $this->paginator->getPagingParams()[$this->query->getRepository()->getAlias()];
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
}