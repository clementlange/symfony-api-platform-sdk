<?php

/**
 * @since   July 05 2023
 * @author  Clément Lange <clement@awelty.com>
 * @version 1.0
 *
 * Cegid API PHP SDK for Symfony
 * Specific to CEGID CRM and herits from ApiPlatformSdk
 */

namespace App\Service\ApiPlatformSdk;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ApiTokenRepository;

class Cegid extends ApiPlatformSdk
{
    /**
     * Base API URL
     * Can still be overridden with method setApiUrl()
     */
    private const DEFAULT_API_URL       = 'https://xrp-flex.cegid.cloud/';

    /**
     * Company slug on API
     * Is concatenated to API URL
     * ie: API URL = https://<api_url>/<COMPANY_SLUG>/[...]
     */
    private const COMPANY_SLUG          = 'company-slug';

    /**
     * After the company slug, at the end of the API URL
     * ie : Full API URL = https://<api_url>/<COMPANY_SLUG>/<API_URL_CONCAT>
     */
    private const API_URL_CONCAT        = 'entity/Default/22.200.001';

    /**
     * Default format (API extension)
     */
    private const DEFAULT_FORMAT        = 'json';
    private const CONCAT_FORMAT         = false;

    /**
     * Default Accept and Content-Type headers
     */
    private const DEFAULT_ACCEPT        = 'application/json';
    private const DEFAULT_CONTENT_TYPE  = 'application/json';

    /**
     * Pagination items by page
     */
    private const PAGING_ITEMS_PER_PAGE = 20;

    /**
     * Default credentials (authentication)
     * Set credentials here if they are constants,
     * or use $cegid->authenticate('login', 'password') to override credientials on the fly
     */
    private const HAS_AUTHENTICATION    = true;                         // true or false if this API requires authentication
    private const TOKEN_LIFETIME_MINS   = 30;                           // Number of MINUTES of token validity. Deleted after that.
    private const AUTHENTICATION_METHOD = 'oauth2';                     // "jwt" is default for API Platform. Other choice can be : "oauth2" for OAuth 2.0.
    private const AUTHENTICATION_URI    = 'identity/connect/token';     // Authentication URI on the API ("login_check" if URI is "/login_check")

    // The Auth URL has a different base URL than API URL, will be overridden in full. LEAVE EMPTY if not applicable.
    private const OVERRIDDEN_AUTH_URL   = 'https://xrp-flex.cegid.cloud/company-slug/identity/connect/token';

    /**
     * ERP Credentials
     */
    private const DEFAULT_LOGIN         = 'API';            // Login
    private const DEFAULT_PASSWORD      = 'mypassword$#';   // Password

    /**
     * OAuth 2.0 specific
     */
    private const OAUTH2_CLIENT_ID      = '7E046684-738C-325A-7AEB-CF42D3BDECE4@COMPANY';
    private const OAUTH2_CLIENT_SECRET  = 'Y_WJjHDQxXGEYQdHBwsNMg';
    private const OAUTH2_REQUEST_SCOPE  = 'api';
    private const OAUTH2_GRANT_TYPE     = 'password';


    /**
     * __construct
     *
     * @return void
     */
    public function __construct(EntityManagerInterface $em, ApiTokenRepository $apiTokenRepository)
    {
        // Initialize with default credentials and configuration
        $this->setApiUrl(trim(self::DEFAULT_API_URL, '/') . '/' . self::COMPANY_SLUG . '/' . trim(self::API_URL_CONCAT, '/'));
        $this->setFormat(self::DEFAULT_FORMAT);
        $this->setAccept(self::DEFAULT_ACCEPT);
        $this->setConcatFormat(self::CONCAT_FORMAT);
        $this->setContentType(self::DEFAULT_CONTENT_TYPE);

        // Authenticate if necessary
        // if (self::HAS_AUTHENTICATION) {
        $this->setLogin(self::DEFAULT_LOGIN);
        $this->setPassword(self::DEFAULT_PASSWORD);
        $this->setAuthenticationUri(self::COMPANY_SLUG . '/' . trim(self::AUTHENTICATION_URI, '/'));
        $this->setOverriddenAuthUrl(self::OVERRIDDEN_AUTH_URL);
        $this->setTokenLifetime(self::TOKEN_LIFETIME_MINS);

        // OAuth 2.0 specific
        // if (self::AUTHENTICATION_METHOD == 'oauth2') {
        $this->setOAuth2ClientId(self::OAUTH2_CLIENT_ID);
        $this->setOAuth2ClientSecret(self::OAUTH2_CLIENT_SECRET);
        $this->setOAuth2RequestScope(self::OAUTH2_REQUEST_SCOPE);
        $this->setOAuth2GrantType(self::OAUTH2_GRANT_TYPE);
        // }
        // }

        // Construct parent object
        parent::__construct(
            $em,
            $apiTokenRepository,
            self::HAS_AUTHENTICATION,
            self::AUTHENTICATION_METHOD
        );
    }


