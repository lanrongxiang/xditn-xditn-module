<?php

namespace XditnModule\Listeners;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use XditnModule\Enums\Code;
use XditnModule\Support\ResponseBuilder;

class RequestHandledListener
{
    /**
     * Handle the event.
     */
    public function handle(RequestHandled $event): void
    {
        if (isRequestFromDashboard()) {
            $response = $event->response;

            // 自定义响应内容
            if ($response instanceof ResponseBuilder) {
                $event->response = $response();
            } else {
                // 标准响应
                if ($response instanceof JsonResponse) {
                    $exception = $response->exception;

                    if ($response->getStatusCode() == SymfonyResponse::HTTP_OK && !$exception) {
                        $response->setData($this->formatData($response->getData()));
                    }
                }
            }
        }
    }

    /**
     * 格式化响应数据.
     */
    protected function formatData(mixed $data): array
    {
        return array_merge(
            [
                'code' => Code::SUCCESS->value(),
                'message' => Code::SUCCESS->message(),
            ],
            format_response_data($data)
        );
    }
}
