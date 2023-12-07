<?php

namespace App\Service\ApiPlatformSdk;

use Symfony\Component\HttpClient\HttpClient;
use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @since   May 07 2021
 * @author  Clément Lange <clement@awelty.com>
 * @package App\Service\ApiPlatformSdk
 * @version 1.2
 * 
 * API Platform PHP SDK for Symfony
 * 
 * Works with "ApiToken" objects in the following directories, to save tokens in database :
 * Entity - App\Entity\ApiToken
 * Repository - App\Repository\ApiTokenRepository
 * 
 * Supposts authentication methods : JWT | OAuth 2.0.
 */
class ApiPlatformSdk
{
    /**
     * Renew tokens after (days)
     */
    const RENEW_TOKEN_DAYS  = 20;

    /**
     * Default format (API extension)
     */
    const DEFAULT_FORMAT        = 'jsonld';
    const CONCAT_FORMAT         = true;

    /**
     * Default Accept and Content-Type headers
     */
    const DEFAULT_ACCEPT        = 'application/ld+json';
    const DEFAULT_CONTENT_TYPE  = 'application/ld+json';

    /**
     * Attributes
     */
    protected $apiUrl;
    protected $login;
    protected $password;
    protected $authenticationMethod;
    protected $token;
    protected $emsToken;
    protected $httpClient;
    protected $queryString = array();
    protected $queryStringAdditional = '';
    protected $concatFormat;
    protected $format;
    protected $accept;
    protected $contentType;
    protected $postData = array();
    protected $maxPage;
    protected $totalItems;
    protected $page;
    protected $orderProperty;
    protected $orderSort;
    protected $content;
    protected $em;
    protected $apiTokenRepository;
    protected $hasAuthentication;
    protected $authenticationUri;
    protected $oAuth2ClientId;
    protected $oAuth2ClientSecret;
    protected $oAuth2RequestScope;
    
    
    /**
     * @method __construct
     * @return void
     */
    public function __construct(
        EntityManagerInterface $em,
        ApiTokenRepository $apiTokenRepository,
        bool $hasAuthentication = false,
        string $authenticationMethod = 'jwt'
    )
    {
        // If used as standalone client, define defaults request parameters
        if (!$this->getFormat()) {
            $this->setFormat(self::DEFAULT_FORMAT);
        }
        if (!$this->getContentType()) {
            $this->setContentType(self::DEFAULT_CONTENT_TYPE);
        }
        if (!$this->getAccept()) {
            $this->setAccept(self::DEFAULT_ACCEPT);
        }
        if (is_null($this->getConcatFormat())) {
            $this->setConcatFormat(self::CONCAT_FORMAT);
        }

        // Create HTTPClient object
        $this->httpClient = HttpClient::create();

        // Entity manager & repositories
        $this->em = $em;
        $this->apiTokenRepository = $apiTokenRepository;

        // If Current API has authentication
        $this->setHasAuthentication($hasAuthentication);

        // Authentication method (JWT, OAuth 2.0 ...)
        $this->setAuthenticationMethod($authenticationMethod);

        // Authentication URI
        if (!$this->getAuthenticationUri()) {
            $this->setAuthenticationUri('auth'); // defaut authentication URI = "/auth"
        }

        // API login with default credentials
        if ($this->getApiUrl()) {
            if ($this->getAuthenticationMethod() == 'jwt') {
                $this->authenticate();
            }
            else {
                $this->authenticateOAuth2();
            }
        }
    }

    
    /**
     * @method __destruct
     * @return void
     */
    public function __destruct()
    {
        // Delete token older than 'RENEW_TOKEN_DAYS' days
        $this->apiTokenRepository->deleteAfterDays(self::RENEW_TOKEN_DAYS);
    }

    
    /**
     * @method setHasAuthentication
     * @param  mixed $hasAuthentication
     * @return void
     */
    public function setHasAuthentication($hasAuthentication)
    {
        $this->hasAuthentication = $hasAuthentication;
    }

    
    /**
     * @method getHasAuthentication
     * @return bool
     */
    protected function getHasAuthentication()
    {
        return $this->hasAuthentication;
    }


    /**
     * @method setAuthenticationUri
     * @param  mixed $authenticationUri
     * @return void
     */
    public function setAuthenticationUri($authenticationUri)
    {
        $this->authenticationUri = trim($authenticationUri,'/');
    }


