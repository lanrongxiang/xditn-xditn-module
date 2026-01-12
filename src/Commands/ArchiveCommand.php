<?php

namespace XditnModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\multiselect;

class ArchiveCommand extends Command
{
    protected $signature = 'xditn:module:archive:tags';

    protected $description = '打包仓库某两个标签之间的差异文件';

    public function handle(): void
    {
        $process = Process::run('git fetch --tags');

        if ($process->successful()) {
            $process = Process::run('git tag');

            if ($process->successful()) {
                $tags = array_filter(explode("\n", $process->output()));

                $selectTags = (multiselect('选择标签', $tags));

                if (count($selectTags) !== 2) {
                    $this->error('只能选择两个标签');
                } else {
                    [$start, $end] = $selectTags;

                    $zip = "{$end}.zip";

                    $process = Process::run("git diff --name-only --diff-filter=ACMRT -z {$start} {$end} | xargs -0 git archive -o {$zip} {$end}");

                    if ($process->successful()) {
                        if (file_exists(base_path($zip))) {
                            $this->info(base_path($zip).' 增量包打包成功');
                        } else {
                            $this->error(base_path($zip).' 增量包打包失败');
                        }
                    }
                }
            }
        } else {
            $this->error('拉取仓库 tags 失败，请手动拉取');
        }
    }
}
