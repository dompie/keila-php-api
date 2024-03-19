<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/04 20:25
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient\Tests;

use Dompie\KeilaApiClient\ApiClientV1;
use Dompie\KeilaApiClient\Campaign;
use Dompie\KeilaApiClient\KeilaRequest;
use Dompie\KeilaApiClient\KeilaResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\UsesClass;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(ApiClientV1::class)]
#[CoversClass(KeilaRequest::class)]
#[UsesClass(Campaign::class)]
#[UsesClass(KeilaResponse::class)]
class ApiClientV1Test extends KeilaTestCase
{
    private ApiClientV1 $client;

    private string $testTld1 = 'example.com';
    private string $testTld2 = 'test.com'; //must be different from testTld1
    private string $email1;
    private string $email2;

    private static string $baseUri = '';
    private static string $apiKey;

    public function setUp(): void
    {
        if (!empty($_ENV['KEILA_BASE_URI'])) {
            self::$baseUri = $_ENV['KEILA_BASE_URI'];
        }
        if (!empty($_ENV['KEILA_API_KEY'])) {
            self::$apiKey = $_ENV['KEILA_API_KEY'];
        }
        $httpClient = new Client();
        $this->client = new ApiClientV1($httpClient, self::$baseUri, self::$apiKey);
        $this->email1 = 'first.example@' . $this->testTld1;
        $this->email2 = 'first.test@' . $this->testTld2;
    }

    public function testContactCreate(): string
    {
        $this->deleteContacts();

        $response = $this->client->contactCreate($this->email1, 'FirstExample', 'LastExample', ['TOKEN' => 123]);
        self::assertKeilaResponseSuccessfull($response);
        $contactId = $this->response2Object($response)->data->id;
        self::assertNotEmpty($contactId);

        return $contactId;
    }

    #[Depends('testContactCreate')]
    public function testContactDelete(string $contactId): void
    {
        self::assertKeilaDeleteSuccessfull($this->client->contactDelete($contactId));
    }

    public function testContactsIndex(): void
    {
        $this->deleteContacts();
        $currentCount = $this->response2Object($this->client->contactIndex())->meta->count;

        $this->client->contactCreate($this->email1, 'FirstExample', 'LastExample', ['TOKEN' => 123]);
        $this->client->contactCreate($this->email2, 'FirstTest', 'LastTest', ['TOKEN' => 123]);

        $response = $this->client->contactIndex();
        self::assertKeilaResponseSuccessfull($response);
        self::assertSame(($currentCount + 2), $this->response2Object($response)->meta->count);
    }

    public function testContactGetById(): void
    {
        $this->deleteContacts();

        $response = $this->client->contactCreate($this->email1, 'FirstExample', 'LastExample', ['TOKEN' => 123]);
        $contactId = $this->response2Object($response)->data->id;
        $response = $this->client->contactGetById($contactId);
        self::assertKeilaResponseSuccessfull($response);
        $responseObj = $this->response2Object($response);
        self::assertSame($this->email1, $responseObj->data->email);
    }

    public function testContactsIndexFilter(): void
    {
        $this->deleteContacts();
        //Create initial address pool of at least 2 emails
        $response1 = $this->client->contactCreate($this->email1, 'FirstExample', 'LastExample', ['TOKEN' => 123]);
        $response2 = $this->client->contactCreate($this->email2, 'FirstTest', 'LastTest', ['TOKEN' => 123]);
        self::assertKeilaResponseSuccessfull($response1);
        self::assertKeilaResponseSuccessfull($response2);

        $responseAllObj = $this->response2Object($this->client->contactIndex());
        $allCount = count($responseAllObj->data);

        $responseObj1 = $this->response2Object($this->client->contactIndex(['email' => ['$like' => ('%' . $this->testTld1)]]));
        $tld1Count = count($responseObj1->data);
        self::assertGreaterThanOrEqual(1, $tld1Count);
        self::assertLessThan($allCount, $tld1Count);

        $responseObj2 = $this->response2Object($this->client->contactIndex(['email' => ['$like' => ('%' . $this->testTld2)]]));
        $tld2Count = count($responseObj2->data);
        self::assertGreaterThanOrEqual(1, $tld2Count);
        self::assertLessThan($allCount, $tld2Count);
    }

    public function testContactsPaginate(): void
    {
        $this->deleteContacts();

        $this->client->contactCreate($this->email1, 'FirstExample', 'LastExample', ['TOKEN' => 123]);
        $this->client->contactCreate($this->email2, 'FirstTest', 'LastTest', ['TOKEN' => 123]);

        $responseObject1 = $this->response2Object($this->client->contactIndex([], 0, 1));
        self::assertSame(0, $responseObject1->meta->page);
        $responseObject2 = $this->response2Object($this->client->contactIndex([], 1, 2));
        self::assertSame(1, $responseObject2->meta->page);
        self::assertSame((int)ceil($responseObject1->meta->page_count / 2), $responseObject2->meta->page_count);
    }

