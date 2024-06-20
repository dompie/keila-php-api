<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/14 13:10
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient\Tests;

use Dompie\KeilaApiClient\Campaign;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Campaign::class)]
class CampaignTest extends KeilaTestCase
{
    public function testOnlyUsedVariablesAreSet(): void
    {
        $c = new Campaign();
        self::assertSame([], $c->toArray());
        $c->withSenderId('senderId');
        self::assertArrayHasKey('sender_id', $c->toArray());
        self::assertSame('senderId', $c->toArray()['sender_id']);
        self::assertCount(1, $c->toArray());

        $c->withName('CampaignName');
        self::assertArrayHasKey('subject', $c->toArray());
        self::assertSame('senderId', $c->toArray()['sender_id']);
        self::assertSame('CampaignName', $c->toArray()['subject']);
        self::assertCount(2, $c->toArray());
    }

    public function testEditorType(): void
    {
        $editor = (new Campaign())->withBlockEditor(['Block content'])->toArray()['settings']['type'];
        self::assertSame('block', $editor);
        $editor = (new Campaign())->withTextEditor('Text content')->toArray()['settings']['type'];
        self::assertSame('text', $editor);
        $editor = (new Campaign())->withMarkdownEditor('Markdown content')->toArray()['settings']['type'];
        self::assertSame('markdown', $editor);
    }

    public function testCompleteState(): void
    {
        $c = (new Campaign())
            ->withSenderId('senderId')
            ->withName('CampaignName')
            ->withTextEditor('text body')
            ->withCustomData(['key' => 'value'])
            ->withoutTracking()
            ->withPreviewText('preview text')
            ->withSegmentId('segmentId')
            ->withTemplateId('templateId')
            ->withWysiwyg();

        $data = $c->toArray();
        self::assertSame('text', $data['settings']['type']);
        self::assertSame(json_encode(['key' => 'value'], JSON_THROW_ON_ERROR), $data['data']);
        self::assertSame(true, $data['settings']['do_not_track']);
        self::assertSame('preview text', $data['preview_text']);
        self::assertSame('segmentId', $data['segment_id']);
        self::assertSame('templateId', $data['template_id']);
        self::assertSame('text body', $data['text_body']);
        self::assertSame(true, $data['settings']['enable_wysiwyg']);

        $c->withoutWysiwyg()
            ->withTracking();
        $data = $c->toArray();
        self::assertSame(false, $data['settings']['enable_wysiwyg']);
        self::assertSame(false, $data['settings']['do_not_track']);
    }
}
