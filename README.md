# Keila PHP API

A PHP HTTP client for the Keila newsletter API with Guzzle as single dependency.

### Install
```bash
composer require dompie/keila-http-api
```

### How to use
```php
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
    ->withTextEditor('Hello world!')
    ->withMarkdownEditor();
    
$responseInterface = $client->createCampaign($campaign);
```
Then you can pass the Guzzle ResponseInterface to your context.

### Response helper
For testing purposes I've added also a KeilaResponse class to quickly access relevant items.
```php
$response = \Dompie\KeilaApiClient\KeilaResponse::new($responseInterface);
$response->hasData();
$response->getDataItem(0); //First element from the data property
$response->getGuzzleResponse()->getStatusCode()
```


### &#9888;&#65039; Tests will trigger E-Mail sending. Use only with keila dev instance.
```bash
cp phpunit.xml.dist phpunit.xml
```

Put your Keila URI and Keila api key into phpunit.xml:
```xml
<env name="KEILA_BASE_URI" value="http://keila.local:4000" force="true"/>
<env name="KEILA_API_KEY" value="secret-api-key" force="true"/>
```

#### Finally tests
```bash
make test
or
XDEBUG_MODE=coverage make test-coverage
```
