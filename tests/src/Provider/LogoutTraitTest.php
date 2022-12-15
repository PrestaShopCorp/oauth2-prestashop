<?php

namespace PrestaShop\OAuth2\Client\Test\Provider;

use PHPUnit\Framework\TestCase;
use PrestaShop\OAuth2\Client\Provider\PrestaShop;

class LogoutTraitTest extends TestCase
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
            'redirectUri' => 'https://test-client-redirect.net',
            'postLogoutCallbackUri' => 'https://test-client-redirect.net/logout?oauth2Callback',
            'uiLocales' => ['fr-CA', 'en'],
            'acrValues' => ['prompt:login'],
        ]);
    }

    /**
     * @test
     */
    public function itShouldGenerateLogoutUrl(): void
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
    public function itShouldGenerateLogoutUrlWithOptionalParameters(): void
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
    public function itShouldGenerateLogoutUrlWithOptionalOnlyParameters(): void
    {
        $idToken = 'someRandomIdToken';
        $postLogoutRedirectUri = 'https://overriden-post-logout-uri.net';

        $this->provider = new PrestaShop([
            'clientId' => 'test-client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://test-client-redirect.net',
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
    public function itShouldGetBaseSessionLogoutUrl(): void
    {
        $url = $this->provider->getBaseSessionLogoutUrl();
        $uri = parse_url($url);

        $path = '';
        if (\is_array($uri) && isset($uri['path'])) {
            $path = $uri['path'];
        }

        $this->assertEquals('/sessions/logout', $path);
    }

    /**
     * @test
     */
    public function itShouldGetLogoutUrl(): void
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

        $this->assertEquals('/sessions/logout', $path);
    }

    /**
     * @test
     */
    public function itShouldThrowExceptionWhenIdTokenIsMissing(): void
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
    public function itShouldThrowExceptionWhenPostLogoutCallbackUriIsMissing(): void
    {
        $idToken = 'someRandomIdToken';

        $this->provider = new PrestaShop([
            'clientId' => 'test-client',
            'clientSecret' => 'secret',
            'redirectUri' => 'https://test-client-redirect.net',
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
