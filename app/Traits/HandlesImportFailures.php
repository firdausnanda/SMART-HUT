<?php

namespace App\Traits;

use Maatwebsite\Excel\Validators\Failure;

trait HandlesImportFailures
{
  /**
   * Map import failures to a serializable array.
   *
   * @param \Illuminate\Support\Collection|array $failures
   * @return array
   */
  protected function mapImportFailures($failures): array
  {
    $errors = [];
    foreach ($failures as $failure) {
      if ($failure instanceof Failure) {
        $errors[] = [
          'row' => $failure->row(),
          'attribute' => $failure->attribute(),
          'errors' => $failure->errors(),
          'values' => $failure->values(),
        ];
      }
    }
    return $errors;
  }
}
