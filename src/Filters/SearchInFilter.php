<?php
declare(strict_types=1);

namespace ExpressRequest\Filters;

use Cake\Database\Expression\QueryExpression;

class SearchInFilter implements FilterTypeInterface
{
    use ProcessableFilterTrait;

    /**
     * @var string
     */
    private $name;

    private $value;

    private $operator = 'in';

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
        $value = $this->setInOperator($value);

        //If value is array, after try set operator, something's wrong.
        if (is_array($value)) {
            return;
        }

        $possibleValues = explode(',', $value);
        $this->setProcessable();
        if (count($possibleValues) > 1) {
            $this->value = $possibleValues;
            return;
        }

        $this->value = $possibleValues;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function process(QueryExpression $expression, string $alias): QueryExpression
    {
        return $expression
            ->{$this->operator}(
                $alias.'.'.$this->getName(),
                $this->getValue()
            );
    }

    private function setInOperator($value)
    {
        if (
            is_array($value)
            && in_array(key($value), ['not'])
        ) {
            $this->operator = 'notIn';
            $value = $value[key($value)];
        }

        return $value;
    }
}
