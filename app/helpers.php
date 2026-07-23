<?php

if (!function_exists('maskPhone')) {
    function maskPhone(?string $value): string
    {
        if (!$value) return '';
        $r = preg_replace('/\D/', '', substr($value, 0, 11));
        if (!$r) return '';
        if (strlen($r) <= 2) return strlen($r) ? '(' . $r : '';
        if (strlen($r) <= 6) return '(' . substr($r, 0, 2) . ') ' . substr($r, 2);
        if (strlen($r) <= 7) return '(' . substr($r, 0, 2) . ') ' . substr($r, 2, 7);
        return '(' . substr($r, 0, 2) . ') ' . substr($r, 2, 5) . '-' . substr($r, 7);
    }
}
