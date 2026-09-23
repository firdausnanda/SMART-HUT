import test from 'node:test';
import assert from 'node:assert/strict';
import { availableUnits, selectedUnit, compareProduction, topCommodities, totalForUnit } from '../../resources/js/Pages/Public/Components/nonWoodUtils.js';

test('unit selection uses all years, defaults to kg and survives refreshes', () => {
  const groups = availableUnits([{ unit: 'batang' }, { unit: 'kg' }, { unit: 'liter' }, { unit: 'kg' }]);
  assert.deepEqual(groups.map(item => item.unit), ['kg', 'liter', 'batang']);
  assert.equal(selectedUnit(groups, 'batang').unit, 'batang');
  assert.equal(selectedUnit(groups, 'ton').unit, 'kg');
  assert.equal(selectedUnit(groups.slice(1), 'kg').unit, 'liter');
  assert.equal(selectedUnit([], 'kg'), null);
});

test('growth distinguishes missing records, zero baselines and true declines', () => {
  assert.equal(compareProduction(null, 10).growth, null);
  assert.equal(compareProduction(10, null).growth, null);
  assert.equal(compareProduction(10, 0).growth, null);
  assert.equal(compareProduction(0, 0).growth, null);
  assert.equal(compareProduction(0, 10).growth, -100);
  assert.equal(compareProduction(15, 10).growth, 50);
});

test('top commodities only includes five from the selected unit', () => {
  const group = { unit: 'kg', commodities: Array.from({ length: 7 }, (_, id) => ({ id, total: 10 - id })) };
  assert.deepEqual(topCommodities(group).map(item => item.id), [0, 1, 2, 3, 4]);
  assert.deepEqual(topCommodities(null), []);
});

test('yearly chart keeps gaps instead of inventing zero production', () => {
  const years = [undefined, { bukan_kayu_by_unit: [{ unit: 'kg', total: 0 }] }, { bukan_kayu_by_unit: [{ unit: 'liter', total: 20 }, { unit: 'kg', total: 30 }] }];
  assert.deepEqual(years.map(year => totalForUnit(year, 'kg')), [null, 0, 30]);
  assert.deepEqual(years.map(year => totalForUnit(year, 'liter')), [null, null, 20]);
});
