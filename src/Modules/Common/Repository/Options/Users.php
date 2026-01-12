<?php

declare(strict_types=1);

namespace Modules\Common\Repository\Options;

use Modules\Member\Models\Members;

/**
 * 用户筛选选项.
 */
class Users implements OptionInterface
{
    public function get(): array
    {
        $request = request();
        $filter = $request->get('filter', '');

        $query = Members::query()
            ->select(['id', 'username'])
            ->when($filter, function ($query) use ($filter) {
                $query->where(function ($q) use ($filter) {
                    $q->where('username', 'like', "%{$filter}%")
                        ->orWhere('email', 'like', "%{$filter}%")
                        ->orWhere('mobile', 'like', "%{$filter}%");
                });
            })
            ->orderBy('id')
            ->limit(100);

        $users = $query->get();

        return $users->map(function ($user) {
            return [
                'value' => $user->id,
                'label' => $user->username,
            ];
        })->toArray();
    }
}
