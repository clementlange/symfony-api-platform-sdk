<?php
/**
 * @since   May 07 2021
 * @author  clement@awelty.com
 * @version 1.0
 * 
 * API Platform PHP SDK for Symfony
 * 
 * Works with "ApiToken" objects in the following directories, to save tokens in database :
 * Entity - App\Entity\ApiToken
 * Repository - App\Repository\ApiTokenRepository
 */

namespace App\Service\ApiPlatformSdk;

use Symfony\Component\HttpClient\HttpClient;
use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;

class ApiPlatformSdk
{
    /**
     * Renew tokens after (days)
     */
    const RENEW_TOKEN_DAYS  = 20;


    /**
     * Attributes
     */
    protected $apiUrl;
    protected $login;
    protected $password;
    protected $token;
    protected $emsToken;
    protected $httpClient;
    protected $queryString = array();
    protected $queryStringAdditional = '';
    protected $format;
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
    
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct($hasAuthentication = false, EntityManagerInterface $em, ApiTokenRepository $apiTokenRepository)
    {
        // Create HTTPClient object
        $this->httpClient = HttpClient::create();

        // Entity manager & repositories
        $this->em = $em;
        $this->apiTokenRepository = $apiTokenRepository;

        // If Current API has authentication
        $this->setHasAuthentication($hasAuthentication);

        // API login with default credentials
        if ($this->getApiUrl()) {
            $this->authenticate();
        }
    }

    
    /**
     * __destruct
     *
     * @return void
     */
    public function __destruct()
    {
        // Delete token older than 'RENEW_TOKEN_DAYS' days
        $this->apiTokenRepository->deleteAfterDays(self::RENEW_TOKEN_DAYS);
    }

    
    /**
     * setHasAuthentication
     *
     * @param  mixed $hasAuthentication
     * @return void
     */
    public function setHasAuthentication($hasAuthentication)
    {
        $this->hasAuthentication = $hasAuthentication;
    }

    
    /**
     * getHasAuthentication
     *
     * @return void
     */
    protected function getHasAuthentication()
    {
        return $this->hasAuthentication;
    }

    
    /**
     * getApiTokenRepository
     *
     * @return App\Repository\ApiTokenRepository
     */
    protected function getApiTokenRepository()
    {
        return $this->apiTokenRepository;
    }

    
    /**
     * getEntityManager
     *
     * @return Doctrine\ORM\EntityManagerInterface
     */
    protected function getEntityManager()
    {
        return $this->em;
    }
    
    
    /**
     * setApiUrl
     *
     * @param  string $apiUrl
     * @return void
     * 
     * If different API URL is used (overridden), call it in controller before request :
     * $emonsite->setApiUrl(new_url);
     */
    public function setApiUrl($apiUrl = '')
    {
        $this->apiUrl = $apiUrl;
    }
    

    /**
     * getApiUrl
     *
     * @return string
     */
    protected function getApiUrl()
    {
        return $this->apiUrl;
    }

    
    /**
     * setLogin
     *
     * @param  string $login
     * @return void
     */
    public function setLogin($login = '')
    {
        $this->login = $login;
    }

        
    /**
     * getLogin
     *
     * @return string
     */
    protected function getLogin()
    {
        return $this->login;
    }

        
    /**
     * setPassword
     *
     * @param  string $password
     * @return void
     */
    public function setPassword($password = '')
    {
        $this->password = $password;
    }

        
    /**
     * getPassword
     *
     * @return void
     */
    protected function getPassword()
    {
        return $this->password;
    }


    /**
     * setToken
     *
     * @param  string $token
     * @return void
     */
    public function setToken($token = '')
    {
        $this->token = $token;
    }

        
    /**
     * getToken
     *
     * @return string
     */
    protected function getToken()
    {
        return $this->token;
    }


