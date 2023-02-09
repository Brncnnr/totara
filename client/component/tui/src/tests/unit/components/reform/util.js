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

export const ReformScopeProvider = {
  props: ['scope'],
  provide() {
    return {
      reformScope: this.scope || {},
    };
  },
  render() {
    return this.$scopedSlots.default();
  },
};

export const ReformFieldContextProvider = {
  props: ['fieldContext'],
  provide() {
    return {
      reformFieldContext: this.fieldContext || {},
    };
  },
  render() {
    return this.$scopedSlots.default();
  },
};

export const ReformScopeReceiver = {
  inject: ['reformScope'],
  render: () => null,
};

export const pathMethods = [
  'getValue',
  'getError',
  'getTouched',
  'update',
  'blur',
  'getInputName',
  '$_internalUpdateSliceState',
];

export function createMockScope() {
  const scope = {};
  pathMethods.forEach(name => {
    scope[name] = jest.fn();
  });
  scope.register = jest.fn();
  scope.unregister = jest.fn();
  scope.updateRegistration = jest.fn();
  return scope;
}
