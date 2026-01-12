# XditnModule

XditnModule 是一个基于 Laravel 的模块化后台管理框架核心包，提供模块管理、CRUD 生成、权限控制、API 版本控制等功能。

## 环境要求

- PHP 8.2+
- Laravel 12.0+
- MySQL 5.7+ / MariaDB 10.3+
- Redis（可选，用于缓存和队列）

## 安装

### 1. 通过 Composer 安装

```bash
composer require xditn/xditn-module
```

### 2. 发布配置文件

```bash
php artisan vendor:publish --provider="XditnModule\Providers\XditnModuleServiceProvider"
```

### 3. 运行系统安装命令

```bash
php artisan xditn:module:install
```

**生产环境：**
```bash
php artisan xditn:module:install --prod
```

**Docker 环境：**
```bash
php artisan xditn:module:install --docker
```

## 目录结构

```
project/
├── modules/                    # 模块目录
│   ├── User/                   # 用户模块
│   ├── Permissions/            # 权限模块
│   └── ...
├── config/
│   └── xditn.php              # 框架配置
└── ...
```

### 模块目录结构

```
modules/{ModuleName}/
├── database/
│   ├── migrations/            # 数据库迁移
│   └── seeders/              # 数据填充
├── Http/
│   ├── Controllers/          # 控制器
│   ├── Requests/             # 表单请求验证
│   └── Resources/            # API 资源
├── Models/                    # 模型
├── Services/                  # 服务类
├── Observers/                 # 模型观察者
├── Enums/                     # 枚举类
├── routes/
│   └── route.php             # 模块路由
└── Installer.php             # 模块安装器
```

## 快速开始

### 创建 CRUD

```bash
# 创建迁移
php artisan xditn:module:make:migration Pay recharge_activities

# 运行迁移
php artisan xditn:module:migrate Pay

# 生成 CRUD
php artisan xditn:module:make:crud Pay RechargeActivity --subgroup=充值管理
```

### 控制器示例

```php
<?php

declare(strict_types=1);

namespace Modules\Pay\Http\Controllers;

use Modules\Pay\Models\RechargeActivity;
use XditnModule\Base\XditnModuleController as Controller;

/**
 * @group 管理端
 * @subgroup 充值活动管理
 */
class RechargeActivityController extends Controller
{
    public function __construct(
        protected readonly RechargeActivity $model
    ) {}

    public function index(): mixed
    {
        return $this->model->getList();
    }

    public function store(): mixed
    {
        return $this->model->storeBy(request()->all());
    }

    public function show(int $id): mixed
    {
        return $this->model->firstBy($id);
    }

    public function update(int $id): mixed
    {
        return $this->model->updateBy($id, request()->all());
    }

    public function destroy(int $id): mixed
    {
        return $this->model->deleteBy($id);
    }
}
```

### 模型示例

```php
<?php

declare(strict_types=1);

namespace Modules\Pay\Models;

use XditnModule\Base\XditnModuleModel;

class RechargeActivity extends XditnModuleModel
{
    protected $table = 'recharge_activities';

    protected $fillable = ['id', 'title', 'description', 'type', 'status'];

    protected array $fields = ['id', 'title', 'type', 'status', 'created_at'];

    public array $searchable = [
        'title' => 'like',
        'type' => '=',
        'status' => '=',
    ];
}
```

## 常用命令

### 迁移命令

```bash
php artisan xditn:module:make:migration {模块名} {表名}
php artisan xditn:module:migrate {模块名}
php artisan xditn:module:migrate:fresh {模块名}
php artisan xditn:module:migrate:rollback {模块名}
```

### 代码生成命令

```bash
php artisan xditn:module:make:controller {模块名} {控制器名}
php artisan xditn:module:make:model {模块名} {模型名} --t=表名
php artisan xditn:module:make:crud {模块名} {资源名} --subgroup=分组名
php artisan xditn:module:make:observer {模块名} {模型名}
php artisan xditn:module:make:resource {模块名} {资源名}
```

### Seeder 命令

```bash
php artisan xditn:module:make:seeder {模块名} {Seeder名称}
php artisan xditn:module:db:seed {模块名}
```

### 维护命令

```bash
php artisan xditn:module:purge-trashed --days=30   # 清理软删除数据
php artisan xditn:module:api:doc                    # 生成 API 文档
php artisan xditn:module:update:password            # 更新管理员密码
php artisan xditn:module:version                    # 查看版本
```

## 核心功能

### 模型基础操作

| 方法 | 说明 |
|------|------|
| `getList()` | 获取列表（支持分页、搜索、排序） |
| `storeBy(array $data)` | 保存数据 |
| `updateBy($id, array $data)` | 更新数据 |
| `firstBy($value, $field)` | 查询单条记录 |
| `deleteBy($id)` | 删除记录 |
| `deletesBy($ids)` | 批量删除 |
| `restoreBy($ids)` | 恢复软删除记录 |
| `toggleBy($id, $field)` | 切换状态 |

### 链式查询

```php
$this->model->setBeforeGetList(fn($q) => $q->with('user'))->getList();
$this->model->disablePaginate()->getList();
$this->model->asTree()->getList();
$this->model->setSearchable(['title' => 'like'])->getList();
```

### 枚举

