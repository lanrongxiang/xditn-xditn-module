<?php

namespace Modules\Mail\Http\Controllers;

use Illuminate\Http\Request;
use Modules\System\Models\SystemConfig;
use Modules\System\Support\Configure;
use XditnModule\Base\CatchController;

class SettingController extends CatchController
{
    public function index()
    {
        return array_values(config('mails', []));

    }

    public function store(Request $request, SystemConfig $config)
    {
        $data = [];
        foreach ($request->all() as $value) {
            $data[$value['platform']] = $value;
        }

        return $config->storeBy(Configure::parse('mails', $data));
    }
}
