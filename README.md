# Symfony API Platform SDK

PHP SDK for Symfony API Platform, and derivated SDKs based on API Platform.

## Installation

Copy files and folders in corresponding folders of your Symfony project.

- `/src/Entity/ApiToken.php`
- `/src/Repository/ApiTokenRepository.php`
- `/src/Service/ApiPlatformSdk/` (and subclasses)
- `/config/packages/ramsey_uuid_doctrine.yaml`

**Important** : The ApiToken entities use UUID as unique ID. You need to add `ramsey/uuid` and `ramsey/uuid-doctrine` to your Symfony project :

- `composer require ramsey/uuid`
- `composer require ramsey/uuid-doctrine`

Don't forget to create and run **Doctrine migrations** to update your database with your new ApiToken entity.

- `php bin/console make:migration`
- `php bin/console doctrine:migrations:migrate`

Or, alternatively, you can simply update database schema :

- `php bin/console d:s:u -f`

## Usage

**As derivated SDK (for example Emonsite) :**

First, edit the constants in the derivated SDK class (here Service\Sdk\Emonsite : DEFAULT_API_URL, DEFAULT_API_FORMAT...).

Example controller using Emonsite SDK (derivated class from `ApiPlatformSdk`, which can also be used as standalone SDK) :

```php
use App\Service\ApiPlatformSdk\Emonsite;

class MyController
{
	public function index(Emonsite $emonsite)
	{
		// Define e-monsite site ID / Not required if ApiPlatformSdk is used as standalone
		$emonsite->setSiteId('536424be8e905c8c5cbbf781');
		
		// Load store orders
		$orders = $emonsite->getEcoOrders();
		
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
	public function index(ApiPlatformSdk $apiPlatformSdk)
	{
		// Set API URL
		$apiPlatformSdk->setApiUrl('https://api.example.com/');

		// Set API Format
		$apiPlatformSdk->setFormat('jsonld');
		
		// If your target API requires an authentication :
		// Set Login and Password for API Authorization, then request token
		$apiPlatformSdk->setHasAuthentication(true);
		$apiPlatformSdk->setLogin('me@example.com');
		$apiPlatformSdk->setPassword('mypassword');
		$apiPlatformSdk->authenticate();

		// Perform a GET request
		// for example : GET /products?page=3&provider=/providers/6&site_id=536424be8e905c8c5cbbf781
		$apiPlatformSdk->setPage(3); // Add pagination (if needed) : page 3
		$apiPlatformSdk->addParameter('provider' => '/providers/6'); // Add query string parameter : &provider=/providers/6
		$apiPlatformSdk->addParameter('site_id' => '536424be8e905c8c5cbbf781'); // Add query string parameter : &site_id=536424be8e905c8c5cbbf781
		
		// API Request : /products
		$products = $apiPlatformSdk->get('products');

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