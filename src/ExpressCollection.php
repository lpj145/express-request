<?php
declare(strict_types=1);
namespace ExpressRequest;

use Cake\Datasource\Paginator;
use Cake\ORM\Query;

class ExpressCollection implements \IteratorAggregate
{
    private Query $query;
    private ?Paginator $paginator;
    private int $page;
    private int $limit;
    private int $maxSize;
    private array $meta = [];

    public function __construct(
        Query $query,
        ?Paginator $paginator = null,
        int $page = 0,
        int $limit = 0,
        int $maxSize = 0,
        array $meta = []
    )
    {
        $this->query = $query;
        $this->paginator = $paginator;
        $this->page = $page;
        $this->limit = $limit;
        $this->maxSize = $maxSize;
        $this->meta = $meta;
    }

    public function getIterator()
    {
        if (is_null($this->paginator)) {
            return new \ArrayIterator($this->getQuery()->all());
        }

        $data = $this->paginator->paginate(
            $this->getQuery(),
            [
                'page' => $this->page,
                'limit' => $this->limit,
            ],
            [
                'maxLimit' => $this->maxSize
            ]
        );

        return new \ArrayIterator([
            'data' => $data,
            'meta' => $this->meta
        ]);
    }

    public function getQuery(): ?Query
    {
        return $this->query;
    }
}