<?php
/**
 * Created by PhpStorm.
 * User: Marquinho
 * Date: 25/03/2020
 * Time: 23:35
 */

namespace ExpressRequest\Filters;


use Cake\Database\Expression\QueryExpression;

class NullFilter implements FilterTypeInterface
{
    const IS_STRATEGY = 'is';
    const NOT_STRATEGY = 'not';

    use ProcessableFilterTrait;

    private $value;

    private $name;

    private $mode;

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
        if ($value === 'null') {
            $value = ['is' => $value];
        }

        if (!is_array($value)) {
            return;
        }

        $this->mode = key($value);

        if (!in_array($this->mode, [self::IS_STRATEGY, self::NOT_STRATEGY])) {
            $this->setCantProcess();
            return;
        }

        $this->setProcessable();
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function process(QueryExpression $expression, string $alias): QueryExpression
    {
        $fieldName = $alias.'.'.$this->getName();
        if ($this->mode !== self::IS_STRATEGY) {
            return $expression->isNull($fieldName);
        }
        return $expression->isNull($fieldName);
    }
}