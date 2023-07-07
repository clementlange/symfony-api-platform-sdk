# Symfony API Platform SDK

PHP SDK for Symfony API Platform, and derivated SDKs based on API Platform.

Currently supported derivated SDKs are :

- E-monsite ([www.e-monsite.com](https://www.e-monsite.com))
- E-confiance ([www.e-monsite.com](https://www.e-confiance.fr))
- EMS-Stock ([www.e-monsite.com](https://www.ems-stock.com))

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

### Option 1 : As derivated SDK (for example Emonsite) :

First, edit the constants in the derivated SDK class (here Service\Sdk\Emonsite : DEFAULT_API_URL, DEFAULT_API_FORMAT...).

Example controller using Emonsite SDK (derivated class from `ApiPlatformSdk`, which can also be used as standalone SDK) :

```php
use App\Service\ApiPlatformSdk\Emonsite;

class MyController
{
	public function index(Emonsite $emonsite)
	{
		// Load store orders
		$orders = $emonsite->getEcoOrders();
		
		// Dumping var
		dump($orders);
	}
}
```

You can find many other methods in `Emonsite`, for instance getBlogPosts(), createStorageImage(), etc. Look up the class to see all available methods.

Each derivated SDK (E-confiance, Agenda Culturel...) has its own specific methods.

### Option 2 : As standalone ApiPlatformSdk :

The SDK can also be used as standalone, to work with any API using API Platform, but without using any specific derivated SDK.

You will have to explicitly declare API URL, format, and credentials / request for authorization token (if applicable).

Example controller using `ApiPlatformSdk` as standalone :

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


Other available verbs include POST, PATCH, PUT and DELETE :

```php
// HTTP POST
$apiPlatformSdk->post('/uri', array $postdata, ?array $headers);

// HTTP PATCH
$apiPlatformSdk->patch('/uri', array $postdata);

// HTTP PUT
$apiPlatformSdk->put('/uri', array $postdata);

// HTTP DELETE
$apiPlatformSdk->delete('/uri', string $id);
```