# PrestaShop Provider for OAuth 2.0 Client

This package provides PrestaShop OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

```
composer require prestashopcorp/oauth2-prestashop
```

## Usage

```php
$prestaShopProvider = new \PrestaShopCorp\OAuth2\Client\Provider\PrestaShop([
    'clientId'                => 'yourClientId',          // The client ID assigned to you by PrestaShop
    'clientSecret'            => 'yourClientSecret',      // The client password assigned to you by PrestaShop
    'redirectUri'             => 'yourClientRedirectUri'  // The URL responding to the code flow implemented here
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
} elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
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
            $prestaShopUser->getEmailPicture(),
            $prestaShopUser->toArray()
        );
    
    } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        exit($e->getMessage());
    }
}
```

For more information see the PHP League's general usage examples.

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## License

The MIT License (MIT). Please see [License File](https://github.com/prestashopcorp/oauth2-prestashop/blob/master/LICENSE) for more information.