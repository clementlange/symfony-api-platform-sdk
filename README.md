# Symfony API Platform SDK

PHP SDK for Symfony API Platform, and derivated SDKs based on API Platform.

## Installation

Copy files and folders in corresponding folders of your Symfony project.

- /Entity/ApiToken.php
- /Repository/ApiTokenRepository.php
- /Service/ApiPlatformSdk.php
- /Service/Sdk/ (and files)

## Usage

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