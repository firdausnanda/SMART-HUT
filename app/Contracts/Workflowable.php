<?php

namespace App\Contracts;

use App\Enums\WorkflowAction;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

interface Workflowable
{
  /**
   * Mapping workflow:
   * [
   * 'approve' => [
   * 'kasi' => [
   * 'from' => 'waiting_kasi',
   * 'to' => 'waiting_cdk',
   * 'timestamp' => 'approved_by_kasi_at'
   * ],
   * ],
   * 'reject' => [
   * 'kasi' => ['from' => 'waiting_kasi']
   * ]
   * ]
   */
  public static function workflowMap(): array;

  public static function baseQuery(array $ids): Builder;
}