```php
use XditnModule\Enums\Enum;
use XditnModule\Enums\EnumTrait;

enum Status: int implements Enum
{
    use EnumTrait;

    case Enable = 1;
    case Disable = 2;

    public function label(): string
    {
        return match ($this) {
            self::Enable => '启用',
            self::Disable => '禁用',
        };
    }
}

// 使用
$options = Status::options();  // [['value' => 1, 'label' => '启用'], ...]
$values = Status::values();    // [1, 2]
```

### 金额处理

```php
use XditnModule\Traits\DB\AmountTrait;

class Order extends XditnModuleModel
{
    use AmountTrait;

    protected array $amountFields = ['amount', 'discount_amount'];
}

// 入库：100.50 USD -> 10050 分
// 出库：10050 分 -> 100.50 USD
```

### 缓存装饰器

```php
use XditnModule\Traits\DB\Cacheable;

class UserService
{
    use Cacheable;

    protected int $cacheTTL = 3600;
    protected ?string $cachePrefix = 'user';

    public function getUser(int $id): ?User
    {
        return $this->remember("user:{$id}", fn() => User::find($id));
    }
}
```

### 查询过滤器

```php
use XditnModule\Traits\DB\HasFilters;
use XditnModule\Support\Query\Filters\StatusFilter;
use XditnModule\Support\Query\Filters\DateRangeFilter;

class Order extends XditnModuleModel
{
    use HasFilters;

    protected array $filters = [
        'status' => StatusFilter::class,
        'date_range' => DateRangeFilter::class,
    ];
}

// 使用
Order::filter(['status' => 1, 'date_range' => ['2024-01-01', '2024-12-31']])->get();
```

### API 版本控制

```php
use XditnModule\Support\ApiVersion;

// 注册版本化路由
ApiVersion::routes('v1', function () {
    Route::get('users', [UserController::class, 'index']);
});

// 版本判断
if (ApiVersion::gte('v2')) {
    // v2+ 逻辑
}
```

### 异常类型

| 异常类 | HTTP 状态码 | 说明 |
|--------|------------|------|
| `ValidationException` | 422 | 验证失败 |
| `AuthenticationException` | 401 | 认证失败 |
| `AuthorizationException` | 403 | 授权失败 |
| `ResourceNotFoundException` | 404 | 资源不存在 |
| `BusinessException` | 400 | 业务异常 |
| `RateLimitException` | 429 | 限流异常 |
| `ServiceUnavailableException` | 503 | 服务不可用 |

```php
throw new BusinessException('余额不足', ['required' => 100, 'current' => 50]);
```

## 中间件

### 速率限制

```php
Route::middleware(RateLimitMiddleware::class)->group(function () {
    // 默认每分钟 60 次请求
});
```

### 请求追踪

```php
Route::middleware(RequestTracingMiddleware::class)->group(function () {
    // 自动添加 X-Trace-Id 响应头
});

// 获取追踪 ID
$traceId = request()->attributes->get('trace_id');
```

### 响应压缩

```php
Route::middleware(CompressResponseMiddleware::class)->group(function () {
    // 自动 Gzip 压缩响应
});
```

## 健康检查

```php
Route::get('health', [HealthController::class, 'index']);
Route::get('health/ready', [HealthController::class, 'ready']);
Route::get('health/live', [HealthController::class, 'live']);
```

响应示例：
```json
{
    "status": "healthy",
    "timestamp": "2024-01-01T00:00:00Z",
    "checks": {
        "database": {"status": "ok", "latency_ms": 5.2},
        "redis": {"status": "ok", "latency_ms": 1.1},
        "cache": {"status": "ok", "latency_ms": 2.3}
    }
}
```

## 配置说明

`config/xditn.php` 主要配置项：

```php
return [
    'super_admin' => 1,                    // 超级管理员 ID
    'request_allowed' => true,             // GET 请求免权限
    
    'module' => [
        'root' => 'modules',               // 模块目录
        'namespace' => 'Modules',          // 命名空间
        'autoload' => true,                // 自动加载
    ],
    
    'route' => [
        'prefix' => 'api',
        'version' => 'v1',
        'supported_versions' => ['v1', 'v2'],
    ],
    
    'amount' => [
        'conversion_rate' => 100,          // 金额转换比例
    ],
    
    'rate_limit' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
    
    'soft_delete' => [
        'auto_purge' => true,
        'purge_after_days' => 30,
    ],
    
    'query_log' => [
        'enabled' => false,
        'slow_threshold' => 1000,          // 慢查询阈值（毫秒）
    ],
];
```

## Octane 支持

在 `config/octane.php` 中配置：

```php
return [
    'listeners' => [
        RequestReceived::class => [
            XditnModule\Octane\RegisterExceptionHandler::class
        ],
    ],
];
```

## 常见问题

### 时间显示为 1970-01-01

从 `$fillable` 中移除时间戳字段（`created_at`, `updated_at`, `deleted_at`）。

### decimal 字段报错

在模型中设置：
```php
protected bool $autoNull2EmptyString = false;
```

### 模块路由未加载

检查 `config/xditn.php` 中 `module.autoload` 是否为 `true`。

## PR 提交规范

| Type | 说明 |
|------|------|
| `feat` | 新功能 |
| `fix` | 修复 bug |
| `docs` | 文档注释 |
| `style` | 代码格式 |
| `refactor` | 重构优化 |
| `perf` | 性能优化 |
| `test` | 增加测试 |
| `chore` | 构建工具 |

示例：`fix(Pay): 修复充值金额计算错误`

## 许可证

[MIT License](LICENSE.md)
