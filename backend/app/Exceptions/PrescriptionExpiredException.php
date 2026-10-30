<?php

namespace App\Exceptions;

use Exception;

class PrescriptionExpiredException extends Exception
{
    protected $message = 'La prescripción no está vigente.';
}
