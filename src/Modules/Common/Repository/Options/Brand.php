<?php

namespace Modules\Common\Repository\Options;

use Illuminate\Support\Collection;
use Modules\Shop\Models\ProductBrand;

class Brand implements OptionInterface
{
    public function get(): Collection
    {

        return ProductBrand::all(['id as value', 'name as label']);
    }
}
