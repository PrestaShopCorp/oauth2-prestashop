<?php

namespace PrestaShop\OAuth2\Client\Test;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;

class TestCase extends \PHPUnit\Framework\TestCase
{
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
}
