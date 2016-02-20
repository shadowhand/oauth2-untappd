# Untappd Provider for OAuth 2.0 Client

[![Join the chat](https://img.shields.io/badge/gitter-join-1DCE73.svg)](https://gitter.im/thephpshadowhand/oauth2-untappd)
[![Build Status](https://img.shields.io/travis/shadowhand/oauth2-untappd.svg)](https://travis-ci.org/thephpshadowhand/oauth2-untappd)
[![Code Coverage](https://img.shields.io/coveralls/thephpshadowhand/oauth2-untappd.svg)](https://coveralls.io/r/thephpshadowhand/oauth2-untappd)
[![Code Quality](https://img.shields.io/scrutinizer/g/thephpshadowhand/oauth2-untappd.svg)](https://scrutinizer-ci.com/g/thephpshadowhand/oauth2-untappd/)
[![License](https://img.shields.io/packagist/l/shadowhand/oauth2-untappd.svg)](https://github.com/thephpshadowhand/oauth2-untappd/blob/master/LICENSE)
[![Latest Stable Version](https://img.shields.io/packagist/v/shadowhand/oauth2-untappd.svg)](https://packagist.org/packages/shadowhand/oauth2-untappd)

This package provides Untappd OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

This package is compliant with [PSR-1][], [PSR-2][] and [PSR-4][]. If you notice compliance oversights, please send
a patch via pull request.

[PSR-1]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md
[PSR-2]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md
[PSR-4]: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md

## Requirements

The following versions of PHP are supported.

* PHP 5.5
* PHP 5.6
* PHP 7.0
* HHVM

## Installation

To install, use composer:

```
composer require shadowhand/oauth2-untappd
```

## Usage

### Authorization Code Flow

```php
$provider = new Shadowhand\OAuth2\Client\Provider\Untappd([
    'clientId'     => '{untappd-app-id}',
    'clientSecret' => '{untappd-app-secret}',
    'redirectUri'  => 'https://example.com/callback-url',
    'hostedDomain' => 'example.com',
]);

if (!empty($_GET['error'])) {

    // Got an error, probably user denied access
    exit('Got error: ' . $_GET['error']);

} elseif (empty($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->state;
    header('Location: ' . $authUrl);
    exit;

} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

    // State is invalid, possible CSRF attack in progress
    unset($_SESSION['oauth2state']);
    exit('Invalid state');

} else {

    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the owner details
        $ownerDetails = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $ownerDetails->getFirstName());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Something went wrong: ' . $e->getMessage());

    }

    // Use this to interact with an API on the users behalf
    echo $token->accessToken;
}
```

### Refreshing a Token

Untappd tokens do not expire and do not need to be refreshed.

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/thephpleague/oauth2-untappd/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Woody Gilk](https://github.com/shadowhand)
- [All Contributors](https://github.com/thephpleague/oauth2-untappd/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/thephpleague/oauth2-untappd/blob/master/LICENSE) for more information.
