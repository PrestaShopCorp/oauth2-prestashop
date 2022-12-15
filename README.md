# PrestaShop Provider for OAuth 2.0 Client

This package provides PrestaShop OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

```
composer require prestashopcorp/oauth2-prestashop
```

[//]: # (Project is not yet published on Packagist, but you can still configure a repository in your composer.json:)

[//]: # ()
[//]: # (```json)

[//]: # ("repositories": [)

[//]: # (    {)

[//]: # (        "type": "vcs",)

[//]: # (        "url": "git@github.com:PrestaShopCorp/oauth2-prestashop.git")

[//]: # (    })

[//]: # (],)

[//]: # (```)

## Usage

```php
$prestaShopProvider = new \PrestaShop\OAuth2\Client\Provider\PrestaShop([
    'clientId' => 'yourClientId', // The client ID assigned to you by PrestaShop
    'clientSecret' => 'yourClientSecret', // The client password assigned to you by PrestaShop
    'redirectUri' => 'yourClientRedirectUri', // The URL responding to the code flow implemented here
    // Optional parameters
    'uiLocales' => ['fr-FR', 'en'],
    'acrValues' => ['prompt:create'], // In that specific case we change the default prompt to the "register" page
]);

if (!empty($_GET['error'])) {
    // Got an error, probably user denied access
    exit($_GET['error']);
    
// If we don't have an authorization code then get one
} elseif (!isset($_GET['code'])) {
    $authorizationUrl = $prestaShopProvider->getAuthorizationUrl($options);

    // Get state and store it to the session
    $_SESSION['oauth2state'] = $prestaShopProvider->getState();

    // Redirect user to authorization URL
    header('Location: ' . $authorizationUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) 
            && $_GET['state'] !== $_SESSION['oauth2state'])) {

    if (isset($_SESSION['oauth2state'])) {
        unset($_SESSION['oauth2state']);
    }
    
    exit('Invalid state');
    
} else {
    try {
        // Try to get an access token (using the authorization code grant)
        $accessToken = $prestaShopProvider->getAccessToken('authorization_code', [
            'code' => $_GET['code']
        ]);
    
        // Use this to interact with an API on the users behalf
        $token = $accessToken->getToken();
        
        // Get resource owner
        $prestaShopUser = $provider->getResourceOwner($accessToken);
        
        var_dump(
            $prestaShopUser->getId(),
            $prestaShopUser->getName(),
            $prestaShopUser->getEmail(),
            $prestaShopUser->getEmailVerified(),
            $prestaShopUser->getPicture(),
            $prestaShopUser->toArray()
        );
    
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        exit($e->getMessage());
    }
}
```

For more information see the PHP League's general usage examples.

## Logout flow 

Going beyond the scope of this library we provide a helper function `getLogoutUrl` to logout from your oauth2 session.

The only required parameter is `id_token_int` here, you can optionally provide `post_logout_redirect_uri` to override the one from the constructor.

Also don't forget to provide `postLogoutCallbackUri` at construction time if you plan to use it.

```php
$prestaShopProvider = new \PrestaShop\OAuth2\Client\Provider\PrestaShop([
    'clientId' => 'yourClientId', // The client ID assigned to you by PrestaShop
    'clientSecret' => 'yourClientSecret', // The client password assigned to you by PrestaShop
    'redirectUri' => 'yourClientRedirectUri', // The URL responding to the code flow implemented here
    'postLogoutCallbackUri' => 'yourLogoutCallbackUri', // Logout url whitelisted among the ones defined with your client
    // Optional parameters
    'uiLocales' => ['fr-FR', 'en'],
    'acrValues' => ['prompt:create'], // In that specific case we change the default prompt to the "register" page
]);

if (isset($_GET['oauth2Callback')) {
    // 
    session_destroy();
    
} else {
    /** @var \League\OAuth2\Client\Token\AccessToken $accessToken */
    $accessToken = $_SESSION['accessToken'];

    // The only required parameter is "id_token_int" here, 
    // you can optionally provide "post_logout_redirect_uri" to override the one from the constructor.
    header('Location: ' . $prestaShopProvider->getLogoutUrl([
        'id_token_hint' => $accessToken->getValues()['id_token'],
        // (Optionnal here) Logout url whitelisted among the ones defined with your client
        // 'post_logout_redirect_uri' => 'https://my-logout-url/?oauth2Callback',
    ]));
    exit;
}
```

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/prestashopcorp/oauth2-prestashop/blob/master/LICENSE) for more information.