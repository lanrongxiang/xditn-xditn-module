<?php

namespace Modules\Wechat\Http\Controllers;

use Modules\Wechat\Support\Official\OfficialAccount;
use XditnModule\Base\XditnModuleController;

class OfficialAccountController extends XditnModuleController
{
    public function sign(OfficialAccount $officialAccount)
    {
        return $officialAccount->serve();
    }
}
