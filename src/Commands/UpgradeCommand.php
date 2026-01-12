<?php

namespace XditnModule\Commands;

use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

use function Laravel\Prompts\spin;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * 更新命令.
 *
 * Class UpgradeCommand
 */
class UpgradeCommand extends Command
{
    protected $signature = 'xditn:module:upgrade {zip : 升级包 zip 的完整路径}';

    protected $description = '应用增量升级包（ZIP）并自动校验、备份、回滚';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $zipFile = base_path($this->argument('zip') ?? '');
        if (!$zipFile || !is_file($zipFile) || !Str::endsWith($zipFile, '.zip')) {
            $this->error("升级包无效：$zipFile");

            exit();
        }

        $tmpDir = storage_path('app/patch_'.Str::random(8));
        $backupDir = storage_path('app/patch_backup/'.now()->format('Ymd_His'));

        try {
            $manifest = spin(
                fn () => $this->deal($zipFile, $tmpDir, $backupDir),
                '升级中...'
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

            $this->deleteTmpAndBackupDir($tmpDir, $backupDir);
        } catch (\Throwable $e) {
            $this->error('升级失败：'.$e->getMessage());
            $this->deleteTmpAndBackupDir($tmpDir, $backupDir);
        } finally {

        }
    }

    /**
     * @param $zipFile
     * @param $tmpDir
     * @param $backupDir
     *
     * @return mixed
     */
    protected function deal($zipFile, $tmpDir, $backupDir): mixed
    {
        try {
            $this->extract($zipFile, $tmpDir);
            $manifest = $this->validatePackage($tmpDir);
            $this->backupFiles($manifest, $backupDir);
            $this->applyChanges($tmpDir, $manifest);
        } catch (\Throwable $e) {
            $this->restore($backupDir);
            throw new RuntimeException($e->getMessage());
        }

        return $manifest;
    }

    /**
     * @param $tmpDir
     * @param $backupDir
     *
     * @return void
     */
    private function deleteTmpAndBackupDir($tmpDir, $backupDir): void
    {
        File::isDirectory($tmpDir) && File::deleteDirectory($tmpDir) && File::delete($tmpDir);
        File::isDirectory($backupDir) && File::deleteDirectory($backupDir) && File::delete($tmpDir);
    }

    /**
     * 解压文件.
     *
     * @throws \Exception
     */
    private function extract(string $zip, string $to): void
    {
        $zipObj = new ZipArchive();
        if ($zipObj->open($zip) !== true) {
            throw new RuntimeException('无法打开 ZIP');
        }
        $zipObj->extractTo($to);
        $zipObj->close();
    }

    /**
     * 校验升级包.
     *
     * @throws \JsonException
     */
    private function validatePackage(string $dir): array
    {
        $manifestFile = "$dir/manifest.json";
        $filesDir = "$dir/files";

        if (!is_file($manifestFile) || !is_dir($filesDir)) {
            throw new RuntimeException('升级包缺少 manifest.json 或 files 目录');
        }

        $manifest = json_decode(File::get($manifestFile), true, 512, JSON_THROW_ON_ERROR);

        // 关键字段
        foreach (['from', 'to', 'files'] as $key) {
            if (!isset($manifest[$key])) {
                throw new RuntimeException("manifest 缺失字段: $key");
            }
        }

        // sha1 校验
        foreach (['added', 'modified', 'renamed'] as $type) {
            foreach ($manifest['files'][$type] ?? [] as $item) {
                $path = $item['path'] ?? ($item['to'] ?? '');
                $expected = $item['sha1'] ?? '';
                $filePath = "$filesDir/$path";
                if (!is_file($filePath) || sha1_file($filePath) !== $expected) {
                    throw new RuntimeException("文件校验失败: $path");
                }

                // 路径安全（禁止 .. / 禁止绝对路径）
                if (str_contains($path, '..') || Str::startsWith($path, ['/', '\\'])) {
                    throw new RuntimeException("非法路径: $path");
                }
            }
        }

        return $manifest;
    }

    /**
     * 备份文件.
     */
    private function backupFiles(array $manifest, string $backup): void
    {
        File::ensureDirectoryExists($backup);

        $targets = collect($manifest['files']['modified'] ?? [])
            ->merge($manifest['files']['deleted'] ?? [])
            ->pluck('path')
            ->merge(collect($manifest['files']['renamed'] ?? [])->pluck('from'))
            ->unique();

        foreach ($targets as $file) {
            $src = base_path($file);
            if (is_file($src)) {
                $dest = "$backup/$file";
                File::ensureDirectoryExists(dirname($dest));
                File::copy($src, $dest);
            }
        }
    }

    /**
     * 升级，更新文件.
     */
    private function applyChanges(string $dir, array $manifest): void
    {
        $filesDir = "$dir/files";

        // 复制新增/修改/重命名后的文件
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($filesDir, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $rel = Str::after($file->getPathname(), $filesDir.DIRECTORY_SEPARATOR);
            $dest = base_path($rel);
            File::ensureDirectoryExists(dirname($dest));
            File::copy($file->getPathname(), $dest);
        }

        // 删除
        foreach ($manifest['files']['deleted'] ?? [] as $del) {
            $path = base_path($del);
            if (is_file($path)) {
                File::delete($path);
            }
        }

        // 重命名
        foreach ($manifest['files']['renamed'] ?? [] as $r) {
            $old = base_path($r['from']);
            $new = base_path($r['to']);
            if (is_file($old)) {
                File::ensureDirectoryExists(dirname($new));
                rename($old, $new);
            }
        }
    }

    /**
     * 回滚.
     */
    private function restore(string $backup): void
    {
        if (!is_dir($backup)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($backup, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $rel = Str::after($file->getPathname(), $backup.DIRECTORY_SEPARATOR);
            $dest = base_path($rel);
            File::ensureDirectoryExists(dirname($dest));
            File::copy($file->getPathname(), $dest);
        }
        $this->warn('已回滚到升级前状态');
    }
}
