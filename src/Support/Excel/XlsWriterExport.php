<?php

declare(strict_types=1);

namespace XditnModule\Support\Excel;

use Closure;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Vtiful\Kernel\Excel;
use XditnModule\Events\Excel\Export as ExportEvent;
use XditnModule\Exceptions\FailedException;

/**
 * xlswriter 导出适配器类
 * 兼容 Laravel Excel 接口，同时使用 xlswriter 引擎.
 */
abstract class XlsWriterExport
{
    /**
     * 数据.
     */
    protected array $data;

    /**
     * 查询参数.
     */
    protected array $params = [];

    /**
     * Excel 表头.
     */
    protected array $header = [];

    /**
     * 文件名.
     */
    protected ?string $filename = null;

    /**
     * 是否启用无限内存模式.
     */
    protected bool $unlimitedMemory = false;

    /**
     * 是否启用固定内存模式.
     */
    protected bool $constMemory = true;

    /**
     * 保存目录.
     *
     * @var string
     */
    protected string $path = '';

    /**
     * xlswriter 配置.
     */
    protected array $xlswriterConfig = [];

    /**
     * 设置回调自定义处理.
     *
     * @var Closure|null
     */
    protected ?Closure $setCallback = null;

    /**
     * 当前 excel 对象
     *
     * @var Excel|null
     */
    protected ?Excel $excelObject = null;

    /**
     * __construct.
     */
    public function __construct()
    {
        if (!extension_loaded('xlswriter')) {
            throw new FailedException('xlswriter 扩展未安装');
        }
    }

    /**
     * 使用 xlswriter 导出.
     */
    public function export(?string $disk = null): string
    {
        try {
            // 设置内存限制
            if ($this->unlimitedMemory) {
                ini_set('memory_limit', -1);
            }

            // 获取写入文件类型
            if (is_null($this->excelObject)) {
                $writeType = $this->getWriteType();

                $this->excelObject = $this->getExportObject($this->getFilename($writeType), $disk);
            }

            // 设置头信息
            $this->header();
            // 如果实现了 format 方法
            $this->setFormats();

            // 设置 data
            $data = $this->array();
            if ($data instanceof LazyCollection) {
                $isNestedArray = array_is_list($data->first()->toArray());

                foreach ($data as $item) {
                    $arr = $item->toArray();
                    if (!$isNestedArray) {
                        $arr = [array_values($arr)];
                    }

                    $this->data($arr);
                }
            } else {
                $this->data($data);
            }

            // 设置编辑时密码保护
            $this->setPassword();

            // 支持回调, 用户自定义处理
            if (!is_null($this->setCallback)) {
                $excelObjectHandle = $this->excelObject->getHandle();
                $this->excelObject = call_user_func_array($this->setCallback, [$this->excelObject, $excelObjectHandle]);
            }

            // 输出
            $file = $this->output();

            $this->excelObject = null;
            // 触发导出事件
            Event::dispatch(ExportEvent::class);

            return $file;
        } catch (Throwable $e) {
            throw new FailedException('导出失败: '.$e->getMessage());
        }
    }

    /**
     * @return $this
     */
    public function header(): static
    {
        $this->excelObject = $this->excelObject->header($this->header);

        return $this;
    }

    /**
     * @return $this
     */
    public function setFormats(): static
    {
        $formats = $this->formats();
        foreach ($formats as $format) {
            $this->excelObject = $this->excelObject->setRow(...$format);
        }

        return $this;
    }

