<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicDashboardTest extends TestCase
{
  public function test_public_dashboard_requires_authentication()
  {
    $this->get(route('public.dashboard'))->assertRedirect(route('login'));
  }
}