    public function testContactsGetByEmails(): void
    {
        $this->deleteContacts();

        $this->client->contactCreate($this->email1, 'FirstExample', 'LastExample', ['TOKEN' => 123]);
        $this->client->contactCreate($this->email2, 'FirstTest', 'LastTest', ['TOKEN' => 123]);

        $response = $this->response2Object($this->client->contactGetByEmails([$this->email1, $this->email2]));
        self::assertCount(2, $response->data);
    }

    public function testContactPatch(): void
    {
        $this->deleteContacts();

        $contactId1 = $this->response2Object($this->client->contactCreate($this->email1, 'FirstExample', 'LastExample', ['TOKEN' => 123]))->data->id;
        $response = $this->response2Object($this->client->contactPatch($contactId1, ['first_name' => 'SecondExample']));
        self::assertSame('SecondExample', $response->data->first_name);
    }

    public function testContactPut(): void
    {
        $this->deleteContacts();

        $contactId1 = $this->response2Object($this->client->contactCreate($this->email1, 'FirstExample', 'LastExample', ['TOKEN' => 123]))->data->id;
        $response = $this->response2Object($this->client->contactPut($contactId1, ['first_name' => 'SecondExample']));
        self::assertSame('SecondExample', $response->data->first_name);
    }

    public function testSegmentFunctionality(): void
    {
        //Remove any possible leftovers from previously aborted tests.
        $segmentId = null;
        $segments = $this->response2Object($this->client->segmentIndex());
        foreach ($segments->data as $segment) {
            if (in_array($segment->name, ['TestSegmentName1', 'TestSegmentName2', 'TestSegmentName3'], true)) {
                $segmentId = $segment->id;
                break;
            }
        }
        if (null !== $segmentId) {
            self::assertKeilaDeleteSuccessfull($this->client->segmentDelete($segmentId));
        }
        $initialName = 'TestSegmentName1';
        $initialCount = $this->response2Object($this->client->segmentIndex())->meta->count;
        //Test create
        $segmentId = $this->response2Object($this->client->segmentCreate($initialName, ['email' => ['$in' => $this->email1]]))->data->id;
        $segments = $this->response2Object($this->client->segmentIndex());
        self::assertSame(($initialCount + 1), $segments->meta->count);

        //Test get
        $response = $this->client->segmentGetById($segmentId);
        self::assertKeilaResponseSuccessfull($response);
        self::assertSame($initialName, $this->response2Object($response)->data->name);

        //Test patch
        $response = $this->client->segmentPatch($segmentId, ['name' => 'TestSegmentName2']);
        self::assertKeilaResponseSuccessfull($response);
        self::assertSame('TestSegmentName2', $this->response2Object($response)->data->name);

        //Test put
        $response = $this->client->segmentPut($segmentId, ['name' => 'TestSegmentName3']);
        self::assertKeilaResponseSuccessfull($response);
        self::assertSame('TestSegmentName3', $this->response2Object($response)->data->name);

        //Test get
        $response = $this->client->segmentGetById($segmentId);
        self::assertKeilaResponseSuccessfull($response);
        self::assertSame('TestSegmentName3', $this->response2Object($response)->data->name);

        //Test delete
        self::assertKeilaDeleteSuccessfull($this->client->segmentDelete($segmentId));
    }

    public function testCampaignIndex(): void
    {
        $response = $this->client->campaignIndex();
        self::assertKeilaResponseSuccessfull($response);
        self::assertGreaterThanOrEqual(0, $this->response2Object($response)->meta->page_count);
    }

    #[Depends('testCampaignIndex')]
    public function testCampaignCreate(): string
    {
        $senderIndexResponse = $this->response2Object($this->client->senderIndex());
        self::assertGreaterThanOrEqual(1, count($senderIndexResponse->data));
        $campaignIndexCount1 = count($this->response2Object($this->client->campaignIndex())->data);

        $c = (new Campaign())
            ->withName('Test api campaign ' . uniqid('', false))
            ->withSenderId($senderIndexResponse->data[0]->id)
            ->withTextEditor();

        $response = $this->client->campaignCreate($c);
        self::assertKeilaResponseSuccessfull($response);
        $campaignIndexCount2 = count($this->response2Object($this->client->campaignIndex())->data);
        $campaignId = $this->response2Object($response)->data->id;
        self::assertGreaterThan($campaignIndexCount1, $campaignIndexCount2);

        return $campaignId;
    }

    #[Depends('testCampaignCreate')]
    public function testCampaignGet(string $campaignId): string
    {
        $response = KeilaResponse::new($this->client->campaignGet($campaignId));
        self::assertTrue($response->hasData());
        self::assertSame($campaignId, $response->getDataItem('id'));

        return $campaignId;
    }

    #[Depends('testCampaignGet')]
    public function testCampaignDelete(string $campaignId): void
    {
        $campaignIndexCount1 = KeilaResponse::new($this->client->campaignIndex())->getDataItemCount();
        self::assertKeilaDeleteSuccessfull($this->client->campaignDelete($campaignId));
        $campaignIndexCount2 = KeilaResponse::new($this->client->campaignIndex())->getDataItemCount();
        self::assertLessThan($campaignIndexCount1, $campaignIndexCount2);
    }

