<?php
/**
 * @since   July 05 2023
 * @author  Clément Lange <clement@awelty.com>
 * @version 1.0
 *
 * E-confiance.fr PHP SDK for Symfony
 * Specific to e-confiance.fr and herits from ApiPlatformSdk
 */

namespace App\Service\ApiPlatformSdk;

use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ApiTokenRepository;

class Econfiance extends ApiPlatformSdk
{
    /**
     * API URL can still be overridden with method setApiUrl()
     */
    private const DEFAULT_API_URL       = 'https://certification.e-confiance.fr/api/';

    /**
     * Default format (API extension)
     */
    private const DEFAULT_FORMAT        = 'jsonld';
    private const CONCAT_FORMAT         = false;

    /**
     * Default Accept and Content-Type headers
     */
    private const DEFAULT_ACCEPT        = 'application/ld+json';
    private const DEFAULT_CONTENT_TYPE  = 'application/ld+json';

    /**
     * Default credentials (authentication)
     * Set credentials here if they are constants,
     * or use $econfiance->authenticate('login', 'password') to override credientials on the fly
     */
    private const HAS_AUTHENTICATION    = true;                 // true or false if this API requires authentication
    private const AUTHENTICATION_METHOD = 'jwt';                // "jwt" is default for API Platform. Other choice can be : "oauth2" for OAuth 2.0.
    private const AUTHENTICATION_URI    = 'login_check';        // Authentication URI on the API ("login_check" if URI is "/login_check")
    private const DEFAULT_LOGIN         = 'companyslug';        // API Login
    private const DEFAULT_PASSWORD      = 'myapipassword';      // API password

    /**
     * Company ID on E-confiance
     * ID can be found in API return for each Authentication request
     */
    private const COMPANY_ID            = 56;


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
        $this->setConcatFormat(self::CONCAT_FORMAT);
        $this->setContentType(self::DEFAULT_CONTENT_TYPE);

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
     * Returns a list of Company items
     *
     * @param  int $page Page number
     * @return mixed
     */
    public function getCompanies($page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        // API request & return
        return $this->get('companies');
    }


    /**
     * Returns a single Company item
     *
     * @param  string $id Company ID
     * @return mixed
     */
    public function getCompany($id = '')
    {
        $this->resetParameters();

        if (empty($id)) {
            $id = self::COMPANY_ID;
        }

        // API request & return
        return $this->getSingle('companies', $id);
    }


    /**
     * Returns a single Company global-rating item
     *
     * @param  string $id Company ID
     * @return mixed
     */
    public function getCompanyGlobalRating($id = '')
    {
        $this->resetParameters();

        if (empty($id)) {
            return false;
        }

        // API request & return
        return $this->getSingle('companies/global-rating', $id);
    }


    /**
     * Returns a single ProductOrder item
     *
     * @param  string $id ProductOrder ID
     * @return mixed
     */
    public function getProductOrder($id = '')
    {
        $this->resetParameters();

        if (empty($id)) {
            return false;
        }

        // API request & return
        return $this->getSingle('product_orders', $id);
    }


    /**
     * Returns a list of Product Reviews
     *
     * @param  int $page Page number
     * @return mixed
     */
    public function getProductReviews($page = 1)
    {
        $this->resetParameters();

        // Load specific page
        if (is_numeric($page)) {
            $this->setPage($page);
        }

        // API request & return
        return $this->get('product_reviews');
    }


    /**
     * Returns a single Product Review item
     *
     * @param  string $id ProductReview ID
     * @return mixed
     */
    public function getProductReview($id = '')
    {
        $this->resetParameters();

        if (empty($id)) {
            return false;
        }

        // API request & return
        return $this->getSingle('product_reviews', $id);
    }


    /**
     * Returns the average rating of a Product
     *
     * @param  string $reference Product reference
     * @return mixed
     */
    public function getProductReviewAverage($reference = '')
    {
        $this->resetParameters();

        if (empty($reference)) {
            return false;
        }

        // API request & return ; Must concat Company slug and Product reference
        return $this->getSingle('product_reviews/average', self::DEFAULT_LOGIN.'/'.$reference);
    }


