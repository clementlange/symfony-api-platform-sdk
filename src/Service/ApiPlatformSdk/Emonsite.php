<?php
/**
 * @since   May 07 2021
 * @author  clement@awelty.com
 * @version 1.2
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
    private const DEFAULT_API_URL       = 'https://api.e-monsite.com/';
    // private const DEFAULT_API_URL       = 'https://api.awelty.com/';

    /**
     * Default format (API extension)
     */
    private const DEFAULT_FORMAT        = 'jsonld';
    private const CONCAT_FORMAT         = true;

    /**
     * Default Accept and Content-Type headers
     */
    private const DEFAULT_ACCEPT        = 'application/ld+json';
    private const DEFAULT_CONTENT_TYPE  = 'application/ld+json';

    /**
     * Default credentials (authentication)
     * Set credentials here if they are constants,
     * or use $emonsite->authenticate('login', 'password') to override credientials on the fly
     */
    private const HAS_AUTHENTICATION    = true;                     // true or false if this API requires authentication
    private const AUTHENTICATION_METHOD = 'jwt';                    // "jwt" is default for API Platform. Other choice can be : "oauth2" for OAuth 2.0.
    private const AUTHENTICATION_URI    = 'auth';                   // Authentication URI on the API ("login_check" if URI is "/login_check")
    private const DEFAULT_LOGIN         = 'email@example.com';      // API login
    private const DEFAULT_PASSWORD      = 'myPassword';             // API password

    /**
     * Default e-monsite site ID
     * Can be overridden at any time in controller with $emonsite->setSideId(id)
     */
    private const DEFAULT_SITE_ID       = '3e8269167b866fde4dbc2c2a';


    /**
     * __construct
     * @return void
     */
    public function __construct(EntityManagerInterface $em, ApiTokenRepository $apiTokenRepository)
    {
        // Initialize with default credentials and configuration
        $this->setApiUrl(self::DEFAULT_API_URL);
        $this->setFormat(self::DEFAULT_FORMAT);
        $this->setConcatFormat(self::CONCAT_FORMAT);
        $this->setAccept(self::DEFAULT_ACCEPT);
        $this->setContentType(self::DEFAULT_CONTENT_TYPE);

        // Sets default e-monsite site ID
        $this->setSiteId(self::DEFAULT_SITE_ID);

        // Authenticate if necessary
        if (self::HAS_AUTHENTICATION) {
            $this->setLogin(self::DEFAULT_LOGIN);
            $this->setPassword(self::DEFAULT_PASSWORD);
            $this->setAuthenticationUri(self::AUTHENTICATION_URI);
        }

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
     * Sets the EMS site ID in the current instance
     *
     * @param  string $siteId
     * @return void
     */
    public function setSiteId($siteId = '')
    {
        $this->siteId = $siteId;
    }


    /**
     * Returns the EMS site ID
     *
     * @return string
     */
    protected function getSiteId()
    {
        return $this->siteId;
    }


    /**
     * Return the list of orders from the EMS Store
     *
     * @return mixed
     */
    public function getEcoOrders($page = 1)
    {
        $this->resetParameters();

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
     * Return a single EcoOrder
     *
     * @param  string $id Order id
     * @return mixed
     */
    public function getEcoOrder($id)
    {
        $this->resetParameters();

        // By default, set descending order on date
        $this->setOrder('addDt', 'desc');

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->getSingle('eco_orders', $id);
    }


    /**
     * Updates some fields of an EcoOrder
     *
     * @param  string $id Order id
     * @param  array $data Order data
     * @return mixed
     */
    public function patchEcoOrder($id, $data)
    {
        $this->resetParameters();

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->patch('eco_orders/' . $id, $data);
    }


    /**
     * Return the list of blog posts from the EMS site
     *
     * @param  string $id Blog post id
     * @return mixed
     */
    public function getBlogPosts($page = 1)
    {
        $this->resetParameters();

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
     * Return a single blog post from the EMS site
     *
     * @return mixed
     */
    public function getBlogPost($id)
    {
        $this->resetParameters();

        // By default, set descending order on publish date
        $this->setOrder('publishFrom', 'desc');

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->getSingle('blog_posts', $id);
    }


    /**
     * Return a list of ecoProducts
     *
     * @param int $page Page number
     * @return mixed
     */
    public function getEcoProducts($page = 1)
    {
        $this->resetParameters();

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
     * Return a single eco product
     *
     * @param  string $id Eco product id
     * @return mixed
     */
    public function getEcoProduct($id, $page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        // By default, set descending order on creation date
        $this->setOrder('createdAt', 'desc');

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->getSingle('eco_products', $id);
    }


    /**
     * Create a new eco product
     *
     * @param  array $data Eco product data
     * @return mixed
     */
    public function postEcoProduct($data)
    {
        $this->resetParameters();

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->post('eco_products', $data);
    }


    /**
     * Update some fields of the eco product
     *
     * @param  string $id Eco product id
     * @param  array $data Eco product data
     * @return mixed
     */
    public function patchEcoProduct($id, $data)
    {
        $this->resetParameters();

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->patch('eco_products/' . $id, $data);
    }


    /**
     * Delete the eco product from the EMS site
     *
     * @param  string $id Eco product id
     * @return mixed
     */
    public function deleteEcoProduct($id)
    {
        $this->resetParameters();

        // Set query parameter "site_id"
        $this->addParameter('site_id', $this->getSiteId());

        // API request & return
        return $this->delete('eco_products/' . $id);
    }


    /**
     * Return the categories from EMS Store
     *
     * @param int $page Page number
     * @return mixed
     */
    public function getCategories($page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        // By default, set descending order on creation date
        $this->setOrder('createdAt', 'desc');

        $this->addParameter('site_id', $this->getSiteId());

        return $this->get('categories');
    }


    /**
     * Get all product attributes on a store
     *
     * @param int $page Page number
     * @return mixed
     */
    public function getProductAttributes($page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        $this->addParameter('site_id', $this->getSiteId());

        return $this->get('eco_product_attributes');
    }


    /**
     * Return the product attribute from EMS Store
     *
     * @param  string $id : eco product attribute id
     * @param int $page Page number
     * @return mixed
     */
    public function getProductAttribute($id, $page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        $this->addParameter('site_id', $this->getSiteId());

        return $this->getSingle('eco_product_attributes', $id);
    }


    /**
     * Post the product attribute to EMS Store
     *
     * @param  array $data : eco product attribute data
     * @return mixed
     */
    public function postProductAttribute($data)
    {
        $this->resetParameters();

        $this->addParameter('site_id', $this->getSiteId());

        return $this->post('eco_product_attributes', $data);
    }


    /**
     * Patch the product attribute to EMS Store
     *
     * @param  string $id : eco product attribute id
     * @param  array $data : eco product attribute data
     * @return mixed
     */
    public function patchProductAttribute($id, $data)
    {
        $this->resetParameters();

        $this->addParameter('site_id', $this->getSiteId());

        return $this->patch('eco_product_attributes/' . $id, $data);
    }


    /**
     * Delete the product attribute from EMS Store
     *
     * @param  string $id Eco product attribute id
     * @return mixed
     */
    public function deleteProductAttribute($id)
    {
        $this->resetParameters();

        $this->addParameter('site_id', $this->getSiteId());

        return $this->delete('eco_product_attributes/' . $id);
    }


    /**
     * Return the product attribute values from EMS Store
     *
     * @param   int $page Page number
     * @return  mixed
     */
    public function getProductAttributeValues($page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        $this->addParameter('site_id', $this->getSiteId());

        return $this->get('eco_product_attribute_values');
    }


    /**
     * Return the eco product variations from EMS Store
     *
     * @param   int $page Page number
     * @return  mixed
     */
    public function getEcoProductVariations($page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        $this->addParameter('site_id', $this->getSiteId());

        return $this->get('eco_product_variations');
    }


    /**
     * Post the eco product variation to EMS Store
     *
     * @param   array $data : eco product variation data
     * @return  mixed
     */
    public function postEcoProductVariation($data)
    {
        $this->resetParameters();

        $this->addParameter('site_id', $this->getSiteId());

        return $this->post('eco_product_variations', $data);
    }


    /**
     * Patch the eco product variation to EMS Store
     *
     * @param   string $id : eco product variation id
     * @param   array $data : eco product variation data
     * @return  mixed
     */
    public function patchEcoProductVariation($id, $data)
    {
        $this->addParameter('site_id', $this->getSiteId());

        return $this->patch('eco_product_variations/' . $id, $data);
    }


    /**
     * Delete the eco product variation from EMS Store
     *
     * @param   string $id Eco product variation id
     * @return  mixed
     */
    public function deleteEcoProductVariation($id)
    {
        $this->resetParameters();

        $this->addParameter('site_id', $this->getSiteId());

        return $this->delete('eco_product_variations/' . $id);
    }


    /**
     * Upload an image POST /storage_images and returns response
     *
     * @param   string $path Image file path
     * @return  mixed
     */
    public function createStorageImage($path = '')
    {
        $this->resetParameters();

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