    /**
     * 设置密码
     *
     * @return $this
     */
    public function setPassword(): static
    {
        if ($password = $this->password()) {
            $this->excelObject = $this->excelObject->protection($password);
        }

        return $this;
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function data(array $data): static
    {
        $this->excelObject = $this->excelObject->data($data);

        return $this;
    }

    /**
     * @return string
     */
    public function output(): string
    {
        return $this->excelObject->output();
    }

    /**
     * @param Excel $excelObject
     *
     * @return $this
     */
    public function setExcelObject(Excel $excelObject): static
    {
        $this->excelObject = $excelObject;

        return $this;
    }

    /**
     * 导出得数据.
     *
     * @return array|LazyCollection
     */
    abstract public function array(): array|LazyCollection;

    /**
     * @param string|null $filename
     * @param string|null $disk
     *
     * @return Excel
     */
    public function getExportObject(?string $filename = null, ?string $disk = null): Excel
    {
        $config = [
            'path' => $this->getExportPath($disk),
        ];

        if (!empty($this->xlswriterConfig)) {
            $config = array_merge($config, $this->xlswriterConfig);
        }

        $filename = $filename ?: $this->getFilename($this->getWriteType());

        $excel = new Excel($config);

        return $this->constMemory ? $excel->constMemory($filename) : $excel->fileName($filename);
    }

    /**
     * 使用 xlswriter 下载.
     */
    public function download(): BinaryFileResponse|StreamedResponse
    {
        $tempFile = $this->export();

        return $this->responseDownload($tempFile);
    }

    /**
     * @param string $tempFile
     *
     * @return BinaryFileResponse
     */
    public function responseDownload(string $tempFile): BinaryFileResponse
    {
        $filename = pathinfo($tempFile, PATHINFO_BASENAME);

        return response()->download($tempFile, $filename, [
            'filename' => $filename,
            'write_type' => $this->getWriteType(),
        ])->deleteFileAfterSend();
    }

    /**
     * 设置 xlswriter 格式.
     */
    public function setDefineCallback(Closure $callback): static
    {
        $this->setCallback = $callback;

        return $this;
    }

    /**
     * @return string|null
     */
    public function password(): ?string
    {
        return null;
    }

    /**
     * @return array
     */
    public function formats(): array
    {
        return [];
    }

    /**
     * 禁用固定内存.
     *
     * @return $this
     */
    public function disableConstMemory(): static
    {
        $this->constMemory = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function setParams(array $params): static
    {
        $this->params = $params;

        return $this;
    }

    /**
     * 获取查询参数.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * 获取写入类型.
     */
    protected function getWriteType(): string
    {
        return 'xlsx';
    }

    /**
     * 获取导出路径.
     */
    public function getExportPath(?string $disk = null): string
    {
        if ($this->path) {
            return $this->path;
        }

        $path = sprintf(config('xditn.excel.export_path', 'excel/export').'/%s', date('Ymd'));

        $path = Storage::disk($disk ?: config('filesystems.default'))->path($path);

        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            throw new FailedException(sprintf('Directory "%s" was not created', $path));
        }

        return $path;
    }

    /**
     * 设置目录.
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * 设置文件名.
     *
     * @return $this
     */
    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * 获取文件名.
     */
    public function getFilename(?string $type = null): string
    {
        if (!$this->filename) {
            $extension = $type ?: $this->getWriteType();

            return Str::random().'.'.$extension;
        }

        return $this->filename;
    }

    /**
     * 获取 Excel 表头.
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * 设置 Excel 表头.
     *
     * @return $this
     */
    public function setHeader(array $header): static
    {
        $this->header = $header;

        return $this;
    }

    public function headings(): array
    {
        $headings = [];

        foreach ($this->header as $k => $item) {
            if (is_string($k) && is_numeric($item)) {
                $headings[] = $k;
            }

            if (is_string($item)) {
                $headings[] = $item;
            }
        }

        return $headings;
    }

    /**
     * 设置 xlswriter 配置.
     *
     * @return $this
     */
    public function setConfig(array $config): static
    {
        $this->xlswriterConfig = $config;

        return $this;
    }

    /**
     * 启用无限内存模式.
     *
     * @return $this
     */
    public function unlimitedMemory(): static
    {
        $this->unlimitedMemory = true;

        return $this;
    }

    /**
     * 异步任务
     */
    public function run(array $params): mixed
    {
        return $this->setParams($params)->export();
    }
}
