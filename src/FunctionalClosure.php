<?php
declare(strict_types=1);

namespace ExpressRequest;

trait FunctionalClosure
{
    protected function matchRun(string $key, array $seeds, string $fun, ...$params): void
    {
        if (array_key_exists($key, $seeds)) {
            $this->{$fun}($seeds[$key], ...$params);
        }
    }

    protected function filterArrayOutFromKeys(array $items, array $possibleKeys)
    {
        return array_filter($items, function($itemKey) use($possibleKeys){
            return !in_array($itemKey, $possibleKeys);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function filterArrayByKeys(array $items, array $possibleKeys)
    {
        return array_filter($items, function($itemKey) use($possibleKeys){
            return in_array($itemKey, $possibleKeys);
        }, ARRAY_FILTER_USE_KEY);
    }

    protected function filterByValue(array $items, string $value)
    {
        return array_filter($items, function($itemValue) use($value){
            return $value === $itemValue;
        });
    }
}
