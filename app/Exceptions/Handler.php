<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Http\Responses\ApiResponses;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        // Handle custom service exceptions
        if ($e instanceof ServiceException) {
            return ApiResponses::error(
                $e->getMessage(),
                $e->getStatus()
            );
        }

        return parent::render($request, $e);
    }
}