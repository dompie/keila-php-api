<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/14 10:07
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient;

class Campaign implements CampaignInterface
{
    /**
     * Currently known settings
     *  'data' => [],
     *  'segment_id' => '',
     *  'sender_id' => '',
     *  'settings' => [
     *    'type' => 'markdown',
     *    'do_not_track' => false,
     *    'enable_wysiwyg' => true,
     *  ],
     *  'subject' => '',
     *  'template_id' => '',
     *  'text_body' => '',
     * @var array
     */
    protected array $data = [];

    public function __construct()
    {
    }

    public function withName(string $name): self
    {
        $this->data['subject'] = $name;

        return $this;
    }

    public function withSenderId(string $senderId): self
    {
        $this->data['sender_id'] = $senderId;

        return $this;
    }


    public function withSegmentId(string $segmentId): self
    {
        $this->data['segment_id'] = $segmentId;

        return $this;
    }


    public function withTemplateId(string $templateId): self
    {
        $this->data['template_id'] = $templateId;

        return $this;
    }

    public function withTextBody(string $textBody): self
    {
        $this->data['text_body'] = $textBody;

        return $this;
    }

    public function withTextEditor(): self
    {
        $this->data['settings']['type'] = 'text';

        return $this;
    }

    public function withMarkdownEditor(): self
    {
        $this->data['settings']['type'] = 'markdown';

        return $this;
    }

    public function withBlockEditor(): self
    {
        $this->data['settings']['type'] = 'block';

        return $this;
    }

    public function withTracking(): self
    {
        $this->data['settings']['do_not_track'] = false;

        return $this;
    }

    public function withoutTracking(): self
    {
        $this->data['settings']['do_not_track'] = true;

        return $this;
    }

    public function withWysiwyg(): self
    {
        $this->data['settings']['enable_wysiwyg'] = true;

        return $this;
    }

    public function withoutWysiwyg(): self
    {
        $this->data['settings']['enable_wysiwyg'] = false;

        return $this;
    }

    public function withPreviewText(string $text): self
    {
        $this->data['preview_text'] = $text;

        return $this;
    }

    public function withCustomData(array $data): self
    {
        $this->data['data'] = json_encode($data, JSON_THROW_ON_ERROR);

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