    /**
     * @method getAuthenticationUri
     * @return string
     */
    protected function getAuthenticationUri()
    {
        return $this->authenticationUri;
    }

    
    /**
     * @method getApiTokenRepository
     * @return App\Repository\ApiTokenRepository
     */
    protected function getApiTokenRepository()
    {
        return $this->apiTokenRepository;
    }

    
    /**
     * @method getEntityManager
     * @return Doctrine\ORM\EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->em;
    }
    
    
    /**
     * If different API URL is used (overridden), call it in controller before request :
     * $emonsite->setApiUrl(new_url);
     * 
     * @method setApiUrl
     * @param  string $apiUrl
     * @return void
     */
    public function setApiUrl($apiUrl = '')
    {
        $this->apiUrl = $apiUrl;
    }
    

    /**
     * Adds a trailing slash at the end of URL if not specified
     * 
     * @method getApiUrl
     * @return string
     */
    protected function getApiUrl()
    {
        return $this->apiUrl.(!preg_match('/\/$/', $this->apiUrl) ? '/' : '');
    }

    
    /**
     * @method setLogin
     * @param  string $login
     * @return void
     */
    public function setLogin($login = '')
    {
        $this->login = $login;
    }

        
    /**
     * @method getLogin
     * @return string
     */
    protected function getLogin()
    {
        return $this->login;
    }


    /**
     * @method setAuthenticationMethod
     * @param  string $authenticationMethod
     * @return void
     */
    public function setAuthenticationMethod($authenticationMethod = '')
    {
        $this->authenticationMethod = $authenticationMethod;
    }

        
    /**
     * @method getAuthenticationMethod
     * @return string
     */
    protected function getAuthenticationMethod()
    {
        return $this->authenticationMethod;
    }

        
    /**
     * @method setPassword
     * @param  string $password
     * @return void
     */
    public function setPassword($password = '')
    {
        $this->password = $password;
    }

        
    /**
     * @method getPassword
     * @return void
     */
    protected function getPassword()
    {
        return $this->password;
    }


    /**
     * @method setToken
     * @param  string $token
     * @return void
     */
    public function setToken($token = '')
    {
        $this->token = $token;
    }

        
    /**
     * @method getToken
     * @return string
     */
    protected function getToken()
    {
        return $this->token;
    }


        
    /**
     * @method getAccept
     * @return string
     */
    protected function getAccept()
    {
        return $this->accept;
    }


    /**
     * If specific "accept" header is used (overridden), call it in controller before request :
     * $emonsite->setAccept(new_accept);
     * 
     * @method setAccept
     * @param  string $accept
     * @return void
     */
    public function setAccept($accept = '')
    {
        $this->accept = $accept;
    }


    /**
     * @method getContentType
     * @return string
     */
    protected function getContentType()
    {
        return $this->contentType;
    }


    /**
     * If specific "Content-Type" header is used (overridden), call it in controller before request :
     * $emonsite->setContentType(new_content_type);
     * 
     * @method setContentType
     * @param  string $contentType
     * @return void
     */
    public function setContentType($contentType = '')
    {
        $this->contentType = $contentType;
    }


    /**
     * If specific format is used (overridden), call it in controller before request :
     * $emonsite->setFormat(new_format);
     * 
     * @method setFormat
     * @param  string $format
     * @return void
     */
    public function setFormat($format = '')
    {
        $this->format = $format;
    }

        
    /**
     * @method getFormat
     * @return string
     */
    protected function getFormat()
    {
        return $this->format;
    }


    /**
     * @method getConcatFormat
     * @return boolean
     */
    protected function getConcatFormat()
    {
        return $this->concatFormat;
    }


    /**
     * If true, adds the format to each GET request
     * example : "GET /api/product_reviews.json" (true), "GET /api/product_reviews" (false)
     * 
     * @method setConcatFormat
     * @param  string $concatFormat
     * @return void
     */
    public function setConcatFormat($concatFormat)
    {
        $this->concatFormat = $concatFormat;
    }


