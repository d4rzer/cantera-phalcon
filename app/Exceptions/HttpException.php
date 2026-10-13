<?php
namespace App\Exceptions;
class HttpException extends \RuntimeException
{
    public function __construct(string $message, public int $status = 422, public array $fields = []) { parent::__construct($message); }
}
