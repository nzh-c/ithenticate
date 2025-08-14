<?php
/**
 * Created by PhpStorm.
 * User: Ning
 */

namespace NzhC\Ithenticate\exception;


use NzhC\Ithenticate\enum\IthenticateEnum;
use Throwable;

/**
 * Class IthenticateRuntimeException
 * @package NzhC\Ithenticate\exception
 */
class IthenticateRuntimeException extends \RuntimeException
{
    private int $statusCode;

    private string $otherMsg;

    public function __construct(?string $message = "" ,int $statusCode = IthenticateEnum::UNAUTHORIZED_ACCESS,
                                string $otherMsg = "",Throwable $previous = null, $code = 0)
    {
        $this->statusCode = $statusCode;
        $this->otherMsg = $otherMsg;
        parent::__construct(($message ?? IthenticateEnum::SYSTEM_ERROR) , $code , $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getOtherMsg():string
    {
        return $this->otherMsg;
    }
}