    /**
     * Returns a single Order item
     *
     * @param  string $id Order ID
     * @return mixed
     */
    public function getOrder($id = '')
    {
        $this->resetParameters();

        if (empty($id)) {
            return false;
        }

        // API request & return
        return $this->getSingle('orders', $id);
    }


    /**
     * Create a Order item
     *
     * @param string $orderNumber Order Number
     * @param string $customerEmail Customer email address
     * @param string|null $firstname Customer firstname
     * @param string|null $lastname Customer lastname
     * @param boolean $mailSent If set to "true", no mail will be sent to customer (default)
     * @param string|null $customerPhone Customer phone number
     * @return mixed
     */
    public function createOrder(
        $orderNumber = '',
        $customerEmail = '',
        $firstname = null,
        $lastname = null,
        $mailSent = true,
        $customerPhone = null
    )
    {
        $this->resetParameters();

        if (empty($orderNumber) || empty($customerEmail)) {
            return false;
        }
        else {
            // API request
            return $this->post('orders', [
                'orderNumber' => $orderNumber,
                'customerEmail' => $customerEmail,
                'firstname' => $firstname,
                'lastname' => $lastname,
                'mailSent' => $mailSent,
                'customerPhone' => $customerPhone
            ]);
        }
    }


    /**
     * Creates both an Order and a Product
     * This function requires the Order ID, so createOrder() must have been called before to get Order ID
     *
     * @param $orderId ID order returned by createOrder
     * @param $productName Product name
     * @param $productReference Product reference
     * @param $freeField Free text field
     * @param $productImageUrl Image URL to join to the review
     * @param $followUp ?
     * @param $productLink Product URL to join to the review
     * @return mixed
     */
    public function createProductOrder(
        $orderId = 0,
        $productName = '',
        $productReference = '',
        $productImageUrl = '',
        $productLink = '',
        $freeField = '',
        $followUp = 0
    )
    {
        $this->resetParameters();

        // API request
        return $this->post('product_orders', [
            'orderParent' => '/api/orders/'.$orderId,
            'name' => $productName,
            'reference' => $productReference,
            'freeField' => $freeField,
            'image' => $productImageUrl,
            'followUp' => $followUp,
            'link' => $productLink
        ]);
    }


    /**
     * Creates a Product Review.
     * You must know the Order ID (Order have to be already created) to pass it as IRI.
     * So createOrder() must have been called prior.
     *
     * @param $orderId ID order returned by createOrder
     * @param $productName Product name
     * @param $productReference Product reference
     * @param $productImageUrl Image URL to join to the review
     * @param $productLink Product URL to join to the review
     * @param $reviewContent Text content of the product review
     * @param $reviewRating Rating (1 to 5)
     * @param $reviewStatus Status : "pending" (awaiting moderation) or "published"
     * @param $customerIp IP adress of customer
     * @param $browserUserAgent Browser User-agent of Customer
     * @param $freeField Free text field
     * @return mixed
     */
    public function createProductReview(
        $orderId = 0,
        $productName = '',
        $productReference = null,
        $productImageUrl = null,
        $productLink = null,
        $reviewContent = '',
        $reviewRating = 0,
        $reviewStatus = 'pending',
        $customerIp = '',
        $browserUserAgent = null,
        $freeField = null
    )
    {
        $this->resetParameters();

        // Review status can be "pending" (awaiting moderation) or "published"
        if ($reviewStatus == 'published') {
            $statusIri = '/api/review_statuses/1';
        }
        else {
            $statusIri = '/api/review_statuses/2';
        }

        // API request
        return $this->post('product_reviews', [
            'orderParent' => '/api/orders/'.$orderId,
            'productName' => $productName,
            'reference' => $productReference,
            'freeField' => $freeField,
            'image' => $productImageUrl,
            'link' => $productLink,
            'content' => $reviewContent,
            'rating' => intval($reviewRating),
            'status' => $statusIri,
            'browser' => [
                $browserUserAgent
            ],
            'customerIp' => $customerIp,
            'company' => '/api/companies/'.self::COMPANY_ID
        ]);
    }

}
