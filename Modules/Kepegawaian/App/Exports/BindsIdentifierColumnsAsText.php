<?php

namespace Modules\Kepegawaian\App\Exports;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

trait BindsIdentifierColumnsAsText
{
    protected function identifierColumns(): array
    {
        return ['B'];
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if ($cell->getRow() > 1 && in_array($cell->getColumn(), $this->identifierColumns(), true) && $value !== null) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return array_fill_keys($this->identifierColumns(), NumberFormat::FORMAT_TEXT);
    }
}
