<?php
/**
 * @since   May 07 2021
 * @author  clement@awelty.com
 * @version 1.0
 * 
 * E-monsite PHP SDK for Symfony
 * Specific to e-monsite and herits from ApiPlatformSdk
 */

namespace App\Service\ApiPlatformSdk;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ApiTokenRepository;

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
    const DEFAULT_API_URL   = 'https://api.e-monsite.com/';

    /**
     * Default format (API extension)
     */
    const DEFAULT_FORMAT    = 'jsonld';

    /**
     * Default credentials (authentication)
     * Set credentials here if they are constants,
     * or use $emonsite->authenticate('login', 'password') to override credientials on the fly
     */
    const DEFAULT_LOGIN     = 'me@example.com';
    const DEFAULT_PASSWORD  = 'mypassword';


    /**
     * __construct
     *
     * @return void
     */
    public function __construct(EntityManagerInterface $em, ApiTokenRepository $apiTokenRepository)
    {
        // Initialize with default credentials and configuration
        $this->setApiUrl(self::DEFAULT_API_URL);
        $this->setLogin(self::DEFAULT_LOGIN);
        $this->setPassword(self::DEFAULT_PASSWORD);
        $this->setFormat(self::DEFAULT_FORMAT);

        parent::__construct($em, $apiTokenRepository);
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

}