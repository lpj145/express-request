<?php
declare(strict_types=1);

namespace ExpressRequest\Filters;

use Cake\Database\Expression\QueryExpression;

class SearchFilter implements FilterTypeInterface
{
    use ProcessableFilterTrait;

    const PARTIAL_STRATEGY = 'partial';
    const START_STRATEGY = 'start';
    const END_STRATEGY = 'end';
    const EXACT_STRATEGY = 'exact';
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $mode;

    private $value;

    /**
     * SearchFilter constructor.
     * @param string $name
     * @param string $mode
     */
    public function __construct(string $name, string $mode = self::EXACT_STRATEGY)
    {
        $this->name = $name;
        $this->mode = $mode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setValue($value)
    {
        $this->setProcessable();
        switch ($this->mode) {
            case self::PARTIAL_STRATEGY:
                $value = '%'.$value.'%';
                break;
            case self::START_STRATEGY:
                $value = $value.'%';
                break;
            case self::END_STRATEGY:
                $value = '%'.$value;
                break;
            case self::EXACT_STRATEGY:
                break;
            default:
                $this->setCantProcess();
                break;
        }
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function process(QueryExpression $expression, string $alias): QueryExpression
    {
        $fieldName = $alias.'.'.$this->getName();
        if ($this->mode !== self::EXACT_STRATEGY) {
            return $expression->like($fieldName, $this->getValue());
        }
        return $expression->eq($fieldName, $this->getValue());
    }
}
