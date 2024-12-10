<?php

namespace PrestaShop\OAuth2\Client\Provider\Traits;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use PrestaShop\OAuth2\Client\Provider\CachedFile;
use PrestaShop\OAuth2\Client\Provider\Exception;
use PrestaShop\OAuth2\Client\Provider\Exception\KidInvalidException;

trait TokenValidatorTrait
{
    /**
     * @var CachedFile
     */
    protected $cachedJwks;

    /**
     * @param bool $forceRefresh
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getJwks($forceRefresh = false)
    {
        if (null === $this->cachedJwks) {
            throw new \Exception('Cache file not configured');
        }

        if ($this->cachedJwks->isExpired() || $forceRefresh) {
            $this->cachedJwks->write(
                $this->getResponse(
                    $this->getRequest('GET', $this->getWellKnown()->jwks_uri)
                )->getBody()
            );
        }

        return json_decode($this->cachedJwks->read(), true);
    }

    /**
     * @param string $token
     * @param bool $refreshJwks
     *
     * @return object decoded token
     *
     * @throws Exception\SignatureInvalidException
     * @throws Exception\TokenExpiredException
     * @throws Exception\TokenInvalidException
     */
    public function verifyToken($token, $refreshJwks = false)
    {
        // verify token signature & expiration (among others)
        try {
            $token = JWT::decode($token, JWK::parseKeySet($this->getJwks($refreshJwks)));
        } catch (ExpiredException $e) {
            throw new Exception\TokenExpiredException($e->getMessage());
        } catch (SignatureInvalidException $e) {
            throw new Exception\SignatureInvalidException($e->getMessage());
        } catch (\UnexpectedValueException $e) {
            // FIXME: check kid header by ourselves
            if (!$refreshJwks && $e->getMessage() == '"kid" invalid, unable to lookup correct key') {
                return $this->verifyToken($token, true);
            }
            throw new KidInvalidException($e->getMessage());
        } catch (\Throwable $e) {
            throw new Exception\TokenInvalidException($e->getMessage());
            /* @phpstan-ignore-next-line */
        } catch (\Exception $e) {
            throw new Exception\TokenInvalidException($e->getMessage());
        }

        return $token;
    }

    /**
     * @param string $token string token to be validated
     * @param array $scope expected scope(s))
     * @param array $audience expected audience(s)
     *
     * @return object decoded token
     *
     * @throws Exception\AudienceInvalidException
     * @throws Exception\ScopeInvalidException
     * @throws Exception\SignatureInvalidException
     * @throws Exception\TokenExpiredException
     * @throws Exception\TokenInvalidException
     */
    public function validateToken($token, array $scope = [], array $audience = [])
    {
        $token = $this->verifyToken($token);
        $this->validateScope($token, $scope);
        $this->validateAudience($token, $audience);

        return $token;
    }

    /**
     * @param object $token
     * @param array $scope
     *
     * @return void
     *
     * @throws Exception\ScopeInvalidException
     */
    public function validateScope($token, array $scope)
    {
        // check expected scopes are included
        $scp = is_array($token->scp) ? array_unique($token->scp) : [];
        if (count(array_intersect($scope, $scp)) < count($scope)) {
            throw new Exception\ScopeInvalidException('Expected scopes not matched: ' . implode(', ', $scp));
        }
    }

    /**
     * @param object $token
     * @param array $audience
     *
     * @return void
     *
     * @throws Exception\AudienceInvalidException
     */
    public function validateAudience($token, array $audience)
    {
        // check expected audiences are included
        $aud = is_array($token->aud) ? array_unique($token->aud) : [];
        if (count(array_intersect($audience, $aud)) < count($audience)) {
            throw new Exception\AudienceInvalidException('Expected audiences not matched: ' . implode(', ', $aud));
        }
    }
}
