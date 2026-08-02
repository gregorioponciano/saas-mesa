<?php

if (! function_exists('maskPhone')) {
    function maskPhone(?string $value): string
    {
        if (! $value) {
            return '';
        }
        $r = preg_replace('/\D/', '', substr($value, 0, 11));
        if (! $r) {
            return '';
        }
        if (strlen($r) <= 2) {
            return strlen($r) ? '('.$r : '';
        }
        if (strlen($r) <= 6) {
            return '('.substr($r, 0, 2).') '.substr($r, 2);
        }
        if (strlen($r) <= 7) {
            return '('.substr($r, 0, 2).') '.substr($r, 2, 7);
        }

        return '('.substr($r, 0, 2).') '.substr($r, 2, 5).'-'.substr($r, 7);
    }
}

if (! function_exists('isValidCpf')) {
    function isValidCpf(?string $value): bool
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;

            for ($i = 0; $i < $t; $i++) {
                $sum += ((int) $cpf[$i]) * (($t + 1) - $i);
            }

            $digit = ((10 * $sum) % 11) % 10;

            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }
}

if (! function_exists('maskCpf')) {
    function maskCpf(?string $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) <= 3) {
            return $digits;
        }

        if (strlen($digits) <= 6) {
            return preg_replace('/(\d{3})(\d+)/', '$1.$2', $digits);
        }

        if (strlen($digits) <= 9) {
            return preg_replace('/(\d{3})(\d{3})(\d+)/', '$1.$2.$3', $digits);
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{1,2})/', '$1.$2.$3-$4', $digits);
    }
}
