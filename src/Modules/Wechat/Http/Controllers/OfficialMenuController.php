<?php

namespace Modules\Wechat\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Wechat\Support\Official\OfficialMenu;
use XditnModule\Base\XditnModuleController;

class OfficialMenuController extends XditnModuleController
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
