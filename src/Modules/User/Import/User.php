<?php

namespace Modules\User\Import;

use Illuminate\Support\Collection;
use Modules\System\Support\Traits\AsyncTaskDispatch;
use Modules\User\Models\User as UserModel;
use XditnModule\Contracts\AsyncTaskInterface;
use XditnModule\Support\Excel\Import;

class User extends Import implements AsyncTaskInterface
{
    use AsyncTaskDispatch;

    public function collection(Collection $users)
    {
        $users->each(function ($user) {
            $userModel = new UserModel();
            $userModel->username = $user[1];
            $userModel->email = $user[2];
            $userModel->password = $user[3];
            $userModel->save();
        });
    }
}
