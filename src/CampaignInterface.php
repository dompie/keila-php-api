<?php
/*
 * Author: Dominik Piekarski <code@dompie.de>
 * Created at: 2024/03/14 10:33
 */
declare(strict_types=1);
namespace Dompie\KeilaApiClient;

interface CampaignInterface
{
    public function withTextEditor(string $textBody): self;

    public function toArray(): array;
}
