<?php
/**
 * @since   May 07 2021
 * @author  clement@awelty.com
 * @version 1.1
 * 
 * E-monsite PHP SDK for Symfony
 * Specific to e-monsite and herits from ApiPlatformSdk
 */

namespace App\Service\ApiPlatformSdk;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ApiTokenRepository;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;

class Emonsite extends ApiPlatformSdk
{
    /**
     * Attributes
     */
    protected $siteId;


    /**
     * Change default API URL depending on the white label :
     * "api.awelty.com" for sites on the Awelty whitelabel / "api.e-monsite.com" for sites on the E-monsite whitelabel
     * API URL can still be overridden with method setApiUrl()
     */
    const DEFAULT_API_URL       = 'https://api.e-monsite.com/';

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
     * or use $emonsite->authenticate('login', 'password') to override credientials on the fly
     */
    const HAS_AUTHENTICATION    = true;                     // true or false if this API requires authentication
    const AUTHENTICATION_URI    = 'auth';                   // Authentication URI on the API ("login_check" if URI is "/login_check")
    const DEFAULT_LOGIN         = 'email@example.com';      // API login
    const DEFAULT_PASSWORD      = 'myPassword';             // API password


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

        if (self::HAS_AUTHENTICATION)
        {
            $this->setLogin(self::DEFAULT_LOGIN);
            $this->setPassword(self::DEFAULT_PASSWORD);
            $this->setAuthenticationUri(self::AUTHENTICATION_URI);
        }

        parent::__construct($em, $apiTokenRepository, self::HAS_AUTHENTICATION);
    }


    /**
     * setSiteId
     *
     * @param  string $siteId
     * @return void
     */
    public function setSiteId($siteId = '')
    {
        $this->siteId = $siteId;
    }
    

    /**
     * getSiteId
     *
     * @return string
     */
    protected function getSiteId()
    {
        return $this->siteId;
    }


    /**
     * getEcoOrders
     *
     * @return mixed
     * 
     * Return the orders from the EMS Store
     */
    public function getEcoOrders($page = 1)
    {
        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        // By default, set descending order on date
        $this->setOrder('addDt', 'desc');

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->get('eco_orders');
    }


    /**
     * getBlogPosts
     *
     * @return mixed
     * 
     * Return the blog posts from the EMS site
     */
    public function getBlogPosts($page = 1)
    {
        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        // By default, set descending order on publish date
        $this->setOrder('publishFrom', 'desc');

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->get('blog_posts');
    }


    /**
     * getEcoProducts
     *
     * @return mixed
     *
     * Return the eco products from the EMS site
     */
    public function getEcoProducts($page = 1)
    {
        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        // By default, set descending order on creation date
        $this->setOrder('createdAt', 'desc');

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->get('eco_products');
    }


    /**
     * createStorageImage
     *
     * @param string $path : image file path
     * @return mixed
     *
     * Upload an image POST /storage_images and returns response
     */
    public function createStorageImage($path = '')
    {
        // make payload
        $formFields = [
            'image[siteId]' => $this->getSiteId(),
            'image[file]' => DataPart::fromPath($path)
        ];
       
        $formData = new FormDataPart($formFields);

        // Create specific headers (Content-Type = multipart/form-data)
        $headers = $formData->getPreparedHeaders()->toArray();
        $headers['accept'] = 'application/ld+json';
       
        return $this->post('storage_images',
            $formData->bodyToIterable(),
            $headers
        );
    }

}