<?php

namespace App\Domain\Workshops\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WorkshopStaffNotFound extends NotFoundHttpException
{
    public function __construct()
    {
        parent::__construct('Workshop staff membership not found.');
    }
}
