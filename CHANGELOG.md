# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- 统一事件接口 `Event` 和事件订阅者 `EventSubscriber`
- 查询过滤器 `Filter` 模式（StatusFilter, DateRangeFilter, SearchFilter, InFilter）
- API 版本控制中间件和助手类 `ApiVersion`
- 请求速率限制中间件 `RateLimitMiddleware`
- 响应压缩中间件 `CompressResponseMiddleware`
- 请求追踪中间件 `RequestTracingMiddleware`
- 健康检查控制器 `HealthController`
- 丰富的异常类型（ValidationException, AuthenticationException, AuthorizationException 等）
- 命令行进度条 Trait `CommandProgressTrait`
- 模型观察者生成命令 `xditn:module:make:observer`
- API 资源生成命令 `xditn:module:make:resource`
- 软删除数据清理命令 `xditn:module:purge-trashed`
- 慢查询日志监听器 `SlowQueryListener`
- Excel 导入导出基类 `BaseExport` / `BaseImport`
- 缓存装饰器 Trait `Cacheable`
- 枚举 Trait `EnumTrait`

### Changed
- 优化模型时间戳处理，使用 `unsignedInteger` 类型
- 统一使用 `XditnModuleController` 和 `XditnModuleModel` 基类
- 优化异常处理和响应格式

### Fixed
- 修复 `BaseOperate::getCreatedAtColumn()` 方法的条件判断错误
- 修复命令加载函数的格式问题
- 移除冗余的 TODO 注释

## [1.0.0] - 2024-XX-XX

### Added
- 初始版本发布
- 模块化架构支持
- CRUD 代码生成
- 权限控制系统
- API 文档生成（Scribe 集成）

