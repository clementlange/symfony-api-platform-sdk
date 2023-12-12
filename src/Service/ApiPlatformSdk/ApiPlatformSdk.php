<?php

namespace App\Service\ApiPlatformSdk;

use Symfony\Component\HttpClient\HttpClient;
use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @since   May 07 2021
 * @author  Clément Lange <clement@awelty.com>
 * @package App\Service\ApiPlatformSdk
 * @version 1.3
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
     * Renew tokens after (minutes)
     */
    const RENEW_TOKEN_MINUTES   = 1440;

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
    protected string $apiUrl;
    protected string $login;
    protected string $password;
    protected string $authenticationMethod;
    protected string $token;
    protected ApiToken $emsToken;
    protected HttpClientInterface $httpClient;
    protected array $queryString = [];
    protected string $queryStringAdditional = '';
    protected string $concatFormat;
    protected string $format;
    protected string $accept;
    protected string $contentType;
    protected array $postData = [];
    protected int $maxPage;
    protected int $totalItems;
    protected int $page;
    protected string $orderProperty;
    protected string $orderSort;
    protected string|array $content;
    protected EntityManagerInterface $em;
    protected ApiTokenRepository $apiTokenRepository;
    protected bool $hasAuthentication;
    protected string $authenticationUri;
    protected string $overriddenAuthUrl = '';
    protected string $oAuth2ClientId;
    protected string $oAuth2ClientSecret;
    protected string $oAuth2RequestScope;
    protected string $oAuth2GrantType;
    protected int $tokenLifetime = 0;


    /**
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

        // Delete token older than the set time
        if (empty($this->tokenLifetime)) {
            // Use default token lifetime value
            $tokenLifetime = self::RENEW_TOKEN_MINUTES;
        }
        else {
            // Use class-specific token lifetime value set in extented class
            $tokenLifetime = $this->getTokenLifetime();
        }
        $this->apiTokenRepository->deleteAfter($tokenLifetime);

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
     * @param  mixed $hasAuthentication
     * @return void
     */
    public function setHasAuthentication($hasAuthentication)
    {
        $this->hasAuthentication = $hasAuthentication;
    }


    /**
     * @return bool
     */
    protected function getHasAuthentication()
    {
        return $this->hasAuthentication;
    }


    /**
     * @param  mixed $authenticationUri
     * @return void
     */
    public function setAuthenticationUri($authenticationUri)
    {
        $this->authenticationUri = trim($authenticationUri,'/');
    }


    /**
     * @return string
     */
    protected function getAuthenticationUri()
    {
        return $this->authenticationUri;
    }


    /**
     * @return App\Repository\ApiTokenRepository
     */
    protected function getApiTokenRepository()
    {
        return $this->apiTokenRepository;
    }


    /**
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
     * @return string
     */
    protected function getApiUrl()
    {
        return $this->apiUrl.(!preg_match('/\/$/', $this->apiUrl) ? '/' : '');
    }


    /**
     * @param  string $login
     * @return void
     */
    public function setLogin($login = '')
    {
        $this->login = $login;
    }


    /**
     * @return string
     */
    protected function getLogin()
    {
        return $this->login;
    }


    /**
     * @param  string $authenticationMethod
     * @return void
     */
    public function setAuthenticationMethod($authenticationMethod = '')
    {
        $this->authenticationMethod = $authenticationMethod;
    }


    /**
     * @return string
     */
    protected function getAuthenticationMethod()
    {
        return $this->authenticationMethod;
    }


    /**
     * @param  string $password
     * @return void
     */
    public function setPassword($password = '')
    {
        $this->password = $password;
    }


    /**
     * @return void
     */
    protected function getPassword()
    {
        return $this->password;
    }


    /**
     * @param  string $token
     * @return void
     */
    public function setToken($token = '')
    {
        $this->token = $token;
    }


    /**
     * @return string
     */
    protected function getToken()
    {
        return $this->token;
    }



    /**
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
     * @param  string $accept
     * @return void
     */
    public function setAccept($accept = '')
    {
        $this->accept = $accept;
    }


    /**
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
     * @param  string $format
     * @return void
     */
    public function setFormat($format = '')
    {
        $this->format = $format;
    }


    /**
     * @return string
     */
    protected function getFormat()
    {
        return $this->format;
    }


    /**
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
     * @param  string $concatFormat
     * @return void
     */
    public function setConcatFormat($concatFormat = '')
    {
        $this->concatFormat = $concatFormat;
    }


    /**
     * @param  string $oAuth2ClientId
     * @return void
     */
    public function setOAuth2ClientId($oAuth2ClientId = '')
    {
        $this->oAuth2ClientId = $oAuth2ClientId;
    }


    /**
     * @return string
     */
    protected function getOAuth2ClientId()
    {
        return $this->oAuth2ClientId;
    }


    /**
     * @param  string $oAuth2ClientSecret
     * @return void
     */
    public function setOAuth2ClientSecret($oAuth2ClientSecret = '')
    {
        $this->oAuth2ClientSecret = $oAuth2ClientSecret;
    }


    /**
     * @return string
     */
    protected function getOAuth2ClientSecret()
    {
        return $this->oAuth2ClientSecret;
    }


    /**
     * @param  string $oAuth2RequestScope
     * @return void
     */
    public function setOAuth2RequestScope($oAuth2RequestScope = '')
    {
        $this->oAuth2RequestScope = $oAuth2RequestScope;
    }


    /**
     * @return string
     */
    protected function getOAuth2RequestScope()
    {
        return $this->oAuth2RequestScope;
    }


    /**
     * @param  string $oAuth2GrantType
     * @return void
     */
    public function setOAuth2GrantType($oAuth2GrantType = '')
    {
        $this->oAuth2GrantType = $oAuth2GrantType;
    }


    /**
     * @return string
     */
    protected function getOAuth2GrantType()
    {
        return $this->oAuth2GrantType;
    }


    /**
     * @param  string $overrideAuthUrl
     * @return void
     */
    public function setOverriddenAuthUrl($overriddenAuthUrl = '')
    {
        $this->overriddenAuthUrl = $overriddenAuthUrl;
    }


    /**
     * @return string
     */
    protected function getOverriddenAuthUrl()
    {
        return $this->overriddenAuthUrl;
    }


    /**
     * @param  int $tokenLifetime
     * @return void
     */
    public function setTokenLifetime($tokenLifetime = 0)
    {
        $this->tokenLifetime = $tokenLifetime;
    }


    /**
     * @return int
     */
    protected function getTokenLifetime()
    {
        return $this->tokenLifetime;
    }


    /**
     * Usage : no need to be explicitly called if default credentials are used.
     * If specific credentials are used (overridden), call it in controller :
     * $auth = $emonsite->authenticate(new_login, new_password);
     *
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
     * Performs an authentication request on the API using OAuth 2.0 protocol
     *
     * @return string $token
     */
    protected function requestAuthenticationOAuth2()
    {
        // If the Auth URL has been overridden
        if (!empty($this->getOverriddenAuthUrl())) {
            $authUrl = $this->getOverriddenAuthUrl();
        }
        // Else, use the authentication URI (default)
        else {
            $authUrl = $this->getAuthenticationUri();
        }

        // Specific Content-type for auth
        $this->setContentType('application/x-www-form-urlencoded');

        // POST request auth
        $this->post($authUrl, [
            'grant_type' => $this->getOAuth2GrantType(),
            'client_id' => $this->getOAuth2ClientId(),
            'client_secret' => $this->getOAuth2ClientSecret(),
            'username' => $this->getLogin(),
            'password' => $this->getPassword()
        ]);

        // Save Token
        if (isset($this->content['body']['access_token']))
        {
            // Saves token in DB
            $this->emsToken = new ApiToken();
            $this->emsToken->setToken($this->content['body']['access_token']);
            $this->emsToken->setUser($this->getLogin());
            $this->emsToken->setDomain($this->getApiUrl());
            $this->em->persist($this->emsToken);
            $this->em->flush();

            // Return token
            return $this->content['body']['access_token'];
        }
        else {
            // Error on authentication, invalid credentials
            return false;
        }
    }


    /**
     * Load Token from database
     *
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
     * @return void
     */
    protected function deleteUserToken()
    {
        return $this->apiTokenRepository->deleteUserToken($this->getLogin(), $this->getApiUrl());
    }


    /**
     * Returns request content
     *
     * @return mixed
     */
    protected function getContent()
    {
        return $this->content;
    }


    /**
     * Runs an HTTP GET request to the API
     *
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
        $isJsonPayload = false;
        foreach ($headers as $i => $h) {
            if (strtolower($i) == 'content-type' && preg_match('/application\/json/i', $h)) {
                $isJsonPayload = true;
                break;
            }
        }

        // POST data is multipart/form-data or form-urlencoded : body has "body" index, not "json"
        if (!$isJsonPayload) {
            $payload['body'] = $this->postData;
        }
        // payload's body content-type is JSON
        else {
            $payload['json'] = $this->postData;
        }

        // If the full URL has been specified in the request, instead of just the URI
        if (preg_match('/^http/', $uri)) {
            $fullUrl = $uri;
        }
        else {
            $fullUrl = $this->getApiUrl().$uri;
        }

        // Make HTTP request
        $response = $this->httpClient->request('POST', $fullUrl
            /* Adds additional query string vars (if applicable) */
            .(!empty($this->getQueryStringAdditional()) ? '?'.$this->getQueryStringAdditional() : ''),
            $payload
        );

        // Delete existing token if request is forbidden (token may be expired)
        if ($response->getStatusCode() == 401) {
            $this->deleteUserToken();
        }

        // Create Json body
        $this->content = [
            'code' => $response->getStatusCode(),
            'body' => (preg_match('/^20[01]$/', $response->getStatusCode()) ? $response->toArray() : null)
        ];

        return $this->content;
    }


    /**
     * Runs an HTTP PUT request to the API
     *
     * @param string $uri Request URI (without parameters)
     * @return mixed Associative array representing response
     */
    public function put($uri = '', $postData = [])
    {
        if (!$uri) return false;

        if (!empty($postData)) {
            $this->postData = $postData;
        }

        $headers = [
            'accept' => $this->getAccept(),
            'Content-Type' => $this->getContentType(),
            /* Authorization token */
            'Authorization' => 'Bearer '.$this->getToken()
        ];

        $payload = [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            /* Main query string vars */
            'query' => $this->getQueryString(),
            /* Set specific headers */
            'headers' => $headers
        ];

        // if payload's body content-type is not Json (example : upload image)
        $isJsonPayload = false;
        foreach ($headers as $i => $h) {
            if (strtolower($i) == 'content-type' && preg_match('/application\/json/i', $h)) {
                $isJsonPayload = true;
                break;
            }
        }

        // POST data is multipart/form-data or form-urlencoded : body has "body" index, not "json"
        if (!$isJsonPayload) {
            $payload['body'] = $this->postData;
        }
        // payload's body content-type is JSON
        else {
            $payload['json'] = $this->postData;
        }

        $response = $this->httpClient->request('PUT', $this->getApiUrl().$uri
            /* Adds additional query string vars (if applicable) */
            .(!empty($this->getQueryStringAdditional()) ? '?'.$this->getQueryStringAdditional() : ''),
            $payload
        );

        // Delete existing token if request is forbidden (token may be expired)
        if ($response->getStatusCode() == 401) {
            $this->deleteUserToken();
        }

        // Create Json body
        $this->content = [
            'code' => $response->getStatusCode(),
            'body' => ($response->getStatusCode() == 200 ? $response->toArray() : null)
        ];

        return $this->content;
    }


    /**
     * Runs an HTTP PATCH request to the API
     * @param string $uri Request URI (without parameters)
     * @return mixed Associative array representing response
     */
    public function patch($uri = '', $postData = [])
    {
        if (!$uri) return false;

        if (!empty($postData)) {
            $this->postData = $postData;
        }

        $headers = [
            'accept' => $this->getAccept(),
            'Content-Type' => 'application/merge-patch+json',
            /* Authorization token */
            'Authorization' => 'Bearer '.$this->getToken()
        ];

        $payload = [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            /* Main query string vars */
            'query' => $this->getQueryString(),
            /* Set specific headers */
            'headers' => $headers
        ];

        // if payload's body content-type is not Json (example : upload image)
        $isJsonPayload = false;
        foreach ($headers as $i => $h) {
            if (strtolower($i) == 'content-type' && preg_match('/application\/json/i', $h)) {
                $isJsonPayload = true;
                break;
            }
        }

        // POST data is multipart/form-data or form-urlencoded : body has "body" index, not "json"
        if (!$isJsonPayload) {
            $payload['body'] = $this->postData;
        }
        // payload's body content-type is JSON
        else {
            $payload['json'] = $this->postData;
        }

        $response = $this->httpClient->request('PATCH', $this->getApiUrl().$uri
            /* Adds additional query string vars (if applicable) */
            .(!empty($this->getQueryStringAdditional()) ? '?'.$this->getQueryStringAdditional() : ''),
            $payload
        );

        // Delete existing token if request is forbidden (token may be expired)
        if ($response->getStatusCode() == 401) {
            $this->deleteUserToken();
        }

        // Create Json body
        $this->content = [
            'code' => $response->getStatusCode(),
            'body' => ($response->getStatusCode() == 200 ? $response->toArray() : null)
        ];

        return $this->content;
    }


    /**
     * Runs an HTTP DELETE request to the API
     *
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
        $this->content = [
            'code' => $response->getStatusCode(),
            'body' => null
        ];

        return $this->content;
    }


    /**
     * Sets a query string parameter (&name=value)
     *
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
     * @return string
     */
    protected function getQueryStringAdditional()
    {
        return $this->queryStringAdditional;
    }


    /**
     * Returns the query string (main part)
     *
     * @return array
     */
    protected function getQueryString()
    {
        return $this->queryString;
    }


    /**
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
     * @return int number of last page
     */
    public function getMaxPage()
    {
        return intval(($this->maxPage ? $this->maxPage : 1));
    }


    /**
     * Sets the maximum page for the result list
     *
     * @param array $content Associative array of API result
     */
    protected function setMaxPage($content)
    {
        if (!isset($content['hydra:view']['hydra:last'])) {
            $this->maxPage = 0;
        }
        if (isset($content['hydra:view']['hydra:last'])) {
            preg_match('/page=([0-9]+)$/i', $content['hydra:view']['hydra:last'], $m);
            $this->maxPage = (isset($m[1]) ? intval($m[1]) : 0);
        }
        else {
            $this->maxPage = 0;
        }
    }


    /**
     * Sets the number of items total for the result list
     *
     * @param array $content Associative array of API result
     */
    protected function setTotalItems($content)
    {
        if (!isset($content['hydra:totalItems'])) {
            $this->totalItems = 0;
        }
        else {
            $this->totalItems = intval($content['hydra:totalItems']);
        }
    }


    /**
     * Returns the total number of items for the result list
     *
     * @return int number of items total
     */
    public function getTotalItems()
    {
        return $this->totalItems;
    }


    /**
     * Sets the query order
     *
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
     * @return array Associative array
     */
    public function getOrder()
    {
        return array($this->orderProperty, $this->orderSort);
    }

}
