<?php

namespace Modules\System\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\System\Models\ConnectorLog;
use Modules\System\Models\ConnectorLogStatistics;

class StatisticsConnectorLog extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'statistics:connector:log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '接口日志统计, 每日统计一次';

    /**
     * 日志记录.
     */
    protected array $logs = [];

    /**
     * 批量插入条数限制.
     */
    protected int $limit = 100;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $pathRequests = $this->pathRequests();

        ConnectorLogStatistics::query()->insert($pathRequests);
    }

    public function pathRequests(): array
    {
        $start = Carbon::yesterday()->startOfDay()->timestamp;

        $end = Carbon::yesterday()->endOfDay()->timestamp;

        $pathRequests = [];

        ConnectorLog::whereBetween('created_at', [$start, $end])
            ->cursor()
            ->each(function (ConnectorLog $request) use (&$pathRequests) {
                if (isset($pathRequests[$request->path])) {
                    $pathRequests[$request->path]['count'] += 1;
                    $pathRequests[$request->path]['time_taken'] += $request['time_taken'];
                    $pathRequests[$request->path][$request->isSuccessful() ? 'success_count' : 'fail_count'] += 1;
                } else {
                    $pathRequests[$request->path] = [
                        'path' => $request->path,
                        'count' => 1,
                        'fail_count' => (int) $request->isSuccessful(),
                        'success_count' => (int) $request->isSuccessful(),
                        'time_token' => $request['time_taken'],
                    ];
                }
            });

        $pathRequests = array_values($pathRequests);

        $now = time();

        foreach ($pathRequests as &$pathRequest) {
            $pathRequest['average_time_taken'] = intval($pathRequest['time_taken'] / $pathRequest['count']);
            unset($pathRequest['time_taken']);
            // 添加时间戳字段（XditnModuleModel 使用 Unix 时间戳格式）
            $pathRequest['created_at'] = $now;
            $pathRequest['updated_at'] = $now;
        }

        return $pathRequests;
    }
}
