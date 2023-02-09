/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

import Vue from 'vue';
import { processComponentOverride } from 'tui/internal/overrides';
import TotaraModuleStore from 'tui/internal/TotaraModuleStore';

jest.mock('tui/internal/TotaraModuleStore');

const modules = new TotaraModuleStore();

const mockComp = name => {
  return {
    $_name: name,
    render: h => h('div', name),
  };
};

modules.require.mockImplementation(name => ({
  default: mockComp(name),
  __esModule: true,
}));
modules.import.mockImplementation(async name => modules.require(name));
modules.default.mockImplementation(m => (m.__esModule ? m.default : m));

describe('processComponentOverride', () => {
  it('prevents inheriting when inheritable = false', () => {
    const comp = {};
    processComponentOverride(modules, comp, { inheritable: false });
    expect(comp.extends).toEqual(undefined);
  });

  it('skips Vue.extend() parents', () => {
    const comp = {};
    processComponentOverride(modules, comp, Vue.extend({}));
    expect(comp.extends).toEqual(undefined);
  });

  it('requires component to have __hasBlocks', () => {
    const comp = {};
    expect(() => processComponentOverride(modules, comp, {})).toThrow(
      'components must be processed by tui-vue-loader'
    );
  });

  it('skips when component has its own script block', () => {
    const comp = { __hasBlocks: { script: true } };
    processComponentOverride(modules, comp, {});
    expect(comp.extends).toEqual(undefined);
  });

  it('allows opt-in extending of script block', () => {
    const comp = { __hasBlocks: { script: true }, __extends: true };
    const parent = {};
    processComponentOverride(modules, comp, parent);
    expect(comp.extends).toEqual(parent);
  });

  it('sets extends when everything is okay', () => {
    const comp = { __hasBlocks: {} };
    const parent = { foo: 5 };
    processComponentOverride(modules, comp, parent);
    expect(comp.extends).toBe(parent);
  });

  it('does not set extends if it is already set', () => {
    const oldParent = {};
    const comp = { __hasBlocks: {}, extends: oldParent };
    const parent = { foo: 5 };
    processComponentOverride(modules, comp, parent);
    expect(comp.extends).toBe(oldParent);
  });

  it('resolves parent as a module if passed a string', () => {
    modules.isEvaluating.mockReturnValueOnce(false);
    processComponentOverride(modules, { __hasBlocks: {} }, 'foo');
    expect(modules.require).toHaveBeenCalledWith('foo');
  });

  it('calls modules._requirePrevious if evaluating the same module', () => {
    modules.isEvaluating.mockImplementationOnce(x => x == 'bar');
    modules._requirePrevious.mockReturnValueOnce({});
    processComponentOverride(modules, { __hasBlocks: {} }, 'bar');
    expect(modules._requirePrevious).toHaveBeenCalledWith('bar');

    modules.isEvaluating.mockImplementationOnce(x => x == 'baz');
    modules._requirePrevious.mockReturnValueOnce(undefined);
    expect(() =>
      processComponentOverride(modules, { __hasBlocks: {} }, 'baz')
    ).toThrow('does not exist');
  });
});
