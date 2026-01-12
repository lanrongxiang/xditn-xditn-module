<?php

declare(strict_types=1);

namespace XditnModule\Commands;

use Illuminate\Console\Application;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

use Symfony\Component\Process\ExecutableFinder;
use XditnModule\Support\Composer;
use XditnModule\XditnModule;

class InstallCommand extends XditnModuleCommand
{
    protected bool $isFinished = false;

    protected $signature = 'xditn:module:install {--prod} {--docker} {--modules=* : 指定要安装的模块，例如 --modules=Ai --modules=Cms}';

    protected $description = 'install xditnmodule';

    /**
     * 默认链接 [mysql, pgsql].
     *
     * @var string
     */
    protected string $defaultConnection;

    protected bool $isProd;

    protected string $appUrl = 'http://127.0.0.1:8000';

    /**
     * @var array|string[]
     */
    private array $defaultExtensions = ['bcmath', 'ctype', 'intl', 'dom', 'mysqli', 'fileinfo', 'json', 'mbstring', 'openssl', 'pcre', 'pdo', 'tokenizer', 'xml', 'pdo_mysql'];

    /**
     * handle.
     */
    public function handle(): void
    {
        if ($this->isRunningInDocker()) {
            $this->runningInDocker();
        } else {
            $this->detectionEnvironment();

            // 是否是生产环境
            $this->isProd = $this->option('prod');

            // 捕捉退出信号
            if (extension_loaded('pcntl')) {
                $this->trap([SIGTERM, SIGQUIT, SIGINT], function () {
                    if (!$this->isFinished) {
                        $this->rollback();
                    }

                    exit;
                });
            }

            try {
                // 如果没有 .env 文件
                if (!File::exists(app()->environmentFile())) {
                    $this->askForCreatingDatabase();
                }

                $this->publishConfig();
                $this->createStorageLink();
                $this->installed();
            } catch (\Throwable $e) {
                $this->rollback();

                $this->error($e->getMessage());
            }
        }
    }

