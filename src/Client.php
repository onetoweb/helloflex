<?php

namespace Onetoweb\HelloFlex;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\RequestOptions;
use Onetoweb\HelloFlex\Token;
use DateTime;

/**
 * HelloFlex Api Client.
 * 
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb. B.V.
 * 
 * @link https://api.helloflex.com/docs/index
 */
class Client
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_DELETE = 'DELETE';
    
    /**
     * @var string
     */
    private $clientId;
    
    /**
     * @var string
     */
    private $clientSecret;
    
    /**
     * @var Token
     */
    private $token;
    
    /**
     * @var int|null
     */
    private $totalCount;
    
    /**
     * @param string $clientId
     * @param string $clientSecret
     */
    public function __construct(string $clientId, string $clientSecret)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }
    
    /**
     * @return void
     */
    public function requestAccessToken(): void
    {
        $token = $this->post('/oauth2/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials'
        ], [], false);
        
        $this->updateToken($token);
    }
    
    /**
     * @param callable $updateTokenCallback
     */
    public function setUpdateTokenCallback(callable $updateTokenCallback): void
    {
        $this->updateTokenCallback = $updateTokenCallback;
    }
    
    /**
     * @param array $tokenArray
     *
     * @return void
     */
    private function updateToken(array $tokenArray): void
    {
        // get expires
        $expires = new DateTime();
        $expires->setTimestamp(time() + $tokenArray['expires_in']);
        
        $token = new Token($tokenArray['access_token'], $expires);
        
        $this->setToken($token);
        
        // token update callback
        ($this->updateTokenCallback)($this->getToken());
    }
    
    /**
     * @param Token $token
     *
     * @return void
     */
    public function setToken(Token $token): void
    {
        $this->token = $token;
    }
    
    /**
     * @return Token
     */
    public function getToken(): ?Token
    {
        return $this->token;
    }
    
    /**
     * @param string $endpoint
     * @param array $query = []
     *
     * @return array|null
     */
    public function get(string $endpoint, array $query = []): ?array
    {
        return $this->request(self::METHOD_GET, $endpoint, [], $query);
    }
    
    /**
     * @param string $endpoint
     * @param array $data = []
     * @param array $query = []
     *
     * @return array|null
     */
    public function post(string $endpoint, array $data = [], array $query = [], bool $json = true): ?array
    {
        return $this->request(self::METHOD_POST, $endpoint, $data, $query, $json);
    }
    
    /**
     * @param string $endpoint
     * @param array $data = []
     * @param array $query = []
     *
     * @return array|null
     */
    public function put(string $endpoint, array $data = [], array $query = [], bool $json = true): ?array
    {
        return $this->request(self::METHOD_PUT, $endpoint, $data, $query, $json);
    }
    
    
    /**
     * @param string $endpoint
     * @param array $data = []
     * @param array $query = []
     *
     * @return array|null
     */
    public function patch(string $endpoint, array $data = [], array $query = [], bool $json = true): ?array
    {
        return $this->request(self::METHOD_PATCH, $endpoint, $data, $query, $json);
    }
    
    /**
     * @param string $endpoint
     * @param array $query = []
     *
     * @return array|null
     */
    public function delete(string $endpoint, array $query = []): ?array
    {
        return $this->request(self::METHOD_DELETE, $endpoint, [], $query);
    }
    
    /**
     * @return int|null
     */
    public function getTotalCount(): ?int
    {
        return $this->totalCount;
    }
    
    /**
     * @param string $method = self::METHOD_GET
     * @param string $endpoint
     * @param array $data = []
     * @param array $query = []
     * 
     * @throws RequestException if the server request contains a error response
     * 
     * @return array|null
     */
    public function request(string $method = self::METHOD_GET, string $endpoint, array $data = [], array $query = [], bool $json = true): ?array
    {
        // build request haders
        $headers = [
            'Cache-Control' => 'no-cache',
            'Connection' => 'close',
            'Accept' => 'application/json',
        ];
        
        // check access token
        if ($endpoint !== '/oauth2/token') {
            
            if ($this->getToken() === null or $this->getToken()->isExpired()) {
                
                $this->requestAccessToken();
                
            }
            
            // add bearer token authorization header
            $headers['Authorization'] = "Bearer {$this->getToken()->getAccessToken()}";
        }
        
        //  add headers to request options
        $options[RequestOptions::HEADERS] = $headers;
        
        // add post data body
        if (in_array($method, [self::METHOD_POST, self::METHOD_PUT, self::METHOD_PATCH])) {
            
            if ($json) {
                $options[RequestOptions::JSON] = $data;
            } else {
                $options[RequestOptions::FORM_PARAMS] = $data;
            }
            
        }
        
        // build query
        if (count($query) > 0) {
            $endpoint .= '?' . http_build_query($query);
        }
        
        // build guzzle client
        $guzzleClient = new GuzzleClient([
            'base_uri' => 'https://api.helloflex.com'
        ]);
        
        // build guzzle request
        $result = $guzzleClient->request($method, $endpoint, $options);
        
        // get total count header
        if ($result->hasHeader('X-Total-Count')) {
            $this->totalCount = $result->getHeader('X-Total-Count')[0];
        } else {
            $this->totalCount = null;
        }
        
        // get contents
        $contents = $result->getBody()->getContents();
        
        // return data
        return json_decode($contents, true);
    }
}