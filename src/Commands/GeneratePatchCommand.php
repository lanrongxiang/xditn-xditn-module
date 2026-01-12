<?php

namespace XditnModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

use function Laravel\Prompts\spin;

use XditnModule\Facade\Zipper;

class GeneratePatchCommand extends Command
{
    protected $signature = 'xditn:module:generate:patch {from : 起始 tag/commit} {to : 目标 tag/commit}';

    protected $description = '生成版本增量更新包';

    public function handle(): int
    {
        $from = (string) $this->argument('from');
        $to = (string) $this->argument('to');
        $output = storage_path('patches');
        $zipName = sprintf('%s_%s.zip', $from, $to);

        $manifest = spin(
            fn () => $this->deal($from, $to, $output, $zipName),
            '生成中...'
        );

        $info = [];
        foreach ($manifest['files'] as $operate => $file) {
            $info[] = [
                match ($operate) {
                    'added' => '新增',
                    'modified' => '修改',
                    'deleted' => '删除',
                    'renamed' => '重命名',
                    default => 'unknown',
                },
                count($file).'个',
            ];
        }
        $this->table([
            '文件操作', '数量',
        ], $info);

        $this->info('增量包保存在:'.$output.DIRECTORY_SEPARATOR.$zipName);

        return self::SUCCESS;
    }

    public function deal($from, $to, $output = '', $zipName = '')
    {

        $tmp = storage_path('app/patch_'.Str::random(8));
        File::ensureDirectoryExists("{$tmp}/files");

        $manifest = [
            'from' => $from,
            'to' => $to,
            'generated_at' => now()->toIso8601String(),
            'files' => ['added' => [], 'modified' => [], 'deleted' => [], 'renamed' => []],
        ];

        foreach ($this->diff($from, $to) as $item) {
            [$status, $path, $pathNew] = $item;

            match ($status) {
                'A', 'M' => $this->copyFile($to, $path, $status, $tmp, $manifest),
                'D' => $manifest['files']['deleted'][] = $path,
                'R' => $this->handleRename($to, $path, $pathNew, $tmp, $manifest),
            };
        }

        File::put("{$tmp}/manifest.json", json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        ));

        $this->zip($tmp, rtrim($output, '/').'/'.$zipName);

        File::deleteDirectory($tmp);

        return $manifest;
    }

    /** @return array<int, array{string,string,string|null}> */
    private function diff(string $from, string $to): array
    {
        // 使用 -z 可避免文件名含空格导致解析错误
        $raw = $this->git(sprintf('git diff --name-status -z %s %s', $from, $to));
        $parts = array_values(array_filter(explode("\0", $raw)));
        $items = [];

        for ($i = 0; $i < \count($parts); $i += 2) {
            $status = $parts[$i];
            $path = $parts[$i + 1] ?? null;

            if ($status === null || $path === null) {
                break;
            }

            if (\str_starts_with($status, 'R')) {
                // R100 old\0new\0
                $pathNew = $parts[$i + 2] ?? null;
                $items[] = ['R', $path, $pathNew];
                $i++; // 额外跳过 newPath
            } else {
                $items[] = [$status, $path, null];
            }
        }

        return $items;
    }

    private function copyFile(
        string $commit,
        string $path,
        string $status,
        string $tmp,
        array &$manifest
    ): void {
        $content = $this->git("git show {$commit}:{$path}");
        $target = "{$tmp}/files/{$path}";
        File::ensureDirectoryExists(\dirname($target));
        File::put($target, $content);

        $key = $status === 'A' ? 'added' : 'modified';
        $manifest['files'][$key][] = ['path' => $path, 'sha1' => \sha1($content)];
    }

    private function handleRename(
        string $commit,
        string $old,
        string $new,
        string $tmp,
        array &$manifest
    ): void {
        $content = $this->git("git show {$commit}:{$new}");
        $target = "{$tmp}/files/{$new}";
        File::ensureDirectoryExists(\dirname($target));
        File::put($target, $content);

        $manifest['files']['renamed'][] = [
            'from' => $old,
            'to' => $new,
            'sha1' => \sha1($content),
        ];
    }

    private function zip(string $dir, string $zipPath): void
    {
        File::ensureDirectoryExists(\dirname($zipPath));

        Zipper::make($zipPath)
            ->add($dir)
            ->close();

    }

    private function git(string $cmd): string
    {
        $proc = Process::run($cmd);
        if ($proc->failed()) {
            $this->error($proc->errorOutput());
            exit(1);
        }

        return $proc->output();
    }
}
