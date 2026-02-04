<?php

namespace App\Enums;

enum WorkflowAction: string
{
  case APPROVE = 'approve';
  case REJECT = 'reject';
  case SUBMIT = 'submit';
  case DELETE = 'delete';
}