<?php

namespace PrestaShop\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PrestaShop\OAuth2\Client\Provider\CachedFile;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;
use PrestaShop\OAuth2\Client\Provider\WellKnown;
use PrestaShop\OAuth2\Client\Test\TestCase;

class PrestaShopTest extends TestCase
{
    /**
     * @var CachedFile
     */
    private $cachedOpenIdConfiguration;

    /**
     * @var string
     */
    private $wellKnown = <<<JSON
{
    "authorization_endpoint": "https://oauth.foo.bar/oauth2/auth",
    "token_endpoint": "https://oauth.foo.bar/oauth2/token",
    "userinfo_endpoint": "https://oauth.foo.bar/userinfo",
    "jwks_uri": "https://oauth.foo.bar/.well-known/jwks.json"
}
JSON;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        // $this->cachedJwks = new CachedFile($this->getTestBaseDir() . '/var/cache/jwks.json');
        $this->cachedOpenIdConfiguration = new CachedFile(
            $this->getTestBaseDir() . '/var/cache/openid-configuration.json', 15 * 60
        );

        $this->provider = new PrestaShop([
            'clientId' => 'test-client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://test-client-redirect.net',
            'cachedWellKnown' => $this->cachedOpenIdConfiguration,
            'uiLocales' => ['fr-CA', 'en'],
            'acrValues' => ['prompt:login'],
        ]);

        $this->wellKnownResponse = $this->createMockResponse($this->wellKnown);
        $this->cachedOpenIdConfiguration->clear();
        $this->initHttpClient();
    }

    /**
     * @test
     */
    public function itShouldNotFailIfCachedFileNotConfigured()
    {
        $this->provider = new PrestaShop([
            'clientId' => 'test-client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://test-client-redirect.net',
            // 'cachedWellKnown' => $this->cachedOpenIdConfiguration,
            'uiLocales' => ['fr-CA', 'en'],
            'acrValues' => ['prompt:login'],
        ]);
        $this->wellKnownResponse = $this->createMockResponse($this->wellKnown);
        $this->initHttpClient();

        $this->assertInstanceOf(WellKnown::class, $this->provider->getWellKnown());

        $this->assertFalse(file_exists($this->cachedOpenIdConfiguration->getFilename()));
    }

    /**
     * @test
     */
    public function itShouldStoreCachedOpenIdConfiguration()
    {
        $this->assertFalse(file_exists($this->cachedOpenIdConfiguration->getFilename()));

        $this->assertInstanceOf(WellKnown::class, $this->provider->getWellKnown());

        $this->assertTrue(file_exists($this->cachedOpenIdConfiguration->getFilename()));
    }

    /**
     * @test
     */
    public function itShouldRefreshCachedOpenIdConfiguration()
    {
        $this->cachedOpenIdConfiguration = new CachedFile(
            $this->getTestBaseDir() . '/var/cache/openid-configuration.json', 1
        );

        $this->provider = new PrestaShop([
            'clientId' => 'test-client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://test-client-redirect.net',
            'cachedWellKnown' => $this->cachedOpenIdConfiguration,
            'uiLocales' => ['fr-CA', 'en'],
            'acrValues' => ['prompt:login'],
        ]);
        $this->cachedOpenIdConfiguration->clear();
        $this->wellKnownResponse = $this->createMockResponse($this->wellKnown);
        $this->initHttpClient();

        $openIdConfiguration = $this->provider->getWellKnown();

        $this->assertFalse($this->cachedOpenIdConfiguration->isExpired());
        $this->assertInstanceOf(WellKnown::class, $openIdConfiguration);
        $this->assertEquals('https://oauth.foo.bar/oauth2/auth', $openIdConfiguration->authorization_endpoint);

        usleep(2000000);

        $this->assertTrue($this->cachedOpenIdConfiguration->isExpired());

        $this->wellKnownResponse = $this->createMockResponse(<<<JSON
{
    "authorization_endpoint": "https://oauth-refreshed.foo.bar/oauth2/auth",
    "token_endpoint": "https://oauth-refreshed.foo.bar/oauth2/token",
    "userinfo_endpoint": "https://oauth-refreshed.foo.bar/userinfo",
    "jwks_uri": "https://oauth-refreshed.prestashop.com/.well-known/jwks.json"
}
JSON
        );

        $openIdConfiguration = $this->provider->getWellKnown();

        $this->assertInstanceOf(WellKnown::class, $openIdConfiguration);
        $this->assertEquals('https://oauth-refreshed.foo.bar/oauth2/auth', $openIdConfiguration->authorization_endpoint);
    }

    /**
     * @test
     */
    public function itShouldGenerateAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        $query = [];

        if (\is_array($uri) && isset($uri['query'])) {
            parse_str($uri['query'], $query);
        }

        $this->assertEquals('openid offline_access', $query['scope']);
        $this->assertEquals('test-client', $query['client_id']);
        $this->assertEquals('https://test-client-redirect.net', $query['redirect_uri']);
        $this->assertEquals('fr-CA en', $query['ui_locales']);
        $this->assertEquals('prompt:login', $query['acr_values']);
        $this->assertArrayHasKey('response_type', $query);
    }

    /**
     * @test
     */
    public function itShouldGetBaseAccessTokenUrl()
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
    public function itShouldGetAuthorizationUrl()
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
    public function itShouldGetAccessTokenWithAuthorizationCode()
    {
        $this->accessTokenResponse = $this->createMockResponse(<<<JSON
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

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertEquals('mock_refresh_token', $token->getRefreshToken());
        $this->assertLessThanOrEqual(time() + 7200, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
    }

    /**
     * @test
     */
    public function itShouldGetAccessTokenWithClientCredentials()
    {
        $this->accessTokenResponse = $this->createMockResponse(<<<JSON
{
  "access_token": "mock_access_token",
  "token_type": "bearer",
  "expires_in": 7200,
  "scope": "public",
  "created_at": 1613125557
}
JSON
        );

        $token = $this->provider->getAccessToken('client_credentials');

        $this->assertEquals('mock_access_token', $token->getToken());
        $this->assertNull($token->getRefreshToken());
        $this->assertLessThanOrEqual(time() + 7200, $token->getExpires());
        $this->assertGreaterThanOrEqual(time(), $token->getExpires());
    }

    /**
     * @test
     */
    public function itShouldGetResourceOwner()
    {
        $this->resourceOwnerResponse = $this->createMockResponse(<<<JSON
{
  "sub": "4rFN5bm2piPeHTYUFtUIwcyFKKKOp",
  "email": "john.doe@prestashop.com",
  "email_verified": "1",
  "name": "John Doe",
  "picture": "https://lh3.googleusercontent.com/a/AATXAJzK3D_K4_7YHFDQHFD3C_1ViDfRVDmQTukCyw=s96-c"
}
JSON
        );

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
    public function itShouldHandleErrors()
    {
        $this->accessTokenResponse = $this->createMockResponse(<<<JSON
{
  "error_description": "This is the description",
  "error": "error_name"
}
JSON
            , 403);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('403 - error_name: This is the description');
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @test
     */
    public function itShouldHandleEmptyErrors()
    {
        $this->accessTokenResponse = $this->createMockResponse('{}', 403);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('403 - : ');
        $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
