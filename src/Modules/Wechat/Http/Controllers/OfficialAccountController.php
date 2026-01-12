<?php

namespace Modules\Wechat\Http\Controllers;

use Modules\Wechat\Support\Official\OfficialAccount;
use XditnModule\Base\CatchController;

class OfficialAccountController extends CatchController
{
    public function sign(OfficialAccount $officialAccount)
    {
        return $officialAccount->serve();
    }
}
