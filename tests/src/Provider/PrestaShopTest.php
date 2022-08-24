<?php

namespace PrestaShopCorp\OAuth2\Client\Test\Provider;

use GuzzleHttp\ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PrestaShopCorp\OAuth2\Client\Provider\PrestaShop;
use PrestaShopCorp\OAuth2\Client\Provider\PrestaShopUser;
use Psr\Http\Message\ResponseInterface;

class PrestaShopTest extends TestCase
{
    /**
     * @var PrestaShop
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new PrestaShop([
            'clientId' => 'test-client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://test-client-rediect.net',
        ]);
    }

    /**
     * @param string $responseBody
     * @param int $statusCode
     *
     * @return MockObject
     */
    private function createMockResponse(string $responseBody, int $statusCode = 200): MockObject
    {
        $response = $this->createMock(ResponseInterface::class);

        $response->method('getStatusCode')
            ->willReturn($statusCode);

        $response->method('getBody')
            ->willReturn($responseBody);

        $response->method('getHeader')
            ->with('content-type')
            ->willReturn('application/json');

        return $response;
    }

    /**
     * @test
     */
    public function itShouldGenerateAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        $query = [];

        if (\is_array($uri) && isset($uri['query'])) {
            parse_str($uri['query'], $query);
        }

        $this->assertEquals('openid offline_access', $query['scope']);
        $this->assertEquals('test-client', $query['client_id']);
        $this->assertEquals('https://test-client-rediect.net', $query['redirect_uri']);
        $this->assertArrayHasKey('response_type', $query);
    }

    /**
     * @test
     */
    public function itShouldGetBaseAccessTokenUrl(): void
    {
        $params = [];

        $url = $this->provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $path = '';
        if (\is_array($uri) && isset($uri['path'])) {
            $path = $uri['path'];
        }

        $this->assertEquals('/oauth2/token', $path);
    }

    /**
     * @test
     */
    public function itShouldGetAuthorizationUrl(): void
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $path = '';
        if (\is_array($uri) && isset($uri['path'])) {
            $path = $uri['path'];
        }

        $this->assertEquals('/oauth2/auth', $path);
    }

    /**
     * @test
     */
    public function itShouldGetAccessTokenWithAuthorizationCode(): void
    {
        $response = $this->createMockResponse(<<<JSON
{
  "access_token": "mock_access_token",
  "token_type": "bearer",
  "refresh_token": "mock_refresh_token",
  "expires_in": 7200,
  "scope": "public",
  "created_at": 1613125557
}
JSON
        );

        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->willReturn($response);


        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertLessThanOrEqual(time() + 7200, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
    }

    /**
     * @test
     */
    public function itShouldGetAccessTokenWithClientCredentials(): void
    {
        $response = $this->createMockResponse(<<<JSON
{
  "access_token": "mock_access_token",
  "token_type": "bearer",
  "expires_in": 7200,
  "scope": "public",
  "created_at": 1613125557
}
JSON
        );
        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->withConsecutive([])
            ->willReturn($response);


        $this->provider->setHttpClient($client);

        $token = $this->provider->getAccessToken('client_credentials');

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getRefreshToken());
        $this->assertLessThanOrEqual(time() + 7200, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
    }

    /**
     * @test
     */
    public function itShouldGetResourceOwner(): void
    {
        $response = $this->createMockResponse(<<<JSON
{
  "sub": "4rFN5bm2piPeHTYUFtUIwcyFKKKOp",
  "email": "john.doe@prestashop.com",
  "email_verified": "1",
  "name": "John Doe",
  "picture": "https://lh3.googleusercontent.com/a/AATXAJzK3D_K4_7YHFDQHFD3C_1ViDfRVDmQTukCyw=s96-c"
}
JSON
        );

        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->willReturn($response);

        $this->provider->setHttpClient($client);

        $accessToken = $this->createMock(AccessToken::class);
        $accessToken->method('getToken')
            ->willReturn('mock_access_token');

        $resourceOwner = $this->provider->getResourceOwner($accessToken);
        $this->assertInstanceOf(PrestaShopUser::class, $resourceOwner);
        $this->assertEquals([
            'sub' => '4rFN5bm2piPeHTYUFtUIwcyFKKKOp',
            'email' => 'john.doe@prestashop.com',
            'email_verified' => 1,
            'name' => 'John Doe',
            'picture' => 'https://lh3.googleusercontent.com/a/AATXAJzK3D_K4_7YHFDQHFD3C_1ViDfRVDmQTukCyw=s96-c',
        ], $resourceOwner->toArray());
    }

    /**
     * @test
     */
    public function itShouldHandleErrors(): void
    {
        $response = $this->createMockResponse(<<<JSON
{
  "error_description": "This is the description",
  "error": "error_name"
}
JSON
            , 403);

        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->willReturn($response);


        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage("403 - error_name: This is the description");
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @test
     */
    public function itShouldHandleEmptyErrors(): void
    {
        $response = $this->createMockResponse("{}", 403);

        $client = $this->createMock(ClientInterface::class);
        $client->method('send')
            ->willReturn($response);


        $this->provider->setHttpClient($client);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage("403 - : ");
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}