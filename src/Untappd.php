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
        return $this->appendQuery(
            'https://api.untappd.com/v4/user/info',
            http_build_query([
                'access_token' => (string) $token,
            ])
        );
    }

    protected function getDefaultScopes()
    {
        return [];
    }

    protected function getAuthorizationParameters(array $options)
    {
        $params = parent::getAuthorizationParameters($options);

        // Untappd uses a non-standard redirect name
        $params['redirect_url'] = $params['redirect_uri'];
        unset($params['redirect_uri']);

        // Untappd does not support state passing
        $this->state = '';
        unset($params['state']);

        return $params;
    }

    protected function getAccessTokenMethod()
    {
        return self::METHOD_GET;
    }

    protected function getAccessTokenUrl(array $params)
    {
        // Untappd requires inclusion of additional params for verification
        $params = array_replace($params, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_url' => $this->redirectUri,
        ]);

        return parent::getAccessTokenUrl($params);
    }

    protected function prepareAccessTokenResponse(array $result)
    {
        // Untappd wraps the response to include metadata
        return parent::prepareAccessTokenResponse($result['response']);
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['meta']['error_type'])) {
            $code = 0;
            if (!empty($data['meta']['http_code'])) {
                $code = $data['meta']['http_code'];
            }
            $error = $data['meta']['error_detail'];

            throw new IdentityProviderException($error, $code, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new UntappdUser($response['response']['user']);
    }
}
