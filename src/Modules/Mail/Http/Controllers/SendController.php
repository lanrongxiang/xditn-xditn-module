<?php

namespace Modules\Mail\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mail\Models\MailTemplate;
use Modules\Mail\Support\MailService;
use XditnModule\Base\XditnModuleController;

class SendController extends XditnModuleController
{
    public function to(Request $request, MailTemplate $template)
    {
        return MailService::provider($request->get('platform'))
            ->sendBatch(
                $request->get('recipients'),
                $request->get('subject'),
                $template->where('id', $request->get('template_id'))->value('content')
            );
    }
}
