<?php

namespace Onetoweb\HelloFlex;

use DateTime;

/**
 * HelloFlex Api Token
 *
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb B.V.
 */
class Token
{
    /**
     * @var string
     */
    private $accessToken;
    
    /**
     * @var DateTime
     */
    private $expires;
    
    /**
     * @param string $accessToken
     * @param DateTime $expires
     */
    public function __construct(string $accessToken, DateTime $expires)
    {
        $this->accessToken = $accessToken;
        $this->expires = $expires;
    }
    
    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }
    
    /**
     * @return string
     */
    public function getExpires(): DateTime
    {
        return $this->expires;
    }
    
    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return (bool) (new DateTime() > $this->expires);
    }
}