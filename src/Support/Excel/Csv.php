<?php

namespace XditnModule\Support\Excel;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class Csv.
 */
class Csv
{
    protected array $header = [];

    /**
     * csv 头信息.
     *
     * @return $this
     */
    public function header(array $header): static
    {
        $this->header = $header;

        return $this;
    }

    /**
     * 下载.
     */
    public function download(string $filename, array|LazyCollection $data): StreamedResponse
    {
        $responseHeader = [
            'Content-Type' => 'text/csv;charset=utf-8',
            // 设置头信息输出编码
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
            'filename' => $filename,
        ];

        return Response::stream(function () use ($data) {
            // 清除之前的所有输出缓冲
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // 设置内部编码
            mb_internal_encoding('UTF-8');

            $output = fopen('php://output', 'w');

            $this->useBom($output);

            fputcsv($output, $this->header);
            if ($data instanceof LazyCollection) {
                foreach ($data as $item) {
                    fputcsv($output, $item->toArray());
                }
            } else {
                fputcsv($output, $data);
            }
            fclose($output);
        }, 200, $responseHeader);

    }

    /**
     * 使用 boom.
     */
    protected function useBom($output): void
    {
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    }
}
