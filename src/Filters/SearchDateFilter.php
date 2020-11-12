<?php
declare(strict_types=1);

namespace ExpressRequest\Filters;

use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\QueryInterface;

require __DIR__.'/../functions.php';

class SearchDateFilter implements FilterTypeInterface
{
    use ProcessableFilterTrait;

    const BETWEEN_STRATEGY = 'between';
    const EXACT_STRATEGY = 'exact';
    const POSSIBLE_OPERATORS = ['lt', 'lte', 'gt', 'gte'];

    /**
     * @var string
     */
    protected $name;

    protected $value;

    protected $mode;

    protected $operator = 'eq';

    protected $firstOperator = 'gte';

    protected $lastOperator = 'lte';

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
        if (is_string($value)) {
            $this->setPeriodByParseString($value);
            return;
        }

        if (is_array($value)) {
            $this->setDateByArray($value);
            return;
        }

        $value = $this->setOperatorIfValueIsArray($value);

        // If value continue array, something's wrong.
        if (is_array($value)) {
            return;
        }

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
            ->{$this->firstOperator}($aliasFieldName, $this->getValue()[0])
            ->{$this->lastOperator}($aliasFieldName, $this->getValue()[1]);
    }

    private function setOneDateSearch(string $date): void
    {
        $datetime = $this->createDateFromString($date);

        if (is_null($datetime)) {
            $this->setCantProcess();
            return;
        }

        $this->value = $datetime;
    }

    private function setTwoDateSearch(string $dateOne, string $dateTwo): void
    {
        $datetimeOne = $this->createDateFromString($dateOne);
        $datetimeTwo = $this->createDateFromString($dateTwo);

        if (is_null($datetimeOne) || is_null($datetimeTwo)) {
            $this->setCantProcess();
            return;
        }

        $this->mode = self::BETWEEN_STRATEGY;
        $this->value = [$datetimeOne, $datetimeTwo];
    }

    /**
     * @param string $date
     * @return \DateTime|null
     */
    protected function createDateFromString(string $date): ?\DateTime
    {
        $date = str_replace('-', '', $date);
        if (matchExp('/^[12][0-9]{3}$/', $date)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $date.'-01-01 00:00:00');
        }

        if (matchExp('/^([12]\d{3}(01|02|03|04|05|06|07|08|09|10|11|12))$/', $date)) {
            return \DateTime::createFromFormat('Ym-d H:i:s', $date.'-01 00:00:00');
        }

        if (matchExp('/^([12]\d{3}(01|02|03|04|05|06|07|08|09|10|11|12)(0[1-9]|[12]\d|3[01]))$/', $date)) {
            return \DateTime::createFromFormat('Ymd H:i:s', $date.' 00:00:00');
        }

        if (matchExp('/([12]\d{3}-(01|02|03|04|05|06|07|08|09|10|11|12)-(0[1-9]|[12]\d|3[01]))/', $date)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $date.' 00:00:00');
        }

        return null;
    }

    private function setOperatorIfValueIsArray($value, string $propertyName = 'operator'): bool
    {
        if (
            is_array($value)
            && in_array(key($value), self::POSSIBLE_OPERATORS)
        ) {
            $this->{$propertyName} = key($value);
            return true;
        }

        return false;
    }

    /**
     * By two periods date=2020-03..2020-03
     * By great than equal date[gte]=2020-03
     * By equal date=2020-03
     * By great than equal and less equal date=2020-03..2020-09
     * This is example params request.
     * @param string $value
     */
    private function setPeriodByParseString(string $value)
    {
        $dates = explode('..', $value);
        // If many of two dots are founded, is un processable
        if (count($dates) > 2) {
            return;
        }

        $this->setProcessable();

        // Not have any dot, try search for only date
        if (count($dates) === 1) {
            $this->setOneDateSearch($dates[0]);
            $this->mode = self::EXACT_STRATEGY;
            return;
        }

        // Founded two dots, try search between dates.
        $this->setTwoDateSearch($dates[0], $dates[1]);
    }

    /**
     * date[lte]=2020-03&date[gte]=2020-03
     * This is example params request.
     * @param array $values
     */
    private function setDateByArray(array $values)
    {
        $this->setProcessable();
        $count = count($values);

        if ($count <= 0 || $count > 2) {
            $this->setCantProcess();
            return;
        }

        if ($count === 1 && $this->setOperatorIfValueIsArray($values)) {
            $this->setOneDateSearch(current($values));
            return;
        }

        $array = array_chunk($values, 1, true);
        if (
            $this->setOperatorIfValueIsArray($array[0], 'firstOperator')
            && $this->setOperatorIfValueIsArray($array[1], 'lastOperator')
        ) {
            $this->setTwoDateSearch(current($array[0]), current($array[1]));
            return;
        }

        $this->setCantProcess();
    }
}
