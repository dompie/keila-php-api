<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/12 11:26
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient\Tests;

use Dompie\KeilaApiClient\KeilaRequest;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(KeilaRequest::class)]
class KeilaRequestTest extends KeilaTestCase
{
    private KeilaRequest $request;
    private Uri $uri;
    private string $apiKey = 'test-api-key';

    public function setUp(): void
    {
        $this->uri = new Uri('https://example.com/api/v1/');
        $this->request = KeilaRequest::new($this->uri, $this->apiKey);
    }

    public function testInitialHeadersAreSet(): void
    {
        self::assertArrayHasKey('headers', $this->request->getOptions());
        self::assertArrayHasKey('Authorization', $this->request->getOptions()['headers']);
        self::assertStringEndsWith($this->apiKey, $this->request->getOptions()['headers']['Authorization']);
    }

    public function testUriPathIsSet(): void
    {
        self::assertSame('/api/v1/', $this->uri->getPath());
    }

    public function testFiltersExpressionShortUsage_in(): void
    {
        $this->request->withFilters(['email' => 'test@example.com']);
        self::assertArrayHasKey('query', $this->request->getOptions());
        self::assertArrayHasKey('filter', $this->request->getOptions()['query']);
        self::assertStringContainsString('$in', $this->request->getOptions()['query']['filter']);
        self::assertStringContainsString('email', $this->request->getOptions()['query']['filter']);
        self::assertStringContainsString('test@example.com', $this->request->getOptions()['query']['filter']);
    }

    public function testFiltersExpressionFullUsage_in(): void
    {
        $this->request->withFilters(['email' => ['$in' => ['test@example.com']]]);
        self::assertArrayHasKey('query', $this->request->getOptions());
        self::assertArrayHasKey('filter', $this->request->getOptions()['query']);
        self::assertStringContainsString('$in', $this->request->getOptions()['query']['filter']);
        self::assertStringContainsString('email', $this->request->getOptions()['query']['filter']);
        self::assertStringContainsString('test@example.com', $this->request->getOptions()['query']['filter']);
    }

    public function testFiltersWithUnsupportedExpressionThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->request->withFilters(['email' => ['$equals' => 'test@example.com']]);
    }

    public function testFiltersObjectCastingWithCastable(): void
    {
        $this->request->withFilters(['email' => ['$in' => new CastableToString()]]);
        self::assertStringContainsString('test-success@example.com', $this->request->getOptions()['query']['filter']);
    }

    public function testFiltersObjectCastingWithUncastable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->request->withFilters(['email' => ['$in' => new UncastableToString()]]);
    }

    public function testFiltersObjectCastingWithUnsupportedType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->request->withFilters(['email' => ['$in' => fopen(__FILE__, 'rb')]]);
    }

    public function testFiltersExpressionFullUsage_like(): void
    {
        $this->request->withFilters(['email' => ['$like' => '%@example.com']]);
        self::assertArrayHasKey('query', $this->request->getOptions());
        self::assertArrayHasKey('filter', $this->request->getOptions()['query']);
        self::assertStringContainsString('$like', $this->request->getOptions()['query']['filter']);
        self::assertStringContainsString('email', $this->request->getOptions()['query']['filter']);
        self::assertStringContainsString('%@example.com', $this->request->getOptions()['query']['filter']);
    }

    public function testExceptionOnInvalidExpression(): void
    {
        try {
            $this->request->withFilters(['email' => ['$expr' => '%@example.com']]);
            self::fail('Exception was expected.');
        } catch (\InvalidArgumentException $iae) {
            self::assertSame('Unsupported filter expression "$expr".', $iae->getMessage());
        }
    }

    public function testPagination(): void
    {
        $this->request->withPagination(5, 25);
        self::assertArrayHasKey('query', $this->request->getOptions());
        self::assertArrayHasKey('paginate', $this->request->getOptions()['query']);
        self::assertArrayHasKey('page', $this->request->getOptions()['query']['paginate']);
        self::assertArrayHasKey('page_size', $this->request->getOptions()['query']['paginate']);

        self::assertSame(5, $this->request->getOptions()['query']['paginate']['page']);
        self::assertSame(25, $this->request->getOptions()['query']['paginate']['page_size']);
    }

    public function testWithJsonDataEmptyNoJsonKey(): void
    {
        $this->request->withJsonData([]);
        self::assertArrayNotHasKey('json', $this->request->getOptions());
    }
}

class CastableToString
{
    public function __toString(): string
    {
        return 'test-success@example.com';
    }
}

class UncastableToString
{
}
