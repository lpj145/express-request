<?php

if (!function_exists('matchExp')) {
    function matchExp(string $exp, string $value): bool
    {
        return (bool)preg_match($exp, $value);
    }
}

if (!function_exists('isEnabled')) {
    /**
     * @see https://www.php.net/manual/pt_BR/function.is-bool.php#124179
     * @param $variable
     * @return false|mixed
     */
    function isEnabled($variable)
    {
        return is_null($variable) ? false : filter_var($variable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
