# Symfony API Platform SDK

PHP SDK for Symfony API Platform, and derivated SDKs based on API Platform.

## Installation

Copy files and folders in corresponding folders of your Symfony project.

- /Entity/ApiToken.php
- /Repository/ApiTokenRepository.php
- /Service/ApiPlatformSdk.php
- /Service/Sdk/ (and files)

## Usage (specific SDK)

Example controller using Emonsite SDK (derivated class from `ApiPlatformSdk`, which can also be used as standalone SDK) :

```php
use App\Service\Sdk\Emonsite;

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

## Usage (standalone ApiPlatformSdk)

The SDK can also be used as standalone, without specific SDK loaded.
Example controller using ApiPlatformSdk as standalone :

```php
use App\Service\ApiPlatformSdk;

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
		
		// If applicable, set Login and Password for API Authorization
		$this->apiPlatformSdk->setLogin('me@example.com');
		$this->apiPlatformSdk->setPassword('mypassword');

		// Perform a request (for example : GET /products?page=3&provider=/providers/6)
		$this->apiPlatformSdk->setPage(3); // Add pagination (if applicable)
		$this->addParameter('provider' => '/providers/6'); // Add query string parameter ?provider=/providers/6
		$this->apiPlatformSdk->get('products'); // API Request
	}
}
```
