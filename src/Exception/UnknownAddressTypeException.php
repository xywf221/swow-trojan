<?php

namespace xywf221\Trojan\Exception;

use RuntimeException;
use Throwable;

class UnknownAddressTypeException extends RuntimeException
{
    public function __construct(int $type, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct("Unknown address type : $type", $code, $previous);
    }
}