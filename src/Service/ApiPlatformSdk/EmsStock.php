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
    const DEFAULT_API_URL       = 'https://api.ems-stock.dev/';

    /**
     * Default format (API extension)
     */
    const DEFAULT_FORMAT        = 'jsonld';

    /**
     * Default Accept and Content-Type headers
     */
    const DEFAULT_ACCEPT        = 'application/ld+json';
    const DEFAULT_CONTENT_TYPE  = 'application/ld+json';

    /**
     * Default credentials (authentication)
     * Set credentials here if they are constants,
     * or use $emsstock->authenticate('login', 'password') to override credientials on the fly
     */
    const HAS_AUTHENTICATION    = false;
    const DEFAULT_LOGIN         = '';
    const DEFAULT_PASSWORD      = '';


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
        if (self::HAS_AUTHENTICATION)
        {
            $this->setLogin(self::DEFAULT_LOGIN);
            $this->setPassword(self::DEFAULT_PASSWORD);
        }

        // Construct parent object
        parent::__construct(self::HAS_AUTHENTICATION, $em, $apiTokenRepository);
    }

    /**
     * getBrands
     *
     * @return mixed
     * 
     * Return the brands
     */
    public function getBrands($page = 1)
    {
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
     *
     * @return mixed
     * 
     * Return the products
     */
    public function getProducts($page = 1)
    {
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