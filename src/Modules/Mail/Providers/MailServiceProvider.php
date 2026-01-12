<?php

namespace Modules\Mail\Providers;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Modules\Mail\Console\SendMailTaskCommand;
use Modules\Mail\Listeners\UniversalMailTrackingListener;
use Modules\Mail\Support\MailTrackingService;
use XditnModule\Providers\XditnModuleModuleServiceProvider;

class MailServiceProvider extends XditnModuleModuleServiceProvider
{
    protected array $commands = [
        SendMailTaskCommand::class,
    ];

    /**
     * route path.
     *
     * @return string
     */
    public function moduleName(): string
    {
        return 'Mail';
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        try {
            parent::register();

            // 注册邮件追踪服务
            $this->app->singleton(MailTrackingService::class);
            $this->app->alias(MailTrackingService::class, 'mail.tracking');
        } catch (\Throwable $e) {

        }
    }

    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        // 注册邮件事件监听器
        $this->registerMailEventListeners();

        // 注册Blade指令
        $this->registerBladeDirectives();
    }

    /**
     * 注册邮件事件监听器.
     */
    protected function registerMailEventListeners(): void
    {
        Event::listen(MessageSending::class, function ($event) {
            $listener = new UniversalMailTrackingListener(app(MailTrackingService::class));
            $listener->handle($event);
        });
    }

    /**
     * 注册Blade指令.
     */
    protected function registerBladeDirectives(): void
    {
        // 追踪像素指令
        Blade::directive('trackingPixel', function ($expression) {
            return "<?php echo app('mail.tracking')->getTrackingPixel({$expression}); ?>";
        });

        // 追踪链接指令
        Blade::directive('trackingLink', function ($expression) {
            $parts = explode(',', str_replace(['(', ')', "'", '"'], '', $expression));
            $url = trim($parts[0] ?? '');
            $trackingId = trim($parts[1] ?? 'null');

            return "<?php echo app('mail.tracking')->getTrackingLink('{$url}', {$trackingId}); ?>";
        });
    }
}
