<?php

declare(strict_types=1);

namespace Tenryuubito\WizMonitorClient\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tenryuubito\WizMonitorClient\Security\NonceStore;
use Tenryuubito\WizMonitorClient\Security\RequestSigner;
use Tenryuubito\WizMonitorClient\Security\SecretProvider;
use Tenryuubito\WizMonitorClient\Status\StatusReport;
use Tenryuubito\WizMonitorClient\Status\StatusRequest;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\NormalizedParams;

final readonly class StatusEndpointMiddleware implements MiddlewareInterface
{
    public const ENDPOINT_PATH = 'wiz-monitor/status';
    private const JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

    public function __construct(
        private SecretProvider $secretProvider,
        private RequestSigner $requestSigner,
        private NonceStore $nonceStore,
        private StatusReport $statusReport,
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private Context $context,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isEndpointRequest($request)) {
            return $handler->handle($request);
        }
        $secret = $this->secretProvider->getSecret();
        if ($secret === null || $request->getMethod() !== 'POST') {
            return $this->createNotFoundResponse();
        }

        $now = (int)$this->context->getPropertyFromAspect('date', 'timestamp');
        $timestamp = $request->getHeaderLine(RequestSigner::HEADER_TIMESTAMP);
        $nonce = $request->getHeaderLine(RequestSigner::HEADER_NONCE);
        $body = (string)$request->getBody();

        if (
            !ctype_digit($timestamp)
            || !$this->requestSigner->isTimestampWithinTolerance((int)$timestamp, $now)
            || !$this->requestSigner->isValidNonce($nonce)
        ) {
            return $this->createNotFoundResponse();
        }

        $expectedSignature = $this->requestSigner->signRequest($secret, $request->getMethod(), $request->getUri()->getPath(), (int)$timestamp, $nonce, $body);
        if (
            !$this->requestSigner->matches($expectedSignature, $request->getHeaderLine(RequestSigner::HEADER_SIGNATURE))
            || !$this->nonceStore->consume($nonce)
        ) {
            return $this->createNotFoundResponse();
        }

        $responseBody = json_encode($this->statusReport->build(StatusRequest::fromJson($body)), self::JSON_FLAGS);

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Robots-Tag', 'noindex, nofollow')
            ->withHeader(RequestSigner::HEADER_TIMESTAMP, (string)$now)
            ->withHeader(RequestSigner::HEADER_SIGNATURE, $this->requestSigner->signResponse($secret, $nonce, $now, $responseBody))
            ->withBody($this->streamFactory->createStream($responseBody));
    }

    private function isEndpointRequest(ServerRequestInterface $request): bool
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        $sitePath = $normalizedParams instanceof NormalizedParams ? $normalizedParams->getSitePath() : '/';
        return $request->getUri()->getPath() === $sitePath . self::ENDPOINT_PATH;
    }

    private function createNotFoundResponse(): ResponseInterface
    {
        return $this->responseFactory->createResponse(404)
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
