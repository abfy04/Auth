<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Http\Responses\ApiResponse;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        // Handle custom service exceptions
        if ($e instanceof ServiceException) {
            return ApiResponse::error(
                $e->getMessage(),
                $e->getStatus()
            );
        }
         if ($e instanceof ThrottleRequestsException) {
            return ApiResponse::error([
                    'Too many requests. Please try again later.',
                    429,
            ]);
        }
        if (!config('app.debug')) {
            return ApiResponse::error('Something went wrong', 500);
        }

        return parent::render($request, $e);
    }
}