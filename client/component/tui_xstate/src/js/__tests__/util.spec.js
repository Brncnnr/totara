/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module tui_xstate
 * @jest-environment jsdom
 */
import { produce } from 'tui/immutable';
import { createSelector } from '../util';
import { showError } from 'tui/errors';
import { createMachine } from '../xstate';
import vueXstatePlugin from '../vue_xstate_plugin';
import { makeState, options } from './fixtures/parent_test_machine';
import { flushMicrotasks } from '../../../../tui/src/tests/unit/util';

let Vue;
beforeEach(() => {
  jest.resetModules();
  Vue = require('vue');
  Vue.config.errorHandler = jest.fn();
});

jest.mock('tui/errors', function() {
  return {
    showError: jest.fn(),
  };
});

describe('createSelector', () => {
  it('bypasses the cache when passed a draft object', () => {
    const context = { foo: { bar: 3 } };
    const selectorCb = jest.fn(ctx => ctx.foo);
    const combinerCb = jest.fn(foo => foo.bar);
    const selector = createSelector(selectorCb, combinerCb);
    expect(selectorCb).not.toHaveBeenCalled();
    expect(combinerCb).not.toHaveBeenCalled();

    selector(context);
    expect(selectorCb).toHaveBeenCalledTimes(1);
    expect(combinerCb).toHaveBeenCalledTimes(1);
    selector(context);
    expect(selectorCb).toHaveBeenCalledTimes(1);
    expect(combinerCb).toHaveBeenCalledTimes(1);

    produce(context, draft => selector(draft));
    expect(selectorCb).toHaveBeenCalledTimes(2);
    expect(combinerCb).toHaveBeenCalledTimes(2);

    selector(context);
    expect(selectorCb).toHaveBeenCalledTimes(2);
    expect(combinerCb).toHaveBeenCalledTimes(2);

    selector({ foo: {} });
    expect(selectorCb).toHaveBeenCalledTimes(3);
    expect(combinerCb).toHaveBeenCalledTimes(3);
  });
});

describe('showError', () => {
  it('auto-handles an unhandled "invoke.onError"  by calling tui/errors showError', async () => {
    const Component = {
      data() {
        return { test: true };
      },
      xState: {
        machine() {
          const state = makeState({
            id: 'parent-with-child-error',
            context: { willError: true },
            onError: null,
          });

          state.states.errored = {
            id: 'errored',
            meta: {
              defaultErrorTarget: true,
            },
          };

          return createMachine(state, options);
        },
      },
    };

    const componentSpy = jest.spyOn(Component, 'data');
    vueXstatePlugin.install(Vue);
    // eslint-disable-next-line no-unused-vars
    const component = new Vue(Component);

    await flushMicrotasks();

    expect(showError).toHaveBeenCalled();
    expect(componentSpy.mock.results[0].value.x.state).toHaveProperty(
      'value',
      'errored'
    );
    componentSpy.mockRestore();
  });
});
