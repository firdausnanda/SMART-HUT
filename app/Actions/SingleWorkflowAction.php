<?php

namespace App\Actions;

use App\Contracts\Workflowable;
use App\Enums\WorkflowAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SingleWorkflowAction
{
  public function execute(
    Workflowable|Model $model,
    WorkflowAction $action,
    User $user,
    array $extraData = []
  ): bool {

    $workflow = $model::workflowMap()[$action->value] ?? [];
    $isAdmin = $user->hasRole('admin');

    foreach ($workflow as $role => $rule) {
      if (!$user->hasRole($role) && !$isAdmin) {
        continue;
      }

      if (isset($rule['from'])) {
        $from = (array) $rule['from'];
        if (!in_array($model->status, $from)) {
          continue;
        }
      }

      if (!empty($rule['delete'])) {
        return $model->delete();
      }

      $model->status = $rule['to'] ?? 'rejected';

      if (!empty($rule['timestamp'])) {
        $model->{$rule['timestamp']} = now();
      }

      foreach ($extraData as $key => $value) {
        $model->{$key} = $value;
      }

      return $model->save();
    }

    return false;
  }
}
