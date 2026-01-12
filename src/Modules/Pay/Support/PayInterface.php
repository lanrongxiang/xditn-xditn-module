<?php

namespace Modules\Pay\Support;

interface PayInterface
{
    public function refund(array $params): mixed;

    public function notify(): mixed;
}
