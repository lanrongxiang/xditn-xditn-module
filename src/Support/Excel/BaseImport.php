<?php

declare(strict_types=1);

namespace XditnModule\Support\Excel;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Throwable;

/**
 * Excel 导入基类.
 *
 * 提供通用的 Excel 导入功能，支持验证、错误处理和批量处理。
 *
 * 使用示例：
 * ```php
 * class UserImport extends BaseImport
 * {
 *     protected function getRules(): array
 *     {
 *         return [
 *             'username' => 'required|string|max:50',
 *             'email' => 'required|email|unique:users,email',
 *             'phone' => 'nullable|string|max:20',
 *         ];
 *     }
 *
 *     protected function processRows(Collection $rows): void
 *     {
 *         foreach ($rows as $row) {
 *             User::create([
 *                 'username' => $row['username'],
 *                 'email' => $row['email'],
 *                 'phone' => $row['phone'] ?? null,
 *             ]);
 *         }
 *     }
 *
 *     // 可选：自定义错误消息
 *     public function customValidationMessages(): array
 *     {
 *         return [
 *             'email.unique' => '邮箱已存在',
 *         ];
 *     }
 * }
 *
 * // 使用
 * $import = new UserImport();
 * Excel::import($import, $request->file('file'));
 *
 * // 获取错误
 * $errors = $import->getErrors();
 * $failures = $import->getFailures();
 * ```
 */
abstract class BaseImport implements SkipsEmptyRows, SkipsOnError, SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use Importable;

    /**
     * 导入错误.
     *
     * @var array<int, Throwable>
     */
    protected array $errors = [];

    /**
     * 验证失败记录.
     *
     * @var array<int, Failure>
     */
    protected array $failures = [];

    /**
     * 成功导入的行数.
     */
    protected int $successCount = 0;

    /**
     * 获取验证规则.
     *
     * @return array<string, mixed>
     */
    abstract protected function getRules(): array;

    /**
     * 处理导入的数据.
     *
     * @param Collection<int, array<string, mixed>> $rows 验证通过的数据行
     */
    abstract protected function processRows(Collection $rows): void;

    /**
     * 处理导入的数据集合.
     *
     * @param Collection<int, Collection<string, mixed>> $collection
     */
    public function collection(Collection $collection): void
    {
        // 转换为数组集合
        $rows = $collection->map(fn ($row) => $row->toArray());

        // 处理数据
        $this->processRows($rows);

        $this->successCount = $rows->count();
    }

    /**
     * 验证规则.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->getRules();
    }

    /**
     * 自定义验证消息.
     *
     * @return array<string, string>
     */
    public function customValidationMessages(): array
    {
        return [];
    }

    /**
     * 自定义验证属性名称.
     *
     * @return array<string, string>
     */
    public function customValidationAttributes(): array
    {
        return [];
    }

    /**
     * 处理错误.
     */
    public function onError(Throwable $e): void
    {
        $this->errors[] = $e;
    }

    /**
     * 处理验证失败.
     *
     * @param array<int, Failure> $failures
     */
    public function onFailure(Failure ...$failures): void
    {
        $this->failures = array_merge($this->failures, $failures);
    }

    /**
     * 获取错误列表.
     *
     * @return array<int, Throwable>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * 获取验证失败列表.
     *
     * @return array<int, Failure>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * 获取成功导入的行数.
     */
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    /**
     * 获取失败的行数.
     */
    public function getFailureCount(): int
    {
        return count($this->failures);
    }

    /**
     * 获取错误数量.
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * 是否有错误.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors) || !empty($this->failures);
    }

    /**
     * 获取格式化的失败信息.
     *
     * @return array<int, array{row: int, attribute: string, errors: array<int, string>, values: array<string, mixed>}>
     */
    public function getFormattedFailures(): array
    {
        return array_map(fn (Failure $failure) => [
            'row' => $failure->row(),
            'attribute' => $failure->attribute(),
            'errors' => $failure->errors(),
            'values' => $failure->values(),
        ], $this->failures);
    }

    /**
     * 批量处理数据（分块处理大文件）.
     *
     * @param Collection<int, array<string, mixed>> $rows
     * @param int $chunkSize 每批处理的行数
     */
    protected function processInChunks(Collection $rows, int $chunkSize = 1000): void
    {
        $rows->chunk($chunkSize)->each(function ($chunk) {
            $this->processBatch($chunk);
        });
    }

    /**
     * 处理一批数据（子类可重写）.
     *
     * @param Collection<int, array<string, mixed>> $batch
     */
    protected function processBatch(Collection $batch): void
    {
        // 默认逐行处理
        foreach ($batch as $row) {
            $this->processRow($row);
        }
    }

    /**
     * 处理单行数据（子类可重写）.
     *
     * @param array<string, mixed> $row
     */
    protected function processRow(array $row): void
    {
        // 子类实现
    }
}
