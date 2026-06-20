<?php

declare(strict_types=1);

namespace App\Exceptions;

class EfiCredentialsNotConfiguredException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Configure suas credenciais EfiBank primeiro.');
    }
}
