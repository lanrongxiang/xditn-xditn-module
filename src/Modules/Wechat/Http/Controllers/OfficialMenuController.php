<?php

namespace Modules\Wechat\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Wechat\Support\Official\OfficialMenu;
use XditnModule\Base\CatchController;

class OfficialMenuController extends CatchController
{
    public function __construct(protected OfficialMenu $menu)
    {
    }

    public function index()
    {

    }

    public function store(Request $request)
    {
        return $this->menu->create($request->all());
    }
}
