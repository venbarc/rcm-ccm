<?php

namespace App\Models\Concerns;

use App\Support\AccountContext;

trait UsesAccountScopedTable
{
    public function getTable()
    {
        $defaultTable = (string) ($this->table ?: parent::getTable());

        return AccountContext::scopedTable($defaultTable);
    }
}