    /**
     * Reset query string parameters
     * and other instance params
     *
     * @return void
     */
    private function resetParameters(): void
    {
        $this->queryString = [];
        $this->postData = [];
        $this->queryStringAdditional = '';
        $this->orderProperty = '';
        $this->orderSort = '';
        $this->page = 0;
        $this->maxPage = 0;
        $this->totalItems = 0;
    }


    /**
     * Acumatica(Cegid)-specific offset/limit pagination
     * Will override the setPage() and getPage() default methods,
     * as paging is not "?page=X", but "?$top=<limit>&$skip=<offset>"
     * $top and $skip params will be added to query string.
     *
     * @param  int $page
     * @return void
     */
    public function setOffsetLimit($page = 1): void
    {
        $limit = self::PAGING_ITEMS_PER_PAGE;
        $offset = ($page * self::PAGING_ITEMS_PER_PAGE) - self::PAGING_ITEMS_PER_PAGE;
        ;

        $this->addParameter('$top', (string)$limit);
        $this->addParameter('$skip', (string)$offset);
    }


    /**
     * Returns a list of Customers
     *
     * @param int $page Page number
     * @param array<int, string> $select List of fields to return
     * @param array<int, string> $expand List of sub-fields to expand
     * @return mixed
     */
    public function getCustomers($page = 1, $select = [], $expand = [])
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setOffsetLimit($page);
        }

        // Which fields to return
        if (!empty($select)) {
            $this->addParameter('$select', implode(',', $select));
        }

        // Expand fields
        if (!empty($expand)) {
            $this->addParameter('$expand', implode(',', $expand));
        }

        // API request & return
        return $this->get('Customer');
    }


    /**
     * Returns a list of CustomerLocations
     *
     * @param int $page Page number
     * @param array<int, string> $select List of fields to return
     * @param array<int, string> $expand List of sub-fields to expand
     * @return mixed
     */
    public function getCustomerLocations($page = 1, $select = [], $expand = [])
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setOffsetLimit($page);
        }

        // Which fields to return
        if (!empty($select)) {
            $this->addParameter('$select', implode(',', $select));
        }

        // Expand fields
        if (!empty($expand)) {
            $this->addParameter('$expand', implode(',', $expand));
        }

        // API request & return
        return $this->get('CustomerLocation');
    }


    /**
     * Returns a list of CustomerLocations
     *
     * @param string $id CustomerLocation ID
     * @param array<int, string> $select List of fields to return
     * @param array<int, string> $expand List of sub-fields to expand
     * @return mixed
     */
    public function getCustomerLocation($id = '', $select = [], $expand = [])
    {
        $this->resetParameters();

        // Which fields to return
        if (!empty($select)) {
            $this->addParameter('$select', implode(',', $select));
        }

        // Expand fields
        if (!empty($expand)) {
            $this->addParameter('$expand', implode(',', $expand));
        }

        // API request & return
        return $this->get('CustomerLocation/'.$id);
    }


    /**
     * Returns a list of Contacts
     *
     * @param  int $page
     * @param array<int, string> $select List of fields to return
     * @param array<int, string> $expand List of sub-fields to expand
     * @return mixed
     */
    public function getContacts($page = 1, $select = [], $expand = [])
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setOffsetLimit($page);
        }

        // Which fields to return
        if (!empty($select)) {
            $this->addParameter('$select', implode(',', $select));
        }

        // Expand fields
        if (!empty($expand)) {
            $this->addParameter('$expand', implode(',', $expand));
        }

        // API request & return
        return $this->get('Contact');
    }
}
