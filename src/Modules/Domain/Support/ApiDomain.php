<?php

namespace Modules\Domain\Support;

use Modules\Domain\Support\Request\AliyunRequest;
use Modules\Domain\Support\Request\QCloudRequest;
use Modules\Domain\Support\Request\Request;

class ApiDomain
{
    /**
     * @param string $type
     *
     * @return Request
     */
    public static function getRequest(string $type): Request
    {
        if ($type === 'aliyun') {
            return new AliyunRequest();
        } else {
            return new QCloudRequest();
        }

    }
}
