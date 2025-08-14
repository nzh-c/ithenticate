<?php
/**
 * Created by PhpStorm.
 * User: Ning
 */

namespace NzhC\Ithenticate\exception;


use NzhC\Ithenticate\enum\IthenticateEnum;

class IthenticateAuthException extends \RuntimeException
{
    private int $statusCode;

    public function __construct(?string $message = "" ,int $statusCode = IthenticateEnum::UNAUTHORIZED_ACCESS, Throwable $previous = null, $code = 0)
    {
        $this->statusCode = $statusCode;
        parent::__construct(($message ?? IthenticateEnum::SYSTEM_ERROR) , $code , $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}