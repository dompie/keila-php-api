<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/11 09:24
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient;

use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;

class KeilaRequest implements KeilaRequestInterface
{
    private array $supportedExpressions = ['$not' => 1, '$or' => 1, '$gt' => 1, '$gte' => 1, '$lt' => 1, '$lte' => 1, '$in' => 1, '$like' => 1];

    private function __construct(private UriInterface $uri, protected array $options = [])
    {
    }

    public static function new(UriInterface $uri, string $apiKey): self
    {
        return (new self($uri))->withApiKey($apiKey);
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withApiKey(string $apiKey): self
    {
        $this->options[RequestOptions::HEADERS]['Authorization'] = 'Bearer ' . $apiKey;
        return $this;
    }

    public function withPagination(int $page = 0, int $pageSize = 50): self
    {
        $this->options[RequestOptions::QUERY]['paginate']['page'] = $page;
        $this->options[RequestOptions::QUERY]['paginate']['page_size'] = $pageSize;

        return $this;
    }

    /*
     * https://github.com/pentacent/keila/blob/06bcc951050e13e993b65310187bae7a6bde8dbb/lib/keila/contacts/query.ex#L11
     * - `"$not"` - logical not.
     *   `%{"$not" => {%"email" => "foo@bar.com"}}`
     * - `"$or"` - logical or.
     *   `%{"$or" => [%{"email" => "foo@bar.com"}, %{"inserted_in" => "2020-01-01 00:00:00Z"}]}`
     * - `"$gt"` - greater-than operator.
     *   `%{"inserted_at" => %{"$gt" => "2020-01-01 00:00:00Z"}}`
     * - `"$gte"` - greater-than-equal operator.
     * - `"$lt"` - lesser-than operator.
     *   `%{"inserted_at" => %{"$lt" => "2020-01-01 00:00:00Z"}}`
     * - `"$lte"` - lesser-than-or-equal operator.
     * - `"$in"` - queries if field value is part of a set.
     *   `%{"email" => %{"$in" => ["foo@example.com", "bar@example.com"]}}`
     * - `"$like"` - queries if the field matches using the SQL `LIKE` statement.
     *   `%{"email" => %{"$like" => "%keila.io"}}`
     *
     *  Usage:
     *   $req->withFilters(['email' => ['$like' => '%@example.com']]);
     *   $req->withFilters(['email' => ['test@example.com', 'test2@example.com]]); //defaults to $in
     *   $req->withFilters(['email' => 'test@example.com'); //defaults to $in
     */
    public function withFilters(array $filters): self
    {
        if (empty($filters)) {
            return $this;
        }
        foreach ($filters as $fieldName => $filterParts) {
            if (is_scalar($filterParts)) {
                $this->withFilter($fieldName, $filterParts);
                continue;
            }
            if (!is_array($filterParts)) {
                $this->withFilter($fieldName, (string)$filterParts);
                continue;
            }
            $expression = key($filterParts);
            $this->withFilter($fieldName, $filterParts[$expression], $expression);
        }

        return $this;
    }

    public function withFilter(string $fieldName, mixed $value, string $filterExpression = '$in'): self
    {
        if (is_object($value)) {
            $objClass = get_class($value);
            try {
                $value = (string)$value;
            } catch (\Throwable $t) {
                throw new \InvalidArgumentException(sprintf('Casting object of type "%s" to string failed.', $objClass));
            }
        }

        if (!is_scalar($value) && !is_array($value)) {
            throw new \InvalidArgumentException('Can only accept scalar data types or array as value.');
        }
        if (!isset($this->supportedExpressions[$filterExpression])) {
            throw new \InvalidArgumentException(sprintf('Unsupported filter expression "%s".', substr($filterExpression, 0, 5)));
        }
        if ($filterExpression === '$in' && !is_array($value)) {
            $value = [$value];
        }
        $this->options[RequestOptions::QUERY]['filter'][$fieldName] = [$filterExpression => $value];

        return $this;
    }


    public function withPath(string $path): self
    {
        $this->uri = $this->uri->withPath(rtrim($this->uri->getPath(), '/') . '/' . ltrim($path, '/'));
        return $this;
    }

    public function withJsonData(array $data): self
    {
        if ($data === []) {
            return $this;
        }
        $this->options[RequestOptions::JSON]['data'] = $data;
        return $this;
    }

    public function getOptions(): array
    {
        $options = $this->options;
        if (!empty($options[RequestOptions::QUERY]['filter'])) {
            $options[RequestOptions::QUERY]['filter'] = json_encode($options[RequestOptions::QUERY]['filter'], JSON_THROW_ON_ERROR);
        }

        return $options;
    }
}
