<?php

namespace Shadowhand\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Untappd extends AbstractProvider
{
    use BearerAuthorizationTrait;

    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'uid';

    public function getBaseAuthorizationUrl()
    {
        return 'https://untappd.com/oauth/authenticate';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://untappd.com/oauth/authorize';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return 'https://api.untappd.com/v4/user/info';
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['meta']['error_type'])) {
            $code = $data['meta']['code'];
            $error = $data['meta']['error_detail'];

            throw new IdentityProviderException($error, $code, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new UntappdUser($response['user']);
    }
}
