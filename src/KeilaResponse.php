<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/14 13:38
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient;

use Psr\Http\Message\ResponseInterface;

class KeilaResponse
{
    protected array $responseData = [];

    private function __construct(protected ResponseInterface $response)
    {
    }

    public static function new(ResponseInterface $response)
    {
        return new static($response);
    }

    public function getGuzzleResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function hasData(): bool
    {
        $this->processResponse();
        return isset($this->responseData['data']) || isset($this->responseData['meta']);
    }

    public function getDataItemCount(): int
    {
        $this->processResponse();
        return is_array($this->responseData['data']) ? count($this->responseData['data']) : 0;
    }

    public function getDataItems(): ?array
    {
        $this->processResponse();
        return $this->responseData['data'];
    }

    public function hasDataItem(int|string $index): bool
    {
        return isset($this->responseData['data'][$index]);
    }

    public function getDataItem(int|string $index): mixed
    {
        $this->processResponse();
        return $this->responseData['data'][$index] ?? null;
    }

    public function getMeta(): ?array
    {
        $this->processResponse();
        return $this->responseData['meta'] ?? null;
    }

    public function getMetaPage(): int
    {
        $this->processResponse();
        return $this->responseData['meta']['page'] ?? 0;
    }


    public function getMetaPageCount(): int
    {
        $this->processResponse();
        return $this->responseData['meta']['page_count'] ?? 0;
    }

    public function getMetaPageSize(): int
    {
        $this->processResponse();
        return $this->responseData['meta']['page_size'] ?? 0;
    }

    /**
     * @throws \JsonException
     */
    protected function processResponse(): void
    {
        if ($this->responseData === []) {
            $this->responseData = json_decode($this->response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR) ?? [];
        }
    }
}
