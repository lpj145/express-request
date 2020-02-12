<?php
namespace ExpressRequest\Filters;

use Cake\Database\Expression\QueryExpression;

interface FilterTypeInterface
{
    public function getName(): string;

    public function setValue($value);

    public function getValue();

    /**
     * If any value can be casted from argument, don't do anything.
     * @return bool
     */
    public function isProcessable(): bool;

    /**
     * Process expression based on filter.
     * @param QueryExpression $expression
     * @param string $alias
     * @return QueryExpression
     */
    public function process(QueryExpression $expression, string $alias): QueryExpression;
}
