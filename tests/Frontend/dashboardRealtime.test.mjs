import test from 'node:test';
import assert from 'node:assert/strict';
import { affectsDashboard } from '../../Modules/Dashboard/resources/js/Pages/Public/Components/dashboardRealtime.js';

test('a yearly change refreshes only a matching year and CDK', () => {
  assert.equal(affectsDashboard({ year: 2026, cdkId: 2 }, [2026], 2), true);
  assert.equal(affectsDashboard({ year: 2025, cdkId: 2 }, [2026], 2), false);
  assert.equal(affectsDashboard({ year: 2026, cdkId: 1 }, [2026], 2), false);
});

test('province view accepts any CDK and yearless changes reach every year', () => {
  assert.equal(affectsDashboard({ year: 2024, cdkId: 9 }, [2026, 2025, 2024], null), true);
  assert.equal(affectsDashboard({ year: null, cdkId: 9 }, [2026], null), true);
  assert.equal(affectsDashboard({ year: null, cdkId: 1 }, [2026], 2), false);
});
