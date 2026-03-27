<?php

namespace App\Exceptions;

use Exception;

class ServiceException extends Exception
{
    protected $status;

    public function __construct($message, $status = 400)
    {
        parent::__construct($message,$status);
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }
    
}
