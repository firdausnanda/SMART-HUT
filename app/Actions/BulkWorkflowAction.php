<?php
namespace App\Actions;

use App\Contracts\Workflowable;
use App\Enums\WorkflowAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkWorkflowAction
{
  public function execute(
    Workflowable|string $model,
    WorkflowAction $action,
    array $ids,
    User $user,
    array $extraData = []
  ): int {
    $modelClass = is_string($model) ? $model : get_class($model);
    $workflow = $modelClass::workflowMap()[$action->value] ?? [];
    $totalCount = 0;
    $isAdmin = $user->hasRole('admin');

    foreach ($workflow as $role => $rule) {
      if (!$user->hasRole($role) && !$isAdmin) {
        continue;
      }

      $query = $modelClass::baseQuery($ids);

      if (isset($rule['from'])) {
        if (is_array($rule['from'])) {
          $query->whereIn('status', $rule['from']);
        } else {
          $query->where('status', $rule['from']);
        }
      }

      if (!empty($rule['delete'])) {
        $totalCount += $query->delete();
        continue;
      }

      $update = [
        'status' => $rule['to'] ?? 'rejected',
      ];

      if (!empty($rule['timestamp'])) {
        $update[$rule['timestamp']] = now();
      }

      $totalCount += $query->update(array_merge($update, $extraData));
    }

    return $totalCount;
  }
}