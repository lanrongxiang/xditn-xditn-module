<?php

declare(strict_types=1);

namespace XditnModule\Support\Macros;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection as LaravelCollection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use XditnModule\Support\Excel\Csv;
use XditnModule\Support\Excel\Export;
use XditnModule\Support\Excel\XlsWriterExport;
use XditnModule\Support\Tree;

class Collection
{
    /**
     * boot.
     */
    public function boot(): void
    {
        $this->toOptions();

        $this->toTree();

        $this->export();

        $this->download();

        $this->downloadAsCsv();

        $this->lazyDownload();

        $this->lazyExport();
    }

    /**
     * collection to tree.
     */
    public function toTree(): void
    {
        LaravelCollection::macro(__FUNCTION__, function (int $pid = 0, string $pidField = 'parent_id', string $child = 'children', $id = 'id') {
            return LaravelCollection::make(Tree::done($this->all(), $pid, $pidField, $child, $id));
        });
    }

    /**
     * toOptions.
     */
    public function toOptions(): void
    {
        LaravelCollection::macro(__FUNCTION__, function () {
            return $this->transform(function ($item, $key) use (&$options) {
                if ($item instanceof Arrayable) {
                    $item = $item->toArray();
                }

                if (is_array($item)) {
                    $item = array_values($item);

                    return [
                        'value' => $item[0],
                        'label' => $item[1],
                    ];
                } else {
                    return [
                        'value' => $key,
                        'label' => $item,
                    ];
                }
            })->values();
        });
    }

    public function export(): void
    {
        LaravelCollection::macro(__FUNCTION__, function (array $header) {
            $items = $this->toArray();

            $export = new class($items, $header) extends Export {
                protected array $items;

                public function __construct(array $items, array $header)
                {
                    $this->items = $items;

                    $this->header = $header;
                }

                public function array(): array
                {
                    // TODO: Implement array() method.
                    return $this->items;
                }
            };

            return $export->export();
        });
    }

    public function download(): void
    {
        LaravelCollection::macro(__FUNCTION__, function (array $header, array $fields = []) {
            $items = $this->toArray();
            // 自定字段重新组装数据
            $newItems = [];
            if (!empty($fields)) {
                foreach ($items as $item) {
                    $newItem = [];
                    foreach ($fields as $field) {
                        $newItem[] = $item[$field] ?? null;
                    }
                    $newItems[] = $newItem;
                }
            }
            if (count($newItems)) {
                $items = $newItems;
            }

            $export = new class($items, $header) extends Export {
                protected array $items;

                public function __construct(array $items, array $header)
                {
                    $this->items = $items;

                    $this->header = $header;
                }

                public function array(): array
                {
                    // TODO: Implement array() method.
                    return $this->items;
                }
            };

            return $export->download();
        });
    }

    /**
     * 下载 csv.
     */
    public function downloadAsCsv(): void
    {
        LazyCollection::macro(__FUNCTION__, function (array $header, ?string $filename = null) {
            $csv = new Csv();

            $filename = $filename ?: Str::random(10).'.csv';

            return $csv->header($header)->download($filename, $this);
        });
    }

    /**
     * @return void
     */
    public function lazyDownload(): void
    {
        LazyCollection::macro('download', function (array $header, array $fields = []) {
            $export = new class($header) extends XlsWriterExport {
                protected array $items;

                public function __construct(array $header)
                {
                    parent::__construct();

                    $this->header = $header;
                }

                public function array(): array
                {
                    // TODO: Implement array() method.
                    return [];
                }
            };

            $export->setExcelObject($export->getExportObject())->header();
            // 是否是嵌套数组
            $isNestedArray = array_is_list($this->first()->toArray());
            $this->each(function ($item) use (&$export, $isNestedArray) {
                $data = $item->toArray();
                if (!$isNestedArray) {
                    $data = [$data];
                }
                $export->data($data);
            });

            return $export->responseDownload($export->output());
        });
    }

    /**
     * @return void
     */
    public function lazyExport(): void
    {
        LaravelCollection::macro('export', function (array $header, array $fields = [], bool $useXlsWriter = false) {
            $items = $this->toArray();
            // 自定字段重新组装数据
            $newItems = [];
            if (!empty($fields)) {
                foreach ($items as $item) {
                    $newItem = [];
                    foreach ($fields as $field) {
                        $newItem[] = $item[$field] ?? null;
                    }
                    $newItems[] = $newItem;
                }
            }
            if (count($newItems)) {
                $items = $newItems;
            }

            if ($useXlsWriter) {
                $export = new class($items, $header) extends XlsWriterExport {
                    protected array $items;

                    public function __construct(array $items, array $header)
                    {
                        parent::__construct();

                        $this->items = $items;

                        $this->header = $header;
                    }

                    public function array(): array
                    {
                        // TODO: Implement array() method.
                        return $this->items;
                    }
                };
            } else {
                $export = new class($items, $header) extends Export {
                    protected array $items;

                    public function __construct(array $items, array $header)
                    {
                        $this->items = $items;

                        $this->header = $header;
                    }

                    public function array(): array
                    {
                        // TODO: Implement array() method.
                        return $this->items;
                    }
                };
            }

            return $export->export();
        });
    }
}