    /**
     * @method setOAuth2ClientId
     * @param  string $oAuth2ClientId
     * @return void
     */
    public function setOAuth2ClientId($oAuth2ClientId = '')
    {
        $this->oAuth2ClientId = $oAuth2ClientId;
    }

    
    /**
     * @method getOAuth2ClientId
     * @return string
     */
    protected function getOAuth2ClientId()
    {
        return $this->oAuth2ClientId;
    }


    /**
     * @method setOAuth2ClientSecret
     * @param  string $oAuth2ClientSecret
     * @return void
     */
    public function setOAuth2ClientSecret($oAuth2ClientSecret = '')
    {
        $this->oAuth2ClientSecret = $oAuth2ClientSecret;
    }

    
    /**
     * @method getOAuth2ClientSecret
     * @return string
     */
    protected function getOAuth2ClientSecret()
    {
        return $this->oAuth2ClientSecret;
    }

    
    /**
     * @method setOAuth2RequestScope
     * @param  string $oAuth2RequestScope
     * @return void
     */
    public function setOAuth2RequestScope($oAuth2RequestScope = '')
    {
        $this->oAuth2RequestScope = $oAuth2RequestScope;
    }

    
    /**
     * @method getOAuth2RequestScope
     * @return string
     */
    protected function getOAuth2RequestScope()
    {
        return $this->oAuth2RequestScope;
    }

    
    /**
     * Usage : no need to be explicitly called if default credentials are used.
     * If specific credentials are used (overridden), call it in controller :
     * $auth = $emonsite->authenticate(new_login, new_password);
     * 
     * @method authenticate
     * @param  string $login
     * @param  string $password
     * @return mixed
     */
    public function authenticate($login = '', $password = '')
    {
        // If current API does not have authentication
        if (!$this->getHasAuthentication()) {
            return true;
        }

        // If override the DEFAULT_LOGIN and DEFAULT_PASSWORD constants
        if ($login || $password) {
            $this->setLogin($login);
            $this->setPassword($password);

            // Renew login with overridden credentials
            return $this->authenticate();
        }

        // Load token from DB
        if ($this->loadTokenFromDb())
        {
            $token = $this->emsToken->getToken();

            // Updates dates in DB
            $this->emsToken->setUpdatedAt(new DateTime());
            $this->em->persist($this->emsToken);
            $this->em->flush();
        }
        else {
            // If token is not set yet or not found in DB, post to /auth
            $token = $this->requestAuthentication();
        }

        if ($token) {
            // save token in current object
            $this->setToken($token);
             // all ok
            return true;
        }
        else {
            return false; // error
        }
    }

    
    /**
     * Performs an authentication request on the API
     * 
     * @method requestAuthentication
     * @return string $token
     */
    protected function requestAuthentication()
    {
        $this->post($this->getAuthenticationUri(), [
            'email' => $this->getLogin(),
            'password' => $this->getPassword()
        ]);

        if (isset($this->content['body']['token']))
        {
            // Saves token in DB
            $this->emsToken = new ApiToken();
            $this->emsToken->setToken($this->content['body']['token']);
            $this->emsToken->setUser($this->getLogin());
            $this->emsToken->setDomain($this->getApiUrl());
            $this->em->persist($this->emsToken);
            $this->em->flush();

            // Return token
            return $this->content['body']['token'];
        }
        else {
            // Error on authentication, invalid credentials
            return false;
        }
    }

    
    /**
     * Authenticate through OAuth 2.0 protocol
     * 
     * @method authenticateOAuth2
     * @return mixed
     */
    protected function authenticateOAuth2()
    {
        // If current API does not have authentication
        if (!$this->getHasAuthentication()) {
            return true;
        }

        // Load token from DB
        if ($this->loadTokenFromDb())
        {
            $token = $this->emsToken->getToken();

            // Updates dates in DB
            $this->emsToken->setUpdatedAt(new DateTime());
            $this->em->persist($this->emsToken);
            $this->em->flush();
        }
        else {
            // If token is not set yet or not found in DB, post to authentication URL
            $token = $this->requestAuthenticationOAuth2();
        }

        if ($token) {
            // save token in current object
            $this->setToken($token);
             // all ok
            return true;
        }
        else {
            return false; // error
        }
    }


