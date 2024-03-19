A PHP HTTP client for the Keila newsletter API with Guzzle as single dependency.

Install:
```
composer require dompie/keila-http-api
```

How to use:
```
require_once 'vendor/autoload.php';

$httpClient = new \GuzzleHttp\Client();
$client = new \Dompie\KeilaApiClient\ApiClientV1($httpClientInterface, 'https://keila-installation.url', 'my-secret-keila-api-key');
$responseInterface = $client->createContact([
    'first_name' => 'First',
    'last_name' => 'Last',
    'email' => 'first.last@example.com',
]);

$campaign = new \Dompie\KeilaApiClient\Campaign();
$campaign->withName('My test campaign')
    ->withTextBody('Hello world!')
    ->withMarkdownEditor();
    
$responseInterface = $client->createCampaign($campaign);
```
Then you can pass the Guzzle ResponseInterface to your context.


For testing purposes I've added also a KeilaResponse class to quickly access relevant items.
```
$response = \Dompie\KeilaApiClient\KeilaResponse::new($responseInterface);
$response->hasData();
$response->getDataItem(0); //First element from the data property
$response->getGuzzleResponse()->getStatusCode()
```

