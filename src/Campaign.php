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
    protected ?string $id = null;

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

    public function withTextEditor(string $textBody): self
    {
        $this->data['settings']['type'] = 'text';
        $this->wipeContentBody();
        $this->data['text_body'] = $textBody;

        return $this;
    }

    public function withMarkdownEditor(string $markdownBody): self
    {
        $this->data['settings']['type'] = 'markdown';
        $this->wipeContentBody();
        $this->data['text_body'] = $markdownBody;

        return $this;
    }

    /**
     * @throws \JsonException
     */
    public function withBlockEditor(string|array $jsonBody): self
    {
        $this->data['settings']['type'] = 'block';
        $this->wipeContentBody();
        $this->data['json_body'] = is_array($jsonBody) ? $jsonBody : json_decode($jsonBody, true, 512, JSON_THROW_ON_ERROR);


        return $this;
    }

    private function wipeContentBody(): self
    {
        unset($this->data['json_body'], $this->data['text_body'], $this->data['html_body']);

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
        $this->data['data'] = $data;

        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function setId(string $campaignId): self
    {
        $this->id = $campaignId;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }
}