    /**
     * @return void
     */
    protected function runningInDocker(): void
    {
        try {
            // 复制一个 .env 文件
            if (!File::exists(app()->environmentFilePath())) {
                File::copy(app()->environmentFilePath().'.example', app()->environmentFilePath());
            }

            $databaseName = env('DB_DATABASE');

            $this->info("正在创建数据库[$databaseName]...");

            $this->createDatabase($databaseName, $this->defaultConnection);

            $this->info("创建数据库[$databaseName] 成功");

            $this->publishConfig();

            $this->installed();
        } catch (\Throwable $e) {
            $this->rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * @return void
     */
    private function createStorageLink(): void
    {
        command('storage:link');
    }

    /**
     * 环境检测.
     */
    protected function detectionEnvironment(): void
    {
        $this->checkDependenciesTools();

        $this->checkPHPVersion();

        $this->checkExtensions();
    }

    /**
     * check needed php extensions.
     */
    private function checkExtensions(): void
    {
        // @var  Collection $loadedExtensions
        $loadedExtensions = Collection::make(get_loaded_extensions())->map(function ($item) {
            return strtolower($item);
        });

        $unLoadedExtensions = [];
        foreach ($this->defaultExtensions as $extension) {
            if (!$loadedExtensions->contains($extension)) {
                $unLoadedExtensions[] = $extension;
            }
        }

        if (count($unLoadedExtensions) > 0) {
            $this->error('PHP 扩展未安装:'.implode(' | ', $unLoadedExtensions));
            exit;
        }
    }

    /**
     * check php version.
     */
    private function checkPHPVersion(): void
    {
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $this->error('PHP 版本必须大于等于 8.2');
            exit(1);
        }
    }

    /**
     * 检测依赖.
     */
    protected function checkDependenciesTools(): void
    {
        $executeFinder = new ExecutableFinder();
        $composer = $executeFinder->find('composer');
        $git = $executeFinder->find('git');
        if (!$git) {
            $this->error('Git 未安装');
            exit;
        }
        if (!$composer) {
            $this->error('Composer 未安装');
            exit;
        }

        if (!function_exists('exec')) {
            $this->error('exec 函数未开启，请开启 exec 函数');
            exit;
        }
    }

    /**
     * create database.
     *
     * @throws BindingResolutionException
     */
    private function createDatabase(string $databaseName, string $driver): void
    {
        if ($driver == 'mysql') {
            $databaseConfig = config('database.connections.'.DB::getDefaultConnection());

            $databaseConfig['database'] = null;

            $connection = app(ConnectionFactory::class)->make($databaseConfig);
            try {
                $connection->getPdo();
            } catch (\Throwable $e) {
                if ($e->getCode() === 2002) {
                    $this->error('Mysql 无法连接，请查看 MySQL 服务是否启动');
                } else {
                    $this->error($e->getMessage());
                }
                exit;
            }

            if (!$connection->getDatabaseName()) {
                app(ConnectionFactory::class)->make($databaseConfig)->select(sprintf("create database if not exists $databaseName default charset %s collate %s", 'utf8mb4', 'utf8mb4_general_ci'));
            }
        } else {
            $databaseConfig = config('database.connections.'.$driver);

            $databaseConfig['database'] = null;

            $connection = app(ConnectionFactory::class)->make($databaseConfig);
            try {
                $connection->getPdo();
            } catch (\Throwable $e) {
                if ($e->getCode() === 7) {
                    $this->error('PgSQL 无法连接，请查看 PgSQL 服务是否启动');
                } else {
                    $this->error($e->getMessage());
                }
                exit;
            }

            if (!$connection->getDatabaseName()) {
                app(ConnectionFactory::class)->make($databaseConfig)
                    ->select(sprintf("create database $databaseName WITH ENCODING = '%s' LC_COLLATE = 'en_US.UTF-8' LC_CTYPE = 'en_US.UTF-8' TEMPLATE = template0;", 'UTF-8'));
            }
        }
    }

    /**
     * copy .env.
     */
    protected function copyEnvFile(): void
    {
        if (!File::exists(app()->environmentFilePath())) {
            File::copy(app()->environmentFilePath().'.example', app()->environmentFilePath());
        }

        if (!File::exists(app()->environmentFilePath())) {
            $this->error('【.env】创建失败, 请重新尝试或者手动创建！');
        }

        File::put(app()->environmentFile(), implode("\n", explode("\n", $this->getEnvFileContent())));
    }

    /**
     * get env file content.
     */
    protected function getEnvFileContent(): string
    {
        return File::get(app()->environmentFile());
    }

    /**
     * publish config.
     */
    protected function publishConfig(): void
    {
        try {
            // mac os
            if (Str::of(PHP_OS)->lower()->contains('dar')) {
                exec(Application::formatCommandString('key:generate'));
                exec(Application::formatCommandString('jwt:secret'));
                exec(Application::formatCommandString('vendor:publish --tag=xditn-config'));
                if ($this->isShouldPublishSanctum()) {
                    exec(Application::formatCommandString('vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"'));
                }

                exec(Application::formatCommandString('migrate'));
            } else {
                Process::run(Application::formatCommandString('key:generate'))->throw();
                Process::run(Application::formatCommandString('jwt:secret'))->throw();
                Process::run(Application::formatCommandString('vendor:publish --tag=xditn-config'))->throw();
                if ($this->isShouldPublishSanctum()) {
                    Process::run(Application::formatCommandString('vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"'))->throw();
                }
                Process::run(Application::formatCommandString('migrate'))->throw();
            }

            // 获取要安装的模块列表
            $modulesToInstall = $this->getModulesToInstall();

            // 安装默认模块（user, develop）
            foreach (['user', 'develop'] as $name) {
                $this->migrateModule($name);
            }

            // 安装指定模块和默认模块
            foreach ($modulesToInstall as $name) {
                try {
                    XditnModule::getModuleInstaller($name)->install();
                    $this->info("模块 [{$name}] 安装成功");
                } catch (\Throwable $e) {
                    $this->warn("模块 [{$name}] 安装失败: {$e->getMessage()}");
                }
            }

        } catch (\Exception|\Throwable $e) {
            $this->warn($e->getMessage());
        }
    }

    protected function migrateModule(string $name): void
    {
        $migrationStr = sprintf('xditn:module:migrate %s', $name);
        $seedStr = sprintf('xditn:module:db:seed %s', $name);

        command([$migrationStr, $seedStr]);
    }

    /**
     * create database.
     *
     * @throws BindingResolutionException
     */
    protected function askForCreatingDatabase(): void
    {
        $appName = text('请输入应用名称', required: '应用名称必须填写');

        $appUrl = text(
            label: '请配置应用的 URL',
            placeholder: 'eg. http://127.0.0.1:8000',
            default: $this->isProd ? 'https://' : 'http://127.0.0.1:8000',
            required: '应用的 URL 必须填写',
            validate: fn ($value) => filter_var($value, FILTER_VALIDATE_URL) !== false ? null : '应用URL不符合规则'
        );

        $this->defaultConnection = select(
            label: '选择数据库驱动',
            options: ['mysql', 'pgsql'],
            default: 'mysql',
        );

        if ($this->defaultConnection == 'pgsql' && !extension_loaded('pdo_pgsql')) {
            $this->error('请先安装 pdo_pgsql 扩展');
            exit;
        }

        $databaseName = text('请输入数据库名称', required: '请输入数据库名称', validate: fn ($value) => preg_match("/[a-zA-Z\_]{1,100}/", $value) ? null : '数据库名称只支持a-z和A-Z以及下划线_');
        $prefix = text('请输入数据库表前缀');
        $dbHost = text('请输入数据库主机地址', 'eg. 127.0.0.1', '127.0.0.1', required: '请输入数据库主机地址');
        $dbPort = text('请输入数据库主机地址', 'eg. 3306', $this->defaultConnection === 'mysql' ? '3306' : '5432', required: '请输入数据库主机地址');
        $dbUsername = text('请输入数据的用户名', 'eg. root', 'root', required: '请输入数据的用户名');
        $dbPassword = text('请输入数据库密码', required: '请输入数据库密码');

        config()->set('database.default', $this->defaultConnection);
        config()->set('database.connections.'.$this->defaultConnection.'.host', $dbHost);
        config()->set('database.connections.'.$this->defaultConnection.'.port', $dbPort);
        config()->set('database.connections.'.$this->defaultConnection.'.database', $databaseName);
        config()->set('database.connections.'.$this->defaultConnection.'.username', $dbUsername);
        config()->set('database.connections.'.$this->defaultConnection.'.password', $dbPassword);
        config()->set('database.connections.'.$this->defaultConnection.'.prefix', $prefix);

        $this->info("正在创建数据库[$databaseName]...");

        $this->createDatabase($databaseName, $this->defaultConnection);

        $this->info("创建数据库[$databaseName] 成功");

        // 写入 .env
        $this->createEnvFile(
            $appName,
            $appUrl,
            $this->defaultConnection,
            $dbHost,
            $dbPort,
            $databaseName,
            $dbUsername,
            $dbPassword,
            $prefix
        );

        // 设置默认字符串长度
        Schema::connection($this->defaultConnection)->defaultStringLength(191);
    }

    protected function resetEnvValue($originValue, $newValue): string
    {
        if (Str::contains($originValue, '=')) {
            $originValue = explode('=', $originValue);

            $originValue[1] = $newValue;

            return implode('=', $originValue);
        }

        return $originValue;
    }

    /**
     * add prs4 autoload.
     */
    protected function addPsr4Autoload(): void
    {
        $composerJson = $this->getComposerJson();

        $composerJson['autoload']['psr-4'][XditnModule::getModuleRootNamespace()] = str_replace('\\', '/', XditnModule::moduleRoot());

        File::put($this->getComposerFile(), json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('composer dump autoload..., 请耐心等待');

        app(Composer::class)->dumpAutoloads();
    }

    protected function getComposerJson(): mixed
    {
        return json_decode(File::get($this->getComposerFile()), true);
    }

    protected function getComposerFile(): string
    {
        return base_path().DIRECTORY_SEPARATOR.'composer.json';
    }

    /**
     * admin installed.
     */
    public function installed(): void
    {
        $this->addPsr4Autoload();

        $this->info('🎉 XditnModule 已安装, 欢迎!');

        $this->isFinished = true;

        $this->output->info(sprintf('
 /------------------------ welcome ----------------------------\
|               __       __       ___       __          _      |
|   _________ _/ /______/ /_     /   | ____/ /___ ___  (_)___  |
|  / ___/ __ `/ __/ ___/ __ \   / /| |/ __  / __ `__ \/ / __ \ |
| / /__/ /_/ / /_/ /__/ / / /  / ___ / /_/ / / / / / / / / / / |
| \___/\__,_/\__/\___/_/ /_/  /_/  |_\__,_/_/ /_/ /_/_/_/ /_/  |
|                                                              |
 \ __ __ __ __ _ __ _ __ enjoy it ! _ __ __ __ __ __ __ ___ _ @
 版本: %s
 初始账号: admin@xditn.com
 初始密码: xditn', XditnModule::VERSION));

        $this->support();
    }

    /**
     * support.
     */
    protected function support(): void
    {
        $answer = $this->askFor('非常感谢支持我们! 是否打开文档', 'yes', true);

        if (in_array(strtolower($answer), ['yes', 'y'])) {
            if (PHP_OS_FAMILY == 'Darwin') {
                exec('open https://doc.XditnModule.vip/start/overview');
            }
            if (PHP_OS_FAMILY == 'Windows') {
                exec('start https://doc.XditnModule.vip/start/overview');
            }
            if (PHP_OS_FAMILY == 'Linux') {
                exec('xdg-open https://doc.XditnModule.vip/start/overview');
            }
        }

        $this->info('官 网: https://XditnModule.vip');
        $this->info('文 档: https://doc.XditnModule.vip/start/overview');
        $this->info('启动后端: php artisan serve');
    }

    protected function createEnvFile(
        $appName,
        $appUrl,
        $driver,
        $dbHost,
        $dbPort,
        $databaseName,
        $dbUsername,
        $dbPassword,
        $prefix
    ): void {
        // 后端项目 .env
        $this->copyEnvFile();

        $env = explode("\n", $this->getEnvFileContent());

        foreach ($env as &$value) {
            foreach ([
                'APP_NAME' => $appName,
                'APP_ENV' => $this->isProd ? 'production' : 'local',
                'APP_DEBUG' => $this->isProd ? 'false' : 'true',
                'APP_URL' => $appUrl,
                'DB_CONNECTION' => $driver,
                'DB_HOST' => $dbHost,
                'DB_PORT' => $dbPort,
                'DB_DATABASE' => $databaseName,
                'DB_USERNAME' => $dbUsername,
                'DB_PASSWORD' => $dbPassword,
                'DB_PREFIX' => $prefix,
            ] as $key => $newValue) {
                if (Str::contains($value, $key) && !Str::contains($value, 'VITE_')) {
                    $value = $this->resetEnvValue($value, $newValue);
                }
            }
        }

        File::put(app()->environmentFile(), implode("\n", $env));

        $this->appUrl = $appUrl;
    }

    protected function rollback(): void
    {
        try {
            if (File::exists(app()->environmentFile())) {
                File::delete(app()->environmentFile());
            }

            foreach (['permissions', 'system'] as $name) {
                XditnModule::getModuleInstaller($name)->uninstall();
            }

            $databaseConfig = config('database.connections.'.$this->defaultConnection);

            $databaseName = $databaseConfig['database'];

            app(ConnectionFactory::class)->make($databaseConfig)->select("drop database $databaseName");
        } catch (\Throwable $e) {
        }
    }

    /**
     * 是否发布 sanctum 配置.
     */
    protected function isShouldPublishSanctum(): bool
    {
        return !($this->isPersonalTokenTableExist() && $this->isHasSanctumConfig());
    }

    protected function isPersonalTokenTableExist(): bool
    {
        foreach (File::allFiles(database_path('migrations')) as $file) {
            if (Str::of($file->getFilename())->contains('personal_access_tokens')) {
                return true;
            }
        }

        return false;
    }

    protected function isHasSanctumConfig(): bool
    {
        return file_exists(config_path('sanctum.php'));
    }

    /**
     * 是否运行在 docker 内.
     *
     * @return bool
     */
    protected function isRunningInDocker(): bool
    {
        return $this->option('docker');
    }

    /**
     * 获取要安装的模块列表.
     *
     * @return array<string>
     */
    protected function getModulesToInstall(): array
    {
        $modules = [];

        // 从命令行参数获取指定模块
        $specifiedModules = $this->option('modules');
        if (!empty($specifiedModules) && is_array($specifiedModules)) {
            foreach ($specifiedModules as $module) {
                $modules[] = ucfirst(strtolower($module));
            }
        }

        // 从配置文件获取默认模块
        $defaultModules = config('xditn.module.default', []);
        if (is_array($defaultModules)) {
            foreach ($defaultModules as $module) {
                $moduleName = ucfirst(strtolower($module));
                // 避免重复添加
                if (!in_array($moduleName, $modules, true)) {
                    $modules[] = $moduleName;
                }
            }
        }

        // 如果没有指定模块，安装默认的核心模块
        if (empty($modules)) {
            $modules = ['permissions', 'system'];
        }

        return $modules;
    }
}
