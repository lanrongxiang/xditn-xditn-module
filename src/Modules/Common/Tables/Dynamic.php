<?php

namespace Modules\Common\Tables;

abstract class Dynamic
{
    public function __invoke($key = null)
    {
        if ($key) {
            return [
                $key => $this->{$key}(),
            ];
        }

        return [
            'form' => $this->form(),
            'table' => $this->table(),
        ];
    }    abstract protected function form();

    abstract protected function table();
}
