<?php
declare(strict_types=1);

namespace ExpressRequest\Filters;

use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\QueryInterface;

class BooleanFilter implements FilterTypeInterface
{
    use ProcessableFilterTrait;

    /**
     * @var string
     */
    private $name;
    /**
     * @var boolean
     */
    private $value;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setValue($value)
    {
        if (
            $value === 1
            || $value === '1'
            || $value === 'true'
            || $value === true
        ) {
            $this->value = true;
            $this->setProcessable();
            return;
        }

        if (
            $value === 0
            || $value === '0'
            || $value === 'false'
            || $value === false
        ) {
            $this->value = false;
            $this->setProcessable();
        }
    }

    public function getValue()
    {
        return $this->value;
    }

    public function process(QueryExpression $expression, string $alias, QueryInterface $query): QueryExpression
    {
        return $this->isProcessable()
            ? $expression->eq($this->getName(), $this->getValue())
            : $expression
        ;
    }
}
