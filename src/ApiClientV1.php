<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/04 16:08
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class ApiClientV1
{
    private const VERSION = 'v1';
    private UriInterface $apiUri;

    public function __construct(private readonly ClientInterface $httpClient, string $baseUrl, private readonly string $keilaApiKey, string $apiPath = 'api')
    {
        $fullUrlPath = $baseUrl . '/' . $apiPath . '/' . self::VERSION . '/';
        $this->apiUri = Uri::fromParts(parse_url($fullUrlPath));
    }

    protected function getRequestClassMethod(): string
    {
        return KeilaRequest::class . '::new';
    }

    /**
     * Filters supported: https://github.com/pentacent/keila/blob/main/lib/keila/contacts/query.ex
     *   ### Supported operators:
     *    - `"$not"` - logical not.
     *    `%{"$not" => {%"email" => "foo@bar.com"}}`
     *    - `"$or"` - logical or.
     *    `%{"$or" => [%{"email" => "foo@bar.com"}, %{"inserted_in" => "2020-01-01 00:00:00Z"}]}`
     *    - `"$gt"` - greater-than operator.
     *    `%{"inserted_at" => %{"$gt" => "2020-01-01 00:00:00Z"}}`
     *    - `"$gte"` - greater-than-equal operator.
     *    - `"$lt"` - lesser-than operator.
     *    `%{"inserted_at" => %{"$lt" => "2020-01-01 00:00:00Z"}}`
     *    - `"$lte"` - lesser-than-or-equal operator.
     *    - `"$in"` - queries if field value is part of a set.
     *    `%{"email" => %{"$in" => ["foo@example.com", "bar@example.com"]}}`
     *    - `"$like"` - queries if the field matches using the SQL `LIKE` statement.
     *    `%{"email" => %{"$like" => "%keila.io"}}`
     *
     * @param array $filters
     * @param int $page
     * @param int $pageSize
     * @param array $order
     * @return ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function contactIndex(array $filters = [], int $page = 0, int $pageSize = 50, array $order = []): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/contacts')
            ->withFilters($filters)
            ->withPagination($page, $pageSize);

        return $this->httpClient->request('GET', $request->getUri(), $request->getOptions());
    }

    public function contactCreate(string $email, string $firstName, string $lastName, array $customData = []): ResponseInterface
    {
        $data = [
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ];
        if (!empty($customData)) {
            $data['data'] = $customData;
        }
        $request = $this->buildKeilaRequest()
            ->withPath('/contacts')
            ->withJsonData($data);

        return $this->httpClient->request('POST', $request->getUri(), $request->getOptions());
    }

    public function contactGetByEmail(string $email): ResponseInterface
    {
        return $this->contactIndex(['email' => $email]);
    }

    public function contactGetByEmails(array $emails): ResponseInterface
    {
        return $this->contactIndex(['email' => ['$in' => $emails]]);
    }

    public function contactGetById(string $contactId): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/contacts/' . $contactId);

        return $this->httpClient->request('GET', $request->getUri(), $request->getOptions());
    }

    public function contactDelete(string $contactId): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/contacts/' . $contactId);

        return $this->httpClient->request('DELETE', $request->getUri(), $request->getOptions());
    }

    /*
     * Returns true when contact was deleted or no contact with given email exists
     */
    public function contactDeleteByEmail(string $email): bool
    {
        $response = $this->contactGetByEmail($email);
        if ($response->getStatusCode() !== 200) {
            throw new \HttpRequestException('Failed fetching contact details for given email.');
        }

        $data = json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);

        if (!isset($data->data[0]->email)) {
            return true;
        }

        if ($email === $data->data[0]->email) {
            return $this->contactDelete($data->data[0]->id)->getStatusCode() === 204;
        }
        return true;
    }

    public function contactPatch(string $contactId, array $keyValueMap): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/contacts/' . $contactId)
            ->withJsonData($keyValueMap);

        return $this->httpClient->request('PATCH', $request->getUri(), $request->getOptions());
    }

    public function contactPut(string $contactId, array $keyValueMap): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/contacts/' . $contactId)
            ->withJsonData($keyValueMap);

        return $this->httpClient->request('PUT', $request->getUri(), $request->getOptions());
    }

    public function segmentIndex(): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/segments');

        return $this->httpClient->request('GET', $request->getUri(), $request->getOptions());
    }

    public function segmentCreate(string $name, array $filters): ResponseInterface
    {
        $data['name'] = $name;
        $data['filter'] = $filters;

        $request = $this->buildKeilaRequest()
            ->withPath('/segments')
            ->withJsonData($data);

        return $this->httpClient->request('POST', $request->getUri(), $request->getOptions());
    }

    public function segmentDelete(string $segmentId): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/segments/' . $segmentId);

        return $this->httpClient->request('DELETE', $request->getUri(), $request->getOptions());
    }

    public function segmentGetById(string $segmentId): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/segments/' . $segmentId);

        return $this->httpClient->request('GET', $request->getUri(), $request->getOptions());
    }

    public function segmentPatch(string $segmentId, array $data): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/segments/' . $segmentId)
            ->withJsonData($data);

        return $this->httpClient->request('PATCH', $request->getUri(), $request->getOptions());
    }

    public function segmentPut(string $segmentId, array $data): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/segments/' . $segmentId)
            ->withJsonData($data);

        return $this->httpClient->request('PUT', $request->getUri(), $request->getOptions());
    }

    public function campaignIndex(): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/campaigns');

        return $this->httpClient->request('GET', $request->getUri(), $request->getOptions());
    }

    public function campaignCreate(CampaignInterface $campaign): ResponseInterface
    {
        return $this->campaignCreateByArray($campaign->toArray());
    }

    public function campaignCreateByArray(array $data): ResponseInterface
    {

        if (!isset($data['subject'])) {
            throw new \InvalidArgumentException('Campaign name is required when creating a campaign.');
        }
        if (!isset($data['sender_id'])) {
            throw new \InvalidArgumentException('Campaign senderId is required set when creating a campaign.');
        }
        if (!isset($data['settings']['type']) || !in_array($data['settings']['type'], ['text', 'block', 'markdown'], true)) {
            throw new \InvalidArgumentException('Campaign editor setting is required when creating a campaign.');
        }

        $request = $this->buildKeilaRequest()
            ->withPath('/campaigns')
            ->withJsonData($data);

        return $this->httpClient->request('POST', $request->getUri(), $request->getOptions());
    }

    public function campaignDelete(string $campaignId): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/campaigns/' . $campaignId);

        return $this->httpClient->request('DELETE', $request->getUri(), $request->getOptions());
    }

    public function campaignGet(string $campaignId): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/campaigns/' . $campaignId);

        return $this->httpClient->request('GET', $request->getUri(), $request->getOptions());
    }

    public function campaignPatch(CampaignInterface $campaign, string $campaignId): ResponseInterface
    {
        return $this->campaignPatchByArray($campaign->toArray(), $campaignId);
    }

    public function campaignPatchByArray(array $campaignData, string $campaignId): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/campaigns/' . $campaignId)
            ->withJsonData($campaignData);

        return $this->httpClient->request('PATCH', $request->getUri(), $request->getOptions());
    }

    public function campaignPut(CampaignInterface $campaign, string $campaignId): responseInterface
    {
        return $this->campaignPutByArray($campaign->toArray(), $campaignId);
    }

    public function campaignPutByArray(array $campaignData, string $campaignId): responseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/campaigns/' . $campaignId)
            ->withJsonData($campaignData);

        return $this->httpClient->request('PUT', $request->getUri(), $request->getOptions());
    }

    public function campaignScheduleFor(string $campaignId, \DateTimeInterface $scheduleFor): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/campaigns/' . $campaignId . '/actions/schedule')
            ->withJsonData([
                'scheduled_for' => $scheduleFor->format('Y-m-d\TH:i:s.u\Z'),
            ]);

        return $this->httpClient->request('POST', $request->getUri(), $request->getOptions());
    }

    public function campaignSend(string $campaignId): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/campaigns/' . $campaignId . '/actions/send');

        return $this->httpClient->request('POST', $request->getUri(), $request->getOptions());
    }

    public function senderIndex(): ResponseInterface
    {
        $request = $this->buildKeilaRequest()
            ->withPath('/senders');

        return $this->httpClient->request('GET', $request->getUri(), $request->getOptions());
    }

    protected function buildKeilaRequest(UriInterface $apiUri = null): KeilaRequestInterface
    {
        return call_user_func($this->getRequestClassMethod(), $apiUri ?? $this->apiUri, $this->keilaApiKey);
    }
}