    /**
     * requestAuthenticationOAuth2
     * 
     * @return string $token
     * 
     * Performs an authentication request on the API using OAuth 2.0 protocol
     */
    protected function requestAuthenticationOAuth2()
    {
        // TODO: POST request auth
        
        // TODO: Save Token

        /* $this->post($this->getAuthenticationUri(), [
            'email' => $this->getLogin(),
            'password' => $this->getPassword()
        ]); */

        /* if (isset($this->content['body']['token']))
        {
            // Saves token in DB
            $this->emsToken = new ApiToken();
            $this->emsToken->setToken($this->content['body']['token']);
            $this->emsToken->setUser($this->getLogin());
            $this->emsToken->setDomain($this->getApiUrl());
            $this->em->persist($this->emsToken);
            $this->em->flush();

            // Return token
            return $this->content['body']['token'];
        }
        else {
            // Error on authentication, invalid credentials
            return false;
        } */
    }


    /**
     * Load Token from database
     * 
     * @method loadTokenFromDb
     * @return App\Entity\ApiToken
     */
    protected function loadTokenFromDb()
    {
        $this->emsToken = $this->apiTokenRepository->findOneBy([
            'user' => $this->getLogin(),
            'domain' => $this->getApiUrl()
        ]);

        return $this->emsToken;
    }

    
    /**
     * Deletes token for specific user
     * 
     * @method deleteUserToken
     * @return void
     */
    protected function deleteUserToken()
    {
        return $this->apiTokenRepository->deleteUserToken($this->getLogin(), $this->getApiUrl());
    }


    /**
     * Runs an HTTP GET request to the API
     * 
     * @method get
     * @param string $uri Request URI (without parameters)
     * @return mixed Associative array representing response
     */
    public function get($uri = '')
    {
        if (!$uri) return false;

        $response = $this->httpClient->request('GET', $this->getApiUrl().$uri.($this->getConcatFormat() ? '.'.$this->getFormat() : '')
            /* Adds additional query string vars (if applicable) */
            .(!empty($this->getQueryStringAdditional()) ? '?'.$this->getQueryStringAdditional() : ''), [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            /* Main query string vars */
            'query' => $this->getQueryString(),
            'headers' => [
                /* Authorization token */
                'Authorization' => 'Bearer '.$this->getToken()
            ]
        ]);

        // Delete existing token if request is forbidden (token may be expired)
        if ($response->getStatusCode() == 401) {
            $this->deleteUserToken();
        }
        
        // Create Json body
        $this->content = json_decode($response->getContent(), true);

        // Sets max page and total items
        $this->setMaxPage($this->content);
        $this->setTotalItems($this->content);

        return (isset($this->content['hydra:member']) ? $this->content['hydra:member'] : $this->content);
    }


    /**
     * Runs an HTTP GET request to the API for a single item (/uri/id)
     * 
    * @method getSingle
    * @param string $uri Request URI (without parameters)
    * @param string $id ID of the item
    * @return mixed Associative array representing response
    */
    public function getSingle($uri = '', $id = '')
    {
        $response = $this->httpClient->request('GET', $this->getApiUrl().$uri.'/'.$id, [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            'query' => $this->getQueryString(),
            'headers' => [
                /* Authorization token */
                'Authorization' => 'Bearer '.$this->getToken()
            ]
        ]);

        // Delete existing token if request is forbidden (token may be expired)
        if ($response->getStatusCode() == 401) {
            $this->deleteUserToken();
        }
        
        // Create Json body
        $this->content = json_decode($response->getContent(), true);

        return (isset($this->content['hydra:member']) ? $this->content['hydra:member'] : $this->content);
    }


