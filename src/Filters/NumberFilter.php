<?php
declare(strict_types=1);

namespace ExpressRequest\Filters;

use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\QueryInterface;

class NumberFilter implements FilterTypeInterface
{
    use ProcessableFilterTrait;

    const BETWEEN_STRATEGY = 'between';
    const EXACT_STRATEGY = 'exact';
    const POSSIBLE_OPERATORS = ['lt', 'lte', 'gt', 'gte'];

    /**
     * @var string
     */
    private $name;

    private $value;

    private $mode;

    private $operator = 'eq';

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
        $value = $this->setOperatorIfValueIsArray($value);

        // If value continue array, something's wrong.
        if (is_array($value)) {
            return;
        }

        $numbers = explode('..', $value);
        // If many of two dots are founded, is un processable
        if (count($numbers) > 2) {
            return;
        }

        $this->setProcessable();

        // Not have any dot, try search for only date
        if (count($numbers) === 1) {
            $this->value = $numbers[0];
            $this->mode = self::EXACT_STRATEGY;
            $this->value = 0 + $this->value;
            return;
        }

        // Founded two dots, try search between dates.
        $this->mode = self::BETWEEN_STRATEGY;
        $this->value = [$numbers[0], $numbers[1]];
    }

    public function getValue()
    {
        return $this->value;
    }

    public function process(QueryExpression $expression, string $alias, QueryInterface $query): QueryExpression
    {
        $aliasFieldName = $alias.'.'.$this->getName();
        if ($this->mode === self::EXACT_STRATEGY) {
            return $expression
                ->{$this->operator}($aliasFieldName, $this->getValue());
        }

        return $expression
            ->between($aliasFieldName, $this->getValue()[0], $this->getValue()[1]);
    }

    private function setOperatorIfValueIsArray($value)
    {
        if (
            is_array($value)
            && in_array(key($value), self::POSSIBLE_OPERATORS)
        ) {
            $this->operator = key($value);
            $value = $value[$this->operator];
        }

        return $value;
    }
}
