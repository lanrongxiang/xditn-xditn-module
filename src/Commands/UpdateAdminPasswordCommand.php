<?php

namespace XditnModule\Commands;

use Illuminate\Console\Command;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class UpdateAdminPasswordCommand extends Command
{
    protected $signature = 'xditn:module:update:password';

    protected $description = '更新超级管理员密码';

    public function handle(): void
    {
        $userModel = config('xditn.auth_model');

        if (!$userModel) {
            $this->error('请先设置后台用户模型');
        } else {
            $userModel = app($userModel);

            $superAdminId = config('xditn.super_admin', 1);

            if (!is_array($superAdminId)) {
                $superAdminId = [$superAdminId];
            }

            $users = [];

            $userModel->whereIn('id', $superAdminId)->get(['id', 'username'])
                ->each(function ($user) use (&$users) {
                    $users[$user->id] = $user->username;
                });

            if (count($users) > 1) {
                $userId = select('选择更新的用户', $users);
            } else {
                $userId = $superAdminId[0];
            }

            $password = text('新密码', placeholder: '请输入新密码',
                required: true,
                validate: fn (string $value) => match (true) {
                    strlen($value) < 8 => '新密码至少八位数字.',
                    default => null
                }
            );

            $user = $userModel->find($userId);
            $user->password = $password;
            $user->save();

            $this->info('🎉 密码更新成功 !!!');
            $this->info("账户: {$user->email}");
            $this->info("密码: {$password}");
        }
    }
}
