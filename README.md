# Symfony API Platform SDK

PHP SDK for Symfony API Platform, and derivated SDKs based on API Platform.

## Installation

Copy files and folders in corresponding folders of your Symfony project.

- /Entity/ApiToken.php
- /Repository/ApiTokenRepository.php
- /Service/ApiPlatformSdk/ (and subclasses)

**Important** : The ApiToken entities use UUID as unique ID. You need to add `ramsey/uuid` and `ramsey/uuid-doctrine` to your Symfony project :

- `composer require ramsey/uuid`
- `composer require ramsey/uuid-doctrine`

Don't forget to create and run **Doctrine migrations** to update your database with your new ApiToken entity.

- `php bin/console make:migration`
- `php bin/console doctrine:migrations:migrate`

## Usage

**As derivated SDK (for example Emonsite) :**

First, edit the constants in the derivated SDK class (here Service\Sdk\Emonsite : DEFAULT_API_URL, DEFAULT_API_FORMAT...).

Example controller using Emonsite SDK (derivated class from `ApiPlatformSdk`, which can also be used as standalone SDK) :

```php
use App\Service\ApiPlatformSdk\Emonsite;

class MyController
{
	private     $emonsite;
	
	public function __construct(Emonsite $emonsite)
	{
		$this->emonsite = $emonsite;
	}
	
	public function index()
	{
		// Define e-monsite site ID / Not required if ApiPlatformSdk is used as standalone
		$this->emonsite->setSiteId('536424be8e905c8c5cbbf781');
		
		// Load store orders
		$orders = $this->emonsite->getEcoOrders();
		
		// Dumping var
		dump($orders);
	}
}
```

**As standalone ApiPlatformSdk :**

The SDK can also be used as standalone, without specific SDK loaded. You will have to explicitly declare API URL, format, and credentials / request for authorization token (if applicable).

Example controller using ApiPlatformSdk as standalone :

```php
use App\Service\ApiPlatformSdk\ApiPlatformSdk;

class MyController
{
	private     $apiPlatformSdk;
	
	public function __construct(ApiPlatformSdk $apiPlatformSdk)
	{
		$this->apiPlatformSdk = $apiPlatformSdk;
	}
	
	public function index()
	{
		// Set API URL
		$this->apiPlatformSdk->setApiUrl('https://api.example.com/');

		// Set API Format
		$this->apiPlatformSdk->setFormat('jsonld');
		
		// If your target API requires an authentication :
		// Set Login and Password for API Authorization, then request token
		$this->apiPlatformSdk->setHasAuthentication(true);
		$this->apiPlatformSdk->setLogin('me@example.com');
		$this->apiPlatformSdk->setPassword('mypassword');
		$this->apiPlatformSdk->authenticate();

		// Perform a GET request
		// for example : GET /products?page=3&provider=/providers/6&site_id=536424be8e905c8c5cbbf781
		$this->apiPlatformSdk->setPage(3); // Add pagination (if needed) : page 3
		$this->apiPlatformSdk->addParameter('provider' => '/providers/6'); // Add query string parameter : &provider=/providers/6
		$this->apiPlatformSdk->addParameter('site_id' => '536424be8e905c8c5cbbf781'); // Add query string parameter : &site_id=536424be8e905c8c5cbbf781
		
		// API Request : /products
		$products = $this->apiPlatformSdk->get('products');

		dump($products);
	}
}
```


Other available verbs include:

```php
// HTTP POST
$apiPlatformSdk->post('/uri');

// HTTP PATCH
$apiPlatformSdk->patch('/uri');

// HTTP PUT
$apiPlatformSdk->put('/uri');

// HTTP DELETE
$apiPlatformSdk->delete('/uri');
```