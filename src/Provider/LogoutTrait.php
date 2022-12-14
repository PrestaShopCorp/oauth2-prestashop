<?php

namespace PrestaShop\OAuth2\Client\Provider;

trait LogoutTrait
{
    /**
     * @return string
     */
    public function getBaseSessionLogoutUrl()
    {
        return 'https://oauth.prestashop.com/sessions/logout';
    }

    /**
     * Builds the session logout URL.
     *
     * @param array $options
     *
     * @return string Logout URL
     *
     * @throws \Exception
     */
    public function getLogoutUrl(array $options = []): string
    {
        $base = $this->getBaseSessionLogoutUrl();
        $params = $this->getLogoutParameters($options);
        $query = $this->getLogoutQuery($params);

        return $this->appendQuery($base, $query);
    }

    /**
     * @param array $options
     *
     * @return string[]
     *
     * @throws \Exception
     */
    protected function getLogoutParameters(array $options): array
    {
        if (empty($options['id_token_hint'])) {
            // $options['id_token_hint'] = $this->getSessionAccessToken()->getValues()['id_token'];
            throw new \Exception('Missing id_token_hint required parameter');
        }

        if (empty($options['post_logout_redirect_uri'])) {
            // $options['post_logout_redirect_uri'] = $this->getPostLogoutRedirectUri();
            throw new \Exception('Missing post_logout_redirect_uri required parameter');
        }

        return $options;
    }

    /**
     * Builds the logout URL's query string.
     *
     * @param array $params Query parameters
     *
     * @return string Query string
     */
    protected function getLogoutQuery(array $params): string
    {
        return $this->buildQueryString($params);
    }
}