<?php

namespace App\Exceptions;

class ChecksumMatchException extends \Exception
{
    public function __construct(string $message = 'File checksum does not match')
    {
        parent::__construct($message);
    }
}