    /**
     * Runs an HTTP POST request to the API
     * 
     * @method post
     * @param string $uri Request URI (without parameters)
     * @param array $postData payload
     * @param array $headers request HTTP headers overriding defaults
     * @return mixed Associative array representing response
     */
    public function post($uri = '', $postData = [], $headers = [])
    {
        if (!$uri) return false;

        if (!empty($postData)) {
            $this->postData = $postData;
        }

        // default headers
        if (empty($headers)) {
            $headers = [
                'accept' => $this->getAccept(),
                'Content-Type' => $this->getContentType()
            ];
        }

        // Authorization token if not /auth requested
        if ($uri != $this->getAuthenticationUri()) {
            $headers['Authorization'] = 'Bearer '.$this->getToken();
        }

        // Actual Payload
        $payload = [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            'query' => $this->getQueryString(),
            /* Set specific headers */
            'headers' => $headers
        ];

        // if payload's body content-type is not Json (example : upload image)
        $isMultipart = false;
        foreach ($headers as $h) {
            if (preg_match('/multipart\/form\-data/i', $h)) {
                $isMultipart = true;
            }
        }

        // POST data is multipart/form-data : body has "body" index, not "json"
        if ($isMultipart) {
            $payload['body'] = $this->postData;
        }
        // payload's body content-type is JSON
        else {
            $payload['json'] = $this->postData;
        }

        // Make HTTP request
        $response = $this->httpClient->request('POST', $this->getApiUrl().$uri
            /* Adds additional query string vars (if applicable) */
            .(!empty($this->getQueryStringAdditional()) ? '?'.$this->getQueryStringAdditional() : ''),
            $payload
        );

        // Delete existing token if request is forbidden (token may be expired)
        if ($response->getStatusCode() == 401) {
            $this->deleteUserToken();
        }
        
        // Create Json body
        $this->content = array(
            'code' => $response->getStatusCode(),
            'body' => (preg_match('/^20[01]$/', $response->getStatusCode()) ? $response->toArray() : null)
        );

        return $this->content;
    }


    /**
     * Runs an HTTP PUT request to the API
     * 
     * @method put
     * @param string $uri Request URI (without parameters)
     * @return mixed Associative array representing response
     */
    public function put($uri = '', $postData = [])
    {
        if (!$uri) return false;

        if (!empty($postData)) {
            $this->postData = $postData;
        }

        $response = $this->httpClient->request('PUT', $this->getApiUrl().$uri
            /* Adds additional query string vars (if applicable) */
            .(!empty($this->getQueryStringAdditional()) ? '?'.$this->getQueryStringAdditional() : ''), [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            /* Main query string vars */
            'query' => $this->getQueryString(),
            /* Set specific headers */
            'headers' => [
                'accept' => $this->getAccept(),
                'Content-Type' => $this->getContentType(),
                /* Authorization token */
                'Authorization' => 'Bearer '.$this->getToken()
            ],
            'json' => $this->postData
        ]);

        // Delete existing token if request is forbidden (token may be expired)
        if ($response->getStatusCode() == 401) {
            $this->deleteUserToken();
        }
        
        // Create Json body
        $this->content = array(
            'code' => $response->getStatusCode(),
            'body' => ($response->getStatusCode() == 200 ? $response->toArray() : null)
        );

        return $this->content;
    }


    /**
     * Runs an HTTP PATCH request to the API
     * @method patch
     * @param string $uri Request URI (without parameters)
     * @return mixed Associative array representing response
     */
    public function patch($uri = '', $postData = [])
    {
        if (!$uri) return false;

        if (!empty($postData)) {
            $this->postData = $postData;
        }

        $response = $this->httpClient->request('PATCH', $this->getApiUrl().$uri
            /* Adds additional query string vars (if applicable) */
            .(!empty($this->getQueryStringAdditional()) ? '?'.$this->getQueryStringAdditional() : ''), [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            /* Main query string vars */
            'query' => $this->getQueryString(),
            /* Set specific headers */
            'headers' => [
                'accept' => $this->getAccept(),
                'Content-Type' => 'application/merge-patch+json',
                /* Authorization token */
                'Authorization' => 'Bearer '.$this->getToken()
            ],
            'json' => $this->postData
        ]);

        // Delete existing token if request is forbidden (token may be expired)
        if ($response->getStatusCode() == 401) {
            $this->deleteUserToken();
        }
        
        // Create Json body
        $this->content = array(
            'code' => $response->getStatusCode(),
            'body' => ($response->getStatusCode() == 200 ? $response->toArray() : null)
        );

        return $this->content;
    }


    /**
     * Runs an HTTP DELETE request to the API
     * 
     * @method delete
     * @param string $uri Request URI (without parameters)
     * @param string $id Object ID
     * @return mixed Associative array representing response
     */
    public function delete($uri = '', $id = '')
    {
        if (!$uri) return false;
        $uri = trim($uri, '/');

        $response = $this->httpClient->request('DELETE', $this->getApiUrl().$uri.'/'.$id, [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            /* Set specific headers */
            'headers' => [
                'accept' => $this->getAccept(),
                /* Authorization token */
                'Authorization' => 'Bearer '.$this->getToken()
            ]
        ]);

        // Delete existing token if request is forbidden (token may be expired)
        if ($response->getStatusCode() == 401) {
            $this->deleteUserToken();
        }
        
        // Create Json body
        $this->content = array(
            'code' => $response->getStatusCode(),
            'body' => null
        );
        
        return $this->content;
    }


