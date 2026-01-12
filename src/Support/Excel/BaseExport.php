<?php

declare(strict_types=1);

namespace XditnModule\Support\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel 导出基类.
 *
 * 提供通用的 Excel 导出功能，子类只需实现 getCollection()、getHeadings() 和 mapRow() 方法。
 *
 * 使用示例：
 * ```php
 * class UserExport extends BaseExport
 * {
 *     public function __construct(
 *         protected array $filters = []
 *     ) {}
 *
 *     protected function getCollection(): Collection
 *     {
 *         return User::query()->filter($this->filters)->get();
 *     }
 *
 *     protected function getHeadings(): array
 *     {
 *         return ['ID', '用户名', '邮箱', '创建时间'];
 *     }
 *
 *     protected function mapRow($row): array
 *     {
 *         return [
 *             $row->id,
 *             $row->username,
 *             $row->email,
 *             $row->created_at->format('Y-m-d H:i:s'),
 *         ];
 *     }
 * }
 *
 * // 使用
 * return Excel::download(new UserExport(['status' => 1]), 'users.xlsx');
 * ```
 */
abstract class BaseExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    /**
     * 获取要导出的数据集合.
     */
    abstract protected function getCollection(): Collection;

    /**
     * 获取表头.
     *
     * @return array<int, string>
     */
    abstract protected function getHeadings(): array;

    /**
     * 映射每行数据.
     *
     * @param mixed $row
     *
     * @return array<int, mixed>
     */
    abstract protected function mapRow($row): array;

    /**
     * @return Collection<int, mixed>
     */
    public function collection(): Collection
    {
        return $this->getCollection();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->getHeadings();
    }

    /**
     * @param mixed $row
     *
     * @return array<int, mixed>
     */
    public function map($row): array
    {
        return $this->mapRow($row);
    }

    /**
     * 表头样式.
     *
     * @return array<string, array<string, mixed>>
     */
    public function styles(Worksheet $sheet): array
    {
        return [
            // 第一行（表头）加粗
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * 获取文件名.
     */
    public function fileName(): string
    {
        return class_basename(static::class).'_'.date('YmdHis').'.xlsx';
    }
}
