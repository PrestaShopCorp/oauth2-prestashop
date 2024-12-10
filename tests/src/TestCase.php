<?php

namespace PrestaShop\OAuth2\Client\Test;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Utils;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PrestaShop
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResponseInterface|(ResponseInterface&\PHPUnit_Framework_MockObject_MockObject)
     */
    protected $wellKnownResponse;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResponseInterface|(ResponseInterface&\PHPUnit_Framework_MockObject_MockObject)
     */
    protected $accessTokenResponse;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResponseInterface|(ResponseInterface&\PHPUnit_Framework_MockObject_MockObject)
     */
    protected $resourceOwnerResponse;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|(\PHPUnit_Framework_MockObject_MockObject&ResponseInterface)|ResponseInterface
     */
    protected $jwksResponse;

    /**
     * @param $responseBody
     * @param $statusCode
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|ResponseInterface|(ResponseInterface&\PHPUnit_Framework_MockObject_MockObject)
     */
    protected function createMockResponse($responseBody, $statusCode = 200)
    {
        $response = $this->createMock(ResponseInterface::class);

        $response->method('getStatusCode')
            ->willReturn($statusCode);

        $response->method('getBody')
            ->willReturn(Utils::streamFor($responseBody));

        $response->method('getHeader')
            ->with('content-type')
            ->willReturn(['application/json']);

        return $response;
    }

    /**
     * @return void
     */
    protected function initHttpClient()
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->willReturnCallback(function ($request) {
                /** @var RequestInterface $request */
                if (preg_match('/jwks\.json$/', $request->getUri())) {
                    return $this->jwksResponse;
                }
                if (preg_match('/openid\-configuration/', $request->getUri())) {
                    return $this->wellKnownResponse;
                }
                if (preg_match('/oauth2\/token/', $request->getUri())) {
                    return $this->accessTokenResponse;
                }
                if (preg_match('/userinfo/', $request->getUri())) {
                    return $this->resourceOwnerResponse;
                }
            });
        $this->provider->setHttpClient($client);
    }

    /**
     * @return string
     */
    protected function getTestBaseDir()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..';
    }
}