    /**
     * setFormat
     *
     * @param  string $format
     * @return void
     * 
     * If specific format is used (overridden), call it in controller before request :
     * $emonsite->setFormat(new_format);
     */
    public function setFormat($format = '')
    {
        $this->format = $format;
    }

        
    /**
     * getFormat
     *
     * @return string
     */
    protected function getFormat()
    {
        return $this->format;
    }

    
    /**
     * authenticate
     *
     * @param  string $login
     * @param  string $password
     * @return mixed
     * 
     * Usage : no need to be explicitly called if default credentials are used.
     * If specific credentials are used (overridden), call it in controller :
     * $auth = $emonsite->authenticate(new_login, new_password);
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
     * loadTokenFromDb
     * 
     * @return App\Entity\ApiToken
     * 
     * Load JWT token from database
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
     * requestAuthentication
     * 
     * @return string $token
     * 
     * Performs an authentication request on the API
     */
    protected function requestAuthentication()
    {
        $this->post('auth', [
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
     * deleteUserToken
     *
     * @return void
     * 
     * Deletes token for specific user
     */
    protected function deleteUserToken()
    {
        return $this->apiTokenRepository->deleteUserToken($this->getLogin(), $this->getApiUrl());
    }


    /**
    * @method get()
    * Runs an HTTP GET request to the API
    * @param string $uri : Request URI (without parameters)
    * @return mixed Associative array representing response
    */
    public function get($uri = '')
    {
        if (!$uri) return false;

        $response = $this->httpClient->request('GET', $this->getApiUrl().$uri.'.'.$this->getFormat()
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
    * @method getSingle()
    * Runs an HTTP GET request to the API for a single item (/uri/id)
    * @param string $uri : Request URI (without parameters)
    * @param string $id : ID of the item
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
    * @method post()
    * Runs an HTTP POST request to the API
    * @param string $uri : Request URI (without parameters)
    * @return mixed Associative array representing response
    */
    public function post($uri = '', $postData = [])
    {
        if (!$uri) return false;

        if (!empty($postData)) {
            $this->postData = $postData;
        }

        $headers = [
            'accept' => 'application/ld+json',
            'Content-Type' => 'application/ld+json'
        ];

        // Authorization token if not /auth requested
        if ($uri != 'auth') {
            $headers['Authorization'] = 'Bearer '.$this->getToken();
        }

        $response = $this->httpClient->request('POST', $this->getApiUrl().$uri, [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            /* Set specific headers */
            'headers' => $headers,
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

        return json_encode($this->content);
    }


    /**
    * @method put()
    * Runs an HTTP PUT request to the API
    * @param string $uri : Request URI (without parameters)
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
                'accept' => 'application/ld+json',
                'Content-Type' => 'application/ld+json',
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

        return json_encode($this->content);
    }


    /**
    * @method patch()
    * Runs an HTTP PATCH request to the API
    * @param string $uri : Request URI (without parameters)
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
                'accept' => 'application/ld+json',
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

        return json_encode($this->content);
    }


    /**
    * @method delete()
    * Runs an HTTP DELETE request to the API
    * @param string $uri : Request URI (without parameters)
    * @param string $id : Object ID
    * @return mixed Associative array representing response
    */
    public function delete($uri = '', $id = '')
    {
        if (!$uri) return false;

        $response = $this->httpClient->request('DELETE', $this->getApiUrl().$uri.'/'.$id, [
            /* Removes SSL certificate verification */
            'verify_peer' => false,
            'verify_host' => false,
            /* Set specific headers */
            'headers' => [
                'accept' => 'application/ld+json',
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
        
        return json_encode($this->content);
    }


    /**
    * @method addParameter()
    * Sets a query string parameter (&name=value)
    * @param string $name : Request parameter's name
    * @param string $value : Request paramter's value
    * @return boolean
    */
    public function addParameter($name = '', $value = '')
    {
        if (!$name || !$value) {
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
    * @method removeParameter()
    * Removes a query string parameter (&name=value)
    * @param string $name : Request parameter's name
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
    * @method getQueryStringAdditional()
    * Returns the query string (additional part)
    * @return string
    */
    protected function getQueryStringAdditional()
    {
        return $this->queryStringAdditional;
    }


    /**
    * @method getQueryString()
    * Returns the query string (main part)
    * @return string
    */
    protected function getQueryString()
    {
        return $this->queryString;
    }


    /**
    * @method setPage()
    * Defines the page (Pagination)
    * @param int $p : Page number
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
    * @method getMaxPage()
    * Returns the maximum page for the result list
    * @param void
    * @return int number of last page
    */
    public function getMaxPage()
    {
        return intval(($this->maxPage ? $this->maxPage : '1'));
    }


    /**
    * @method setMaxPage()
    * Sets the maximum page for the result list
    * @param array $content : Associative array of API result
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
    * @method setTotalItems()
    * Sets the number of items total for the result list
    * @param array $content : Associative array of API result
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
    * @method getTotalItems()
    * Returns the total number of items for the result list
    * @param void
    * @return int number of items total
    */
    public function getTotalItems()
    {
        return $this->totalItems;
    }


    /**
    * @method setOrder()
    * Sets the query order
    * @param string property : field to sort
    * @param string sort : order sort (asc|desc)
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
    * @method getOrder()
    * Gets the query order
    * @return array Associative array
    */
    public function getOrder()
    {
        return array($this->orderProperty, $this->orderSort);
    }


}