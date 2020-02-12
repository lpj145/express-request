<?php
declare(strict_types=1);

namespace ExpressRequest\Types;

use ExpressRequest\Filters\FilterTypeInterface;

class FiltersCollection
{
    private $filters = [];

    public function __construct(array $filters)
    {
        array_walk($filters, function($filter){
            if (!$filter instanceof FilterTypeInterface) {
                throw new \ErrorException('The filter: '.print_r($filter, true).' cannot a FilterTypeInterface expected');
            }
        });

        $this->filters = $filters;
    }

    public function addFilter(FilterTypeInterface $filterType)
    {
        $this->filters[] = $filterType;
        return $this;
    }

    public function getFilterNames(): array
    {
        return array_map(function(FilterTypeInterface $filter){
            return $filter->getName();
        }, $this->filters);
    }

    /**
     * @return FilterTypeInterface[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    public function isEmpty()
    {
        return empty($this->filters);
    }

    public function count()
    {
        return count($this->filters);
    }

    public function each(callable $fun)
    {
        array_walk($this->filters, $fun);
    }

    public function eachProcessable(callable $fun): void
    {
        $processableFilters = array_filter($this->filters, function(FilterTypeInterface $filter){
            return $filter->isProcessable();
        });
        array_walk($processableFilters, $fun);
    }
}
