<?php

if (!function_exists('matchExp')) {
    function matchExp(string $exp, string $value): bool
    {
        return (bool)preg_match($exp, $value);
    }
}
