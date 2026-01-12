<?php

declare(strict_types=1);

namespace XditnModule\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\text;

use XditnModule\Facade\Zipper;

class BuildCommand extends XditnModuleCommand
{
    protected $signature = 'xditn:module:build';

    protected $description = '打包后端项目';

    public function handle(): void
    {
        $useBt = $this->ask('是否使用宝塔进行部署?', 'yes') == 'yes';

        $appUrl = text(
            label: '请配置线上环境的正式域名',
            placeholder: 'eg. https://test.XditnModule.com',
            required: '正式域名',
            validate: fn ($value) => filter_var($value, FILTER_VALIDATE_URL) !== false ? null : '应用URL不符合规则'
        );

        // 获取域名
        $domain = parse_url($appUrl)['host'];
        // 检测域名设置 A 解析
        if (empty(dns_get_record($domain, DNS_A))) {
            $this->warn("检测到域名 $domain 没有 A 解析, 请在部署后解析");
        }

        $this->useBt($useBt, $appUrl, $domain);

        $this->compileWholeProject($useBt);

        if ($useBt) {
            File::delete($this->btImportSql());
            File::delete(base_path('nginx.rewrite'));
            File::delete($this->getAutoInstallJson());
        }
    }

    /**
     * @return void
     */
    protected function useBt(bool $useBt, string $appUrl, string $domain)
    {
        if ($useBt) {
            Artisan::call('schema:dump');

            if (file_exists($schemaSql = $this->getDumpSchemaSql())) {
                File::put(
                    $this->btImportSql(),
                    file_get_contents($schemaSql)
                );
            }

            File::put($this->getAutoInstallJson(), json_encode($this->authInstall(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $this->writeNginxRewrite($domain);
        }
    }

    /**
     * 打包项目.
     */
    protected function compileWholeProject(bool $useBt): void
    {
        $this->info('开始项目打包');
        // 目录忽略
        $excludePaths = [
            base_path('.github'),
            base_path('.git'),
            base_path('api-doc'),
            storage_path('uploads'),
        ];
        $zipFile = base_path().DIRECTORY_SEPARATOR.'project.zip';
        if (File::exists($zipFile)) {
            File::delete($zipFile);
        }

        $this->info('清理项目目录');
        // clean dirs
        foreach ([
            base_path('storage'.DIRECTORY_SEPARATOR.'logs'),
            base_path('storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'cache'),
            base_path('storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'sessions'),
            base_path('storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'testing'),
            base_path('storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'views'),
            base_path('storage'.DIRECTORY_SEPARATOR.'clockwork'),
        ] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            File::cleanDirectory($dir);
            File::put(
                $dir.DIRECTORY_SEPARATOR.'.gitignore',
                <<<'TEXT'
compiled.php
config.php
down
events.scanned.php
maintenance.php
routes.php
routes.scanned.php
schedule-*
services.json
TEXT
            );
        }

        $this->info('删除项目目录');

        // 删除软链接
        foreach (array_keys(config('filesystems.links')) as $link) {
            File::deleteDirectory($link);
        }

        // File::cleanDirectory(base_path('storage'.DIRECTORY_SEPARATOR.'uploads'));
        File::deleteDirectory(base_path('storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'excel'));

        // 项目打包
        Zipper::zip(base_path().DIRECTORY_SEPARATOR.'project.zip')
            ->setExcludeDirs($excludePaths)
            ->add(base_path())
            ->add(base_path('.env'))
            ->addEmptyDir('storage'.DIRECTORY_SEPARATOR.'logs')
            ->addEmptyDir('storage'.DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'public')
            ->addEmptyDir('storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'cache')
            ->addEmptyDir('storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'sessions')
            ->addEmptyDir('storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'testing')
            ->addEmptyDir('storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'views')
            ->addEmptyDir('storage'.DIRECTORY_SEPARATOR.'uploads')
            ->close();

        if (File::exists($zipFile)) {
            $this->warn('项目打包完成');
            $this->warn('项目打包文件：'.$zipFile);
            if ($useBt) {
                $this->warn('请查看对应的宝塔文档进行部署: https://doc.XditnModule.vip/start/bt-deploy');
            } else {
                $this->warn('请将打包好的项目压缩包上传至项目服务器解压');
                $this->warn('先要 chmod 777 -R storage 给予权限');
                $this->warn('再修改 .env 文件配置对应的线上数据库信息');
                $this->warn('因为使用的是打包本地所有文件，包含 vendor 文件夹，如果在线上允许出现环境问题，请自行调整');
            }
        } else {
            $this->error('项目打包失败');
        }
    }

    protected function getAutoInstallJson(): string
    {
        return base_path().DIRECTORY_SEPARATOR.'auto_install.json';
    }

    protected function getDumpSchemaSql(): string
    {
        return database_path('schema').DIRECTORY_SEPARATOR.'mysql-schema.sql';
    }

    protected function btImportSql(): string
    {
        return base_path().DIRECTORY_SEPARATOR.'import.sql';
    }

    protected function writeNginxRewrite(string $domain): void
    {
        $nginxConfig = <<<'TEXT'
location /api {
     if (!-e $request_filename) {
       rewrite  ^(.*)$  /index.php?s=/$1  last;
       break;
     }
}

location /uploads/ {
  alias /www/wwwroot/{domain}/storage/uploads/;
  autoindex on;
}

TEXT;

        File::put(base_path('nginx.rewrite'), str_replace('{domain}', $domain, $nginxConfig));
    }

    /**
     * @return array
     */
    protected function authInstall()
    {
        return [
            'php_ext' => 'opcache,bcmath, ctype,intl,dom,fileinfo, json, mbstring, openssl, pcre, pdo, tokenizer, xml, pdo_mysql,redis',
            'chmod' => [
                [
                    'mode' => 777,
                    'path' => '/storage',
                ],
            ],
            'success_url' => '/#/login',
            'php_versions' => '82,83',
            'db_config' => '',
            'admin_username' => '',
            'admin_password' => '',
            'run_path' => '/public',
            'remove_file' => [
                '/install',
                '/temp',
                '/.user.ini',
            ],
            'enable_functions' => [
                'system',
                'exec',
                'proc_open',
                'shell_exec',
                'pcntl_exec',
                'pcntl_fork',
                'pcntl_waitpid',
                'pcntl_wifexited',
                'pcntl_wifsignaled',
                'symlink',
            ],
        ];
    }
}
