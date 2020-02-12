<?php
declare(strict_types=1);

namespace ExpressRequest;

use Cake\Database\Expression\QueryExpression;
use Cake\ORM\Query;
use ExpressRequest\Filters\FilterTypeInterface;
use ExpressRequest\Types\ExpressParams;

class FilterRepositoryService
{
    public function __invoke(Query $query, ExpressParams $expressParams): Query
    {
        if ($expressParams->hasFields()) {
            $query->select($expressParams->getFields());
        }

        $query
            ->order($expressParams->getOrderAsc())
            ->order($expressParams->getOrderDesc())
            ->contain($expressParams->getNested())
        ;

        $expression = $query->newExpr();
        $alias = $expressParams->getAlias();

        $expressParams
            ->getFilters()
            ->eachProcessable(function(FilterTypeInterface $filter) use($expression, $alias){
                $filter->process($expression, $alias);
            });

        if ($this->expressionIsEmpty($expression)) {
            return $query;
        }

        return $query->where($expression);
    }

    protected function expressionIsEmpty(QueryExpression $expression)
    {
        return !(bool)$expression->count();
    }
}
