<?php

namespace PrestaShopCorp\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

/*
League\OAuth2\Client\Provider\GenericResourceOwner Object
(
    [response:protected] => Array
        (
            [aud] => Array
                (
                    [0] => shop-client
                )

            [auth_time] => 1661343934
            [email] => john.doe@prestashop.com
            [email_verified] => 1
            [iat] => 1661343934
            [iss] => http://hydra:4444/
            [name] => John Doe
            [picture] => https://lh3.googleusercontent.com/a/AATXAJzK3D_K4_7YHFDQHFD3C_1ViDfRVDmQTukCyw=s96-c
            [rat] => 1661343934
            [sub] => 4rFN5bm2piPeHTYUFtUIwcyFKKKOp
        )

    [resourceOwnerId:protected] => id
)
*/

class PrestaShopUser implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array  $response
     */
    public function __construct(array $response = array())
    {
        $this->response = $response;
    }

    public function getId()
    {
        return $this->getValueByKey($this->response, 'sub');
    }

    /**
     * Get resource owner name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getValueByKey($this->response, 'name');
    }

    /**
     * Get resource owner email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getValueByKey($this->response, 'email');
    }

    /**
     * Get resource owner email verified
     *
     * @return string|null
     */
    public function getEmailVerified()
    {
        return $this->getValueByKey($this->response, 'email_verified');
    }

    /**
     * Get resource owner picture
     *
     * @return string|null
     */
    public function getPicture()
    {
        return $this->getValueByKey($this->response, 'picture');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}