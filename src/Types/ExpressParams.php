<?php
declare(strict_types=1);

namespace ExpressRequest\Types;

class ExpressParams
{
    private $alias = '';

    private $fields = [];

    private $limit = 0;

    private $orderAsc = [];

    private $orderDesc = [];

    private $page = 1;

    private $nested = [];

    private $filtersCollections;

    public function setAlias(string $alias): void
    {
        $this->alias = $alias;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function hasFields()
    {
        return !empty($this->fields);
    }

    /**
     * @param array $fields
     * @throws \ErrorException
     */
    public function setFields(array $fields): void
    {
        $this->fields = $this->setAliasOnValues($fields);
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limits
     */
    public function setLimit(int $limits): void
    {
        $this->limit = $limits;
    }

    /**
     * @return array
     */
    public function getOrderAsc(): array
    {
        return $this->orderAsc;
    }

    /**
     * @param array $orderAsc
     */
    public function setOrderAsc(array $orderAsc): void
    {
        $this->orderAsc = $orderAsc;
    }

    /**
     * @return array
     */
    public function getOrderDesc(): array
    {
        return $this->orderDesc;
    }

    /**
     * @param array $orderDesc
     * @throws \ErrorException
     */
    public function setOrderDesc(array $orderDesc): void
    {
        $this->orderDesc = $this->setAliasOnKeys($orderDesc);
    }

    /**
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @param int $page
     */
    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function hasNested(): bool
    {
        return !empty($this->nested);
    }

    /**
     * @return array
     */
    public function getNested(): array
    {
        return $this->nested;
    }

    /**
     * @param array $nested
     */
    public function setNested(array $nested): void
    {
        $this->nested = $nested;
    }

    public function setFilters(FiltersCollection $collection): void
    {
        $this->filtersCollections = $collection;
    }

    public function getFilters(): FiltersCollection
    {
        return $this->filtersCollections;
    }

    /**
     * @param array $items
     * @return array
     * @throws \ErrorException
     */
    protected function setAliasOnValues(array $items): array
    {
        if (empty($this->alias)) {
            throw new \ErrorException('ExpressRequest: Alias is empty.');
        }
        return array_map(function($item) {
            return $this->alias.'.'.$item;
        }, $items);
    }

    /**
     * @param array $items
     * @return array
     * @throws \ErrorException
     */
    protected function setAliasOnKeys(array $items)
    {
        if (empty($this->alias)) {
            throw new \ErrorException('ExpressRequest: Alias is empty.');
        }

        return array_map(function($fieldName){
            return $this->alias.'.'.$fieldName;
        }, array_keys($items));
    }
}
