<?php

namespace Modules\Mail\Console;

use Illuminate\Console\Command;
use Modules\Mail\Support\SendMailTaskService;

class SendMailTaskCommand extends Command
{
    protected $signature = 'mail:send-tasks {--limit=10 : 每次处理的任务数量}';

    protected $description = '处理待发送的邮件任务';

    public function handle(): int
    {
        $sendMailTaskService = app(SendMailTaskService::class);

        $limit = (int) $this->option('limit');

        $this->info("开始处理邮件发送任务，限制数量: {$limit}");

        // 使用 Service 处理任务
        $result = $sendMailTaskService->processTasks($limit);

        $this->info($result['message']);

        if ($result['processed'] > 0) {
            $this->info("处理详情: 成功 {$result['success']} 个，失败 {$result['failed']} 个");
        }

        return self::SUCCESS;
    }
}
