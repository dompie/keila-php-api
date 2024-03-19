<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/11 14:38
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class KeilaTestCase extends TestCase
{
    final public static function assertKeilaResponseSuccessfull(ResponseInterface $response): void
    {
        self::assertSame(200, $response->getStatusCode());
        self::assertGreaterThan(0, $response->getBody()->getSize());
    }

    final public static function assertKeilaDeleteSuccessfull(ResponseInterface $response): void
    {
        self::assertSame(204, $response->getStatusCode());
    }

    final public static function assertKeilaMetaCountGreaterThanOrEqual(int $expected, ResponseInterface $response): void
    {
        $obj = json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        self::assertGreaterThanOrEqual($expected, $obj->meta->count);
    }
}
