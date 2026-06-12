<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;

class RawRowsImport implements ToArray
{
    private array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }

    public function rows(): array
    {
        return $this->rows;
    }
}
