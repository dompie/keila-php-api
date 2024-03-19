<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/11 09:54
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient;

use Psr\Http\Message\UriInterface;

interface KeilaRequestInterface
{
    public static function new(UriInterface $uri, string $apiKey): KeilaRequestInterface;

    public function getUri(): UriInterface;

    public function withApiKey(string $apiKey): KeilaRequestInterface;

    public function withPagination(int $page = 0, int $pageSize = 50): KeilaRequestInterface;

    public function withFilters(array $filters): self;

    public function withFilter(string $fieldName, $value, string $filterExpression = '$like'): KeilaRequestInterface;

    public function withJsonData(array $data): KeilaRequestInterface;

    public function withPath(string $path): KeilaRequestInterface;

    public function getOptions(): array;
}
