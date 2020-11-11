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
        $this->mode = self::BETWEEN_STRATEGY;
        $this->setTwoDateSearch($dates[0], $dates[1]);
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
            ->gte($aliasFieldName, $this->getValue()[0])
            ->lte($aliasFieldName, $this->getValue()[1]);
    }

    private function setOneDateSearch(string $date)
    {
        $datetime = $this->createDateFromString($date);

        if (is_null($datetime)) {
            $this->setCantProcess();
            return;
        }

        $this->value = $datetime;
    }

    private function setTwoDateSearch(string $dateOne, string $dateTwo)
    {
        $datetimeOne = $this->createDateFromString($dateOne);
        $datetimeTwo = $this->createDateFromString($dateTwo);

        if (is_null($datetimeOne) || is_null($datetimeTwo)) {
            $this->setCantProcess();
            return;
        }

        $this->value = [$datetimeOne, $datetimeTwo];
    }

    /**
     * @param string $date
     * @return \DateTime|null
     */
    protected function createDateFromString(string $date): ?\DateTime
    {
        if (matchExp('/^[12][0-9]{3}$/', $date)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $date.'-01-01 00:00:00');
        }

        if (matchExp('/([12]\d{3}(01|02|03|04|05|06|07|08|09|10|11|12))/', $date)) {
            return \DateTime::createFromFormat('Ym-d H:i:s', $date.'-01 00:00:00');
        }

        if (matchExp('/([12]\d{3}-(01|02|03|04|05|06|07|08|09|10|11|12))/', $date)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $date.'-01 00:00:00');
        }

        if (matchExp('/([12]\d{3}(01|02|03|04|05|06|07|08|09|10|11|12)(0[1-9]|[12]\d|3[01]))/', $date)) {
            return \DateTime::createFromFormat('Ymd H:i:s', $date.' 00:00:00');
        }

        if (matchExp('/([12]\d{3}-(01|02|03|04|05|06|07|08|09|10|11|12)-(0[1-9]|[12]\d|3[01]))/', $date)) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $date.' 00:00:00');
        }

        return null;
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
