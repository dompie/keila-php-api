<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/15 08:25
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient\Tests;

use Dompie\KeilaApiClient\KeilaResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

#[CoversClass(KeilaResponse::class)]
class KeilaResponseTest extends KeilaTestCase
{
    public function testExtendingKeilaResponse(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);
        self::assertStringEndsWith('KeilaResponseExtended', get_class(KeilaResponseExtended::new($responseMock)));
    }

    public function testResponseUsage(): void
    {
        $responseStruct = [
            'data' => [
                [
                    'email' => 'first@example.com',
                    'first_name' => 'test1',
                    'last_name' => 'test1',
                ],
                [
                    'email' => 'second@example.com',
                    'first_name' => 'test2',
                    'last_name' => 'test2',
                ],
            ],
            'meta' => [
                'page' => 0,
                'page_count' => 1,
                'page_size' => 2,]
        ];

        $responseMock = $this->createMock(ResponseInterface::class);
        $streamMock = $this->getMockBuilder(StreamInterface::class)->getMock();
        $streamMock->method('getContents')->willReturn(json_encode($responseStruct, JSON_THROW_ON_ERROR));
        $responseMock->method('getBody')->willReturn($streamMock);

        $response = KeilaResponse::new($responseMock);
        self::assertTrue($response->hasData());
        self::assertSame(2, $response->getDataItemCount());
        self::assertCount(2, $response->getDataItems());
        self::assertSame('second@example.com', $response->getDataItem(1)['email']);
        self::assertSame('first@example.com', $response->getDataItem(0)['email']);
        self::assertArrayHasKey('page', $response->getMeta());
        self::assertArrayHasKey('page_count', $response->getMeta());
        self::assertArrayHasKey('page_size', $response->getMeta());
        self::assertSame(0, $response->getMetaPage());
        self::assertSame(1, $response->getMetaPageCount());
        self::assertSame(2, $response->getMetaPageSize());
        self::assertIsObject($response->getGuzzleResponse());
    }
}

class KeilaResponseExtended extends KeilaResponse
{

}