    public function testCampaignUpdate(): void
    {
        $campaignName = 'Test api campaign ' . uniqid('', false);
        $senderIndexResponse = KeilaResponse::new($this->client->senderIndex());
        $c = (new Campaign())
            ->withName($campaignName)
            ->withSenderId($senderIndexResponse->getDataItem(0)['id'])
            ->withTextEditor();

        $campaign = $this->response2Object($this->client->campaignCreate($c));
        self::assertSame($campaignName, $campaign->data->subject);
        $newCampaignName = 'Test api campaign ' . sprintf('%04d', random_int(1, 9999));
        $c = (new Campaign())->withName($newCampaignName)->withMarkdownEditor();
        $updatedCampaign = $this->response2Object($this->client->campaignPatch($c, $campaign->data->id));
        self::assertSame($newCampaignName, $updatedCampaign->data->subject, sprintf('Campaign name should be "%s" but is "%s"', $newCampaignName, $updatedCampaign->subject));

        $newCampaignName = 'Test api campaign ' . '(PUT test)';
        $c = (new Campaign())->withName($newCampaignName)->withTextEditor();
        $updatedCampaign = $this->response2Object($this->client->campaignPut($c, $campaign->data->id));
        self::assertSame($newCampaignName, $updatedCampaign->data->subject, sprintf('Campaign name should be "%s" but is "%s"', $newCampaignName, $updatedCampaign->subject));
    }

    public function testSenderIndex(): void
    {
        $response = $this->client->senderIndex();
        self::assertKeilaResponseSuccessfull($response);
        $response = KeilaResponse::new($response);
        self::assertGreaterThanOrEqual(1, $response->getDataItemCount());
        self::assertArrayHasKey('id', $response->getDataItem(0));
        self::assertArrayHasKey('name', $response->getDataItem(0));
        self::assertArrayHasKey('from_email', $response->getDataItem(0));
    }

    public function testCampaignScheduleFor(): void
    {
        $this->deleteContacts();
        $this->client->contactCreate($this->email1, 'First', 'Last');
        $segment = $this->response2Object($this->client->segmentCreate('Campaign-Schedule-Test .' . date('His'), ['$in' => [$this->email1]]));

        $name = 'Send campaign test ' . date('dmY-His');
        $senderIndexResponse = KeilaResponse::new($this->client->senderIndex());
        $c = (new Campaign())
            ->withTextBody('Hello world!')
            ->withName($name)
            ->withSenderId($senderIndexResponse->getDataItem(0)['id'])
            ->withSegmentId($segment->data->id)
            ->withTextEditor();
        $response = $this->response2Object($this->client->campaignCreate($c));
        self::assertNull($response->data->scheduled_for);
        self::assertNull($response->data->sent_at);

        try {
            $time = time();
            $response = $this->client->campaignScheduleFor($response->data->id, \DateTime::createFromFormat('U', (string)$time));
            self::fail('Expected 400 Bad request response with text: "must be at least 300 seconds in the future"');
        } catch (ClientException $ce) {
            self::assertStringContainsString('must be at least 300 seconds in the future', $ce->getMessage());
        }

        $in10Minutes = (string)($time + 600);
        $response = $this->response2Object($this->client->campaignScheduleFor($response->data->id, \DateTime::createFromFormat('U', $in10Minutes)));
        self::assertStringContainsString(date('Y-m-d'), $response->data->scheduled_for);
        self::assertNull($response->data->sent_at);
    }

    public function testCampaignSend(): void
    {
        $this->deleteContacts();
        $this->client->contactCreate($this->email1, 'First', 'Last');
        $segment = $this->response2Object($this->client->segmentCreate('Campaign-Send-Test .' . date('His'), ['$in' => [$this->email1]]));

        $name = 'Send campaign test ' . date('dmY-His');
        $senderIndexResponse = KeilaResponse::new($this->client->senderIndex());
        $c = (new Campaign())
            ->withTextBody('Hello world!')
            ->withName($name)
            ->withSenderId($senderIndexResponse->getDataItem(0)['id'])
            ->withSegmentId($segment->data->id)
            ->withTextEditor();
        $response = $this->response2Object($this->client->campaignCreate($c));
        self::assertNull($response->data->sent_at);

        $response = $this->response2Object($this->client->campaignSend($response->data->id));
        self::assertStringContainsString(date('d-m-Y'), $response->data->sent_at);
    }

    private function response2Object(ResponseInterface $response): \stdClass
    {
        return json_decode($response->getBody()->getContents(), false, 8, JSON_THROW_ON_ERROR);
    }

    private function deleteContacts(): void
    {
        $this->client->contactDeleteByEmail($this->email1);
        $this->client->contactDeleteByEmail($this->email2);
    }
}