    /**
     * Sets a query string parameter (&name=value)
     * 
     * @method addParameter
     * @param string $name Request parameter's name
     * @param string $value Request paramter's value
     * @param boolean $force_empty Force value if empty will make "&name=" as an explicitly empty value
     * @return boolean
     */
    public function addParameter($name = '', $value = '', $force_empty = false)
    {
        if ((!$name || !$value) && !$force_empty) {
            return false;
        }
        else {
            if (!preg_match('/\[\]$/i', $name)) {
                $this->queryString[$name] = $value;
            }
            else {
                $this->queryStringAdditional .= '&'.$name.'='.$value;
            }
        }
    }


    /**
     * Removes a query string parameter (&name=value)
     * 
     * @method removeParameter
     * @param string $name Request parameter's name
     * @return boolean
     */
    public function removeParameter($name = '')
    {
        if (!$name) {
            return false;
        }
        else {
            if (!preg_match('/\[\]$/i', $name)) {
                unset($this->queryString[$name]);
            }
            else {
                $this->queryStringAdditional = preg_replace('/\&'.$name.'=[a-z0-9\_\.\-\%]+/i','', $this->queryStringAdditional);
            }
        }
    }


    /**
     * Returns the query string (additional part)
     * 
     * @method getQueryStringAdditional
     * @return string
     */
    protected function getQueryStringAdditional()
    {
        return $this->queryStringAdditional;
    }


    /**
     * Returns the query string (main part)
     * 
     * @method getQueryString
     * @return array
     */
    protected function getQueryString()
    {
        return $this->queryString;
    }


    /**
     * Defines the page (Pagination)
     * 
     * @method setPage
     * 
     * @param int $p Page number
     * @return bool
     */
    public function setPage($p = 1)
    {
        if (is_numeric($p)) {
            $this->page = $p;
            $this->addParameter('page', $this->page);
            return true;
        }
        else {
            return false;
        }
    }

    
    /**
     * Returns the maximum page for the result list
     * 
     * @method getMaxPage
     * @return int number of last page
     */
    public function getMaxPage()
    {
        return intval(($this->maxPage ? $this->maxPage : '1'));
    }


    /**
     * Sets the maximum page for the result list
     * 
     * @method setMaxPage
     * @param array $content Associative array of API result
     */
    protected function setMaxPage($content)
    {
        if (!isset($content['hydra:view']['hydra:last'])) {
            $this->maxPage = false;
        }
        if (isset($content['hydra:view']['hydra:last'])) {
            preg_match('/page=([0-9]+)$/i', $content['hydra:view']['hydra:last'], $m);
            $this->maxPage = (isset($m[1]) ? $m[1] : 0);
        }
        else {
            $this->maxPage = 0;
        }
    }


    /**
     * Sets the number of items total for the result list
     * 
     * @method setTotalItems
     * @param array $content Associative array of API result
     */
    protected function setTotalItems($content)
    {
        if (!isset($content['hydra:totalItems'])) {
            $this->totalItems = false;
        }
        else {
            $this->totalItems = $content['hydra:totalItems'];
        }
    }


    /**
     * Returns the total number of items for the result list
     * 
     * @method getTotalItems
     * @return int number of items total
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }


    /**
     * Sets the query order
     * 
     * @method setOrder
     * @param string property field to sort
     * @param string sort order sort (asc|desc)
     * @return array Associative array of items
     */
    public function setOrder($property, $sort)
    {
        $this->orderProperty = $property;
        $this->orderSort = $sort;
        if (preg_match('/^(asc|desc)$/i', $sort)) {
            // remove already set order if needed
            $this->removeParameter('order');
            $this->addParameter('order['.$property.']', $sort);
        }
    }


    /**
     * Gets the query order
     * 
     * @method getOrder
     * @return array Associative array
     */
    public function getOrder()
    {
        return array($this->orderProperty, $this->orderSort);
    }


}