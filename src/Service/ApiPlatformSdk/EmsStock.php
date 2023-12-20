<?php
/**
 * @since   May 07 2021
 * @author  clement@awelty.com
 * @version 1.0
 *
 * Ems-Stock PHP SDK for Symfony
 * Specific to e-monsite and herits from ApiPlatformSdk
 */

namespace App\Service\ApiPlatformSdk;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ApiTokenRepository;

class EmsStock extends ApiPlatformSdk
{
    /**
     * API URL can still be overridden with method setApiUrl($url)
     */
    private const DEFAULT_API_URL       = 'https://api.ems-stock.dev/';

    /**
     * Default format (API extension)
     */
    private const DEFAULT_FORMAT        = 'jsonld';

    /**
     * Default Accept and Content-Type headers
     */
    private const DEFAULT_ACCEPT        = 'application/ld+json';
    private const DEFAULT_CONTENT_TYPE  = 'application/ld+json';

    /**
     * Default credentials (authentication)
     * Set credentials here if they are constants,
     * or use $emsstock->authenticate('login', 'password') to override credientials on the fly
     */
    private const HAS_AUTHENTICATION    = false;
    private const DEFAULT_LOGIN         = '';
    private const DEFAULT_PASSWORD      = '';


    /**
     * __construct
     *
     * @return void
     */
    public function __construct(EntityManagerInterface $em, ApiTokenRepository $apiTokenRepository)
    {
        // Initialize with default credentials and configuration
        $this->setApiUrl(self::DEFAULT_API_URL);
        $this->setFormat(self::DEFAULT_FORMAT);
        $this->setAccept(self::DEFAULT_ACCEPT);
        $this->setContentType(self::DEFAULT_CONTENT_TYPE);

        // Authenticate if necessary
        if (self::HAS_AUTHENTICATION) {
            $this->setLogin(self::DEFAULT_LOGIN);
            $this->setPassword(self::DEFAULT_PASSWORD);
        }

        // Construct parent object
        parent::__construct(
            $em,
            $apiTokenRepository,
            self::HAS_AUTHENTICATION
        );
    }


    /**
     * Reset query string parameters
     * and other instance params
     *
     * @return void
     */
    private function resetParameters()
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
     * getBrands
     * Return the brands
     *
     * @return mixed
     */
    public function getBrands($page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        // By default, set ascending order on name
        $this->setOrder('name', 'asc');

        // API request & return
        return $this->get('brands');
    }


    /**
     * getProducts
     * Return the products
     *
     * @return mixed
     */
    public function getProducts($page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        // By default, set descending order on creation date
        $this->setOrder('createdAt', 'desc');

        // API request & return
        return $this->get('products');
    }

}
