<?php

namespace PrestaShop\OAuth2\Client\Test\Provider;

use PHPUnit\Framework\TestCase;
use PrestaShop\OAuth2\Client\Provider\PrestaShopUser;

class PrestaShopUserTest extends TestCase
{
    /**
     * @var PrestaShopUser
     */
    private $user;

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

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->user = new PrestaShopUser([
            'email' => 'john.doe@prestashop.com',
            'email_verified' => 1,
            'name' => 'John Doe',
            'picture' => 'https://lh3.googleusercontent.com/a/AATXAJzK3D_K4_7YHFDQHFD3C_1ViDfRVDmQTukCyw=s96-c',
            'sub' => '4rFN5bm2piPeHTYUFtUIwcyFKKKOp',
        ]);
    }

    /**
     * @test
     */
    public function itShouldGetId()
    {
        $this->assertEquals('4rFN5bm2piPeHTYUFtUIwcyFKKKOp', $this->user->getId());
    }

    /**
     * @test
     */
    public function itShouldGetEmail()
    {
        $this->assertEquals('john.doe@prestashop.com', $this->user->getEmail());
    }

    /**
     * @test
     */
    public function itShouldGetEmailVerified()
    {
        $this->assertEquals(1, $this->user->getEmailVerified());
    }

    /**
     * @test
     */
    public function itShouldGetName()
    {
        $this->assertEquals('John Doe', $this->user->getName());
    }

    /**
     * @test
     */
    public function itShouldGetPicture()
    {
        $this->assertEquals('https://lh3.googleusercontent.com/a/AATXAJzK3D_K4_7YHFDQHFD3C_1ViDfRVDmQTukCyw=s96-c', $this->user->getPicture());
    }
}
