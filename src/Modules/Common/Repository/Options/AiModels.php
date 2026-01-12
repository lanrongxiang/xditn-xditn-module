<?php

namespace Modules\Common\Repository\Options;

use Illuminate\Support\Collection;
use Modules\Ai\Models\AiProviders;
use XditnModule\Enums\Status;

class AiModels extends Option implements OptionInterface
{
    public function get(): array|Collection
    {
        $providers = AiProviders::query()->with(['models' => function ($query) {
            $query->select(['id as value', 'name as label', 'provider_id'])
                ->where('status', Status::Enable->value);
        }])->get(['id', 'title as label'])->filter(function ($provider) {
            if ($provider->models->isEmpty()) {
                return false;
            }

            return true;
        })->toArray();

        foreach ($providers as &$provider) {
            $provider['options'] = $provider['models'];
            unset($provider['models']);
        }

        return $providers;
    }
}
