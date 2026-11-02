<?php

declare(strict_types=1);

namespace Src\ExceptionHandler\Http;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use SuperKernel\Attribute\Autowired;
use SuperKernel\Contract\StdoutLoggerInterface;
use SuperKernel\HttpMessage\Stream\StringStream;
use SuperKernel\HttpServer\Attribute\HttpExceptionHandler;
use SuperKernel\HttpServer\Contract\HttpExceptionHandlerInterface;
use SuperKernel\HttpServer\Contract\StoppableHttpExceptionHandlerInterface;
use SuperKernel\HttpServer\Exception\HttpException;
use Throwable;

use function json_encode;

use const JSON_THROW_ON_ERROR;

#[HttpExceptionHandler(exception: HttpException::class)]
final class HttpServerExceptionHandler implements HttpExceptionHandlerInterface, StoppableHttpExceptionHandlerInterface
{
    #[Autowired]
    protected StdoutLoggerInterface $stdoutLogger;

    /** @throws JsonException When the HTTP error message cannot be encoded */
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stdoutLogger->warning($throwable->getMessage());

        return $response
            ->withoutHeader('Content-Length')
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody(new StringStream(json_encode([
                'error' => $throwable->getMessage(),
            ], JSON_THROW_ON_ERROR)));
    }

    public function isPropagationStopped(Throwable $throwable, ResponseInterface $response): bool
    {
        return true;
    }
}
