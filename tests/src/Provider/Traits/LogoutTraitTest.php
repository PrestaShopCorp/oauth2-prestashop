<?php

namespace PrestaShop\OAuth2\Client\Test\Provider\Traits;

use PrestaShop\OAuth2\Client\Provider\CachedFile;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;
use PrestaShop\OAuth2\Client\Test\TestCase;

class LogoutTraitTest extends TestCase
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
    "jwks_uri": "https://oauth.foo.bar/.well-known/jwks.json",
    "end_session_endpoint":"https://oauth.foo.bar/oauth2/sessions/logout"
}
JSON;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->cachedOpenIdConfiguration = new CachedFile(
            $this->getTestBaseDir() . '/var/cache/openid-configuration.json', 15 * 60
        );

        $this->provider = new PrestaShop([
            'clientId' => 'test-client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://test-client-redirect.net',
            'cachedWellKnown' => $this->cachedOpenIdConfiguration,
            'postLogoutCallbackUri' => 'https://test-client-redirect.net/logout?oauth2Callback',
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
    public function itShouldGenerateLogoutUrl()
    {
        $idToken = 'someRandomIdToken';

        $url = $this->provider->getLogoutUrl([
            'id_token_hint' => $idToken,
        ]);
        $uri = parse_url($url);
        $query = [];

        if (\is_array($uri) && isset($uri['query'])) {
            parse_str($uri['query'], $query);
        }

        $this->assertEquals($idToken, $query['id_token_hint']);
        $this->assertEquals('https://test-client-redirect.net/logout?oauth2Callback', $query['post_logout_redirect_uri']);
        // $this->assertEquals('fr-CA en', $query['ui_locales']);
    }

    /**
     * @test
     */
    public function itShouldGenerateLogoutUrlWithOptionalParameters()
    {
        $idToken = 'someRandomIdToken';
        $postLogoutRedirectUri = 'https://overriden-post-logout-uri.net';

        $url = $this->provider->getLogoutUrl([
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => $postLogoutRedirectUri,
        ]);
        $uri = parse_url($url);
        $query = [];

        if (\is_array($uri) && isset($uri['query'])) {
            parse_str($uri['query'], $query);
        }

        $this->assertEquals($idToken, $query['id_token_hint']);
        $this->assertEquals($postLogoutRedirectUri, $query['post_logout_redirect_uri']);
        // $this->assertEquals('fr-CA en', $query['ui_locales']);
    }

    /**
     * @test
     */
    public function itShouldGenerateLogoutUrlWithOptionalOnlyParameters()
    {
        $idToken = 'someRandomIdToken';
        $postLogoutRedirectUri = 'https://overriden-post-logout-uri.net';

        $this->provider = new PrestaShop([
            'clientId' => 'test-client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://test-client-redirect.net',
            'cachedWellKnown' => $this->cachedOpenIdConfiguration,
            // 'postLogoutCallbackUri' => 'https://test-client-redirect.net/logout?oauth2Callback',
            'uiLocales' => ['fr-CA', 'en'],
            'acrValues' => ['prompt:login'],
        ]);

        $url = $this->provider->getLogoutUrl([
            'id_token_hint' => $idToken,
            'post_logout_redirect_uri' => $postLogoutRedirectUri,
        ]);
        $uri = parse_url($url);
        $query = [];

        if (\is_array($uri) && isset($uri['query'])) {
            parse_str($uri['query'], $query);
        }

        $this->assertEquals($idToken, $query['id_token_hint']);
        $this->assertEquals($postLogoutRedirectUri, $query['post_logout_redirect_uri']);
        // $this->assertEquals('fr-CA en', $query['ui_locales']);
    }

    /**
     * @test
     */
    public function itShouldGetBaseSessionLogoutUrl()
    {
        $url = $this->provider->getBaseSessionLogoutUrl();
        $uri = parse_url($url);

        $path = '';
        if (\is_array($uri) && isset($uri['path'])) {
            $path = $uri['path'];
        }

        $this->assertEquals('/oauth2/sessions/logout', $path);
    }

    /**
     * @test
     */
    public function itShouldGetLogoutUrl()
    {
        $idToken = 'someRandomIdToken';

        $url = $this->provider->getLogoutUrl([
            'id_token_hint' => $idToken,
        ]);
        $uri = parse_url($url);

        $path = '';
        if (\is_array($uri) && isset($uri['path'])) {
            $path = $uri['path'];
        }

        $this->assertEquals('/oauth2/sessions/logout', $path);
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionWhenIdTokenIsMissing()
    {
        $this->expectException(\Exception::class);

        $this->expectExceptionMessage('Missing id_token_hint required parameter');

        $this->provider->getLogoutUrl([
            // 'id_token_hint' => $idToken
        ]);
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionWhenPostLogoutCallbackUriIsMissing()
    {
        $idToken = 'someRandomIdToken';

        $this->provider = new PrestaShop([
            'clientId' => 'test-client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://test-client-redirect.net',
            'cachedWellKnown' => $this->cachedOpenIdConfiguration,
            // 'postLogoutCallbackUri' => 'https://test-client-redirect.net/logout?oauth2Callback',
            'uiLocales' => ['fr-CA', 'en'],
            'acrValues' => ['prompt:login'],
        ]);

        $this->expectException(\Exception::class);

        $this->expectExceptionMessage('Missing post_logout_redirect_uri required parameter');

        $this->provider->getLogoutUrl([
            'id_token_hint' => $idToken,
        ]);
    }
}
