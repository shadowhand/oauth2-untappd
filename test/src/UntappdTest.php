<?php

namespace Shadowhand\OAuth2\Client\Test\Provider;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Mockery as m;
use Shadowhand\OAuth2\Client\Provider\Untappd as UntappdProvider;

class UntappdTest extends \PHPUnit_Framework_TestCase
{
    protected $provider;

    protected function setUp()
    {
        $this->provider = new UntappdProvider([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testAuthorizationUrl()
    {
        $url = $this->provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertEquals('/oauth/authenticate', $uri['path']);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_url', $query);
        $this->assertArrayHasKey('response_type', $query);
    }

    public function testBaseAccessTokenUrl()
    {
        $url = $this->provider->getBaseAccessTokenUrl([]);
        $uri = parse_url($url);

        $this->assertEquals('/oauth/authorize', $uri['path']);
    }

    public function testResourceOwnerDetailsUrl()
    {
        $token = m::mock('League\OAuth2\Client\Token\AccessToken', [['access_token' => 'mock_access_token']]);

        $url = $this->provider->getResourceOwnerDetailsUrl($token);
        $uri = parse_url($url);

        $this->assertEquals('/v4/user/info', $uri['path']);
        $this->assertNotContains('mock_access_token', $url);

    }

    public function testUserData()
    {
        $json = <<<EOJ
{
  "meta": {
    "http_code": 200
  },
  "response": {
    "user": {
      "uid": 1,
      "id": 1,
      "user_name": "gregavola",
      "first_name": "Greg",
      "last_name": "Avola",
      "user_avatar": "https://gravatar.com/avatar/0c6922e238dae5cccce96a32889fc911?size=100&d=htt…44.cloudfront.net%2Fsite%2Fassets%2Fimages%2Fdefault_avatar_v2.jpg%3Fv%3D1",
      "user_avatar_hd": "https://gravatar.com/avatar/0c6922e238dae5cccce96a32889fc911?size=125&d=htt…44.cloudfront.net%2Fsite%2Fassets%2Fimages%2Fdefault_avatar_v2.jpg%3Fv%3D1",
      "user_cover_photo": "https://untappd.s3.amazonaws.com/coverphoto/933f9eebffb9151299188512cbd5981b.jpg",
      "user_cover_photo_offset": 214,
      "is_private": 0,
      "location": "New York, NY",
      "url": "http://gregavola.com",
      "bio": "Co-Founder and CTO of Untappd, Web Developer, Beer Drinker & Community Guy",
      "is_supporter": 1,
      "relationship": "self",
      "untappd_url": "http://untappd.com/user/gregavola",
      "account_type": "user",
      "stats": {
        "total_badges": 379,
        "total_friends": 1723,
        "total_checkins": 2197,
        "total_beers": 1187,
        "total_created_beers": 65,
        "total_followings": 176,
        "total_photos": 325
      },
      "date_joined": "Wed, 07 Jul 2010 05:51:10 +0000",
      "settings": {
        "badge": {
          "badges_to_facebook": 0,
          "badges_to_twitter": 1
        },
        "checkin": {
          "checkin_to_facebook": 0,
          "checkin_to_twitter": 0,
          "checkin_to_foursquare": 1
        },
        "navigation": {
          "default_to_checkin": 0
        },
        "user_birthday": "User Birth Here",
        "email_address": "User Email Here"
      }
    }
  }
}
EOJ;
        $response = json_decode($json, true);

        $provider = m::mock('Shadowhand\OAuth2\Client\Provider\Untappd[fetchResourceOwnerDetails]')
            ->shouldAllowMockingProtectedMethods();

        $provider->shouldReceive('fetchResourceOwnerDetails')
            ->times(1)
            ->andReturn($response);

        $token = m::mock('League\OAuth2\Client\Token\AccessToken');
        $user = $provider->getResourceOwner($token);

        $this->assertInstanceOf('League\OAuth2\Client\Provider\ResourceOwnerInterface', $user);

        $this->assertEquals(1, $user->getId());
        $this->assertEquals('gregavola', $user->getUsername());
        $this->assertEquals('Greg', $user->getFirstName());
        $this->assertEquals('Avola', $user->getLastName());
        $this->assertEquals('User Email Here', $user->getEmail());

        $user = $user->toArray();

        $this->assertArrayHasKey('uid', $user);
        $this->assertArrayHasKey('user_name', $user);
        $this->assertArrayHasKey('first_name', $user);
        $this->assertArrayHasKey('last_name', $user);
        $this->assertArrayHasKey('settings', $user);
    }

    public function testErrorResponse()
    {
        $response = m::mock('GuzzleHttp\Psr7\Response');

        $response->shouldReceive('getHeader')
            ->with('content-type')
            ->andReturn(['application/json']);

        $json = <<<EOJ
{
  "meta": {
    "code": 500,
    "error_detail": "The user has not authorized this application or the token is invalid.",
    "error_type": "invalid_auth",
    "developer_friendly": "The user has not authorized this application or the token is invalid.",
    "response_time": {
      "time": 0,
      "measure": "seconds"
    }
  }
}
EOJ;

        $response->shouldReceive('getBody')
            ->andReturn($json);

        $provider = m::mock('Shadowhand\OAuth2\Client\Provider\Untappd[sendRequest]')
            ->shouldAllowMockingProtectedMethods();

        $provider->shouldReceive('sendRequest')
            ->times(1)
            ->andReturn($response);

        $this->setExpectedException(IdentityProviderException::class);

        $token = m::mock('League\OAuth2\Client\Token\AccessToken');
        $user = $provider->getResourceOwner($token);
    }
}
