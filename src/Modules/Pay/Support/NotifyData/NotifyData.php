<?php

namespace Modules\Pay\Support\NotifyData;

abstract class NotifyData implements NotifyDataInterface
{
    public function __construct(
        protected array $data
    ) {
    }    /**
     * @param string $key
     *
     * @return array|mixed
     */
    public function get(string $key = ''): mixed
    {
        if ($key) {
            return $this->data;
        }

        return data_get($this->data, $key);
    }

    /**
     * @param bool $isPretty
     *
     * @return bool|string
     */
    public function json(bool $isPretty = true): bool|string
    {
        if ($isPretty) {
            return json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        return json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }
}
