<?php

namespace Modules\System\Support\Webhook;

interface WebhookInterface
{
    public function sign(): string;

    public function send(string $msgType, string $content): bool;
}
