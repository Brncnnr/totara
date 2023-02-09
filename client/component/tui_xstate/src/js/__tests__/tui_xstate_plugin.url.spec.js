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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module tui_xstate
 * @jest-environment jsdom
 */

import vueXstatePlugin, { mixin } from '../vue_xstate_plugin';
import createDebounceMachine from './fixtures/debounce_test_machine';
import { flushMicrotasks } from '../../../../tui/src/tests/unit/util';

let Vue;
beforeEach(() => {
  jest.resetModules();
  Vue = require('vue');
  Vue.config.errorHandler = jest.fn();
  window.history.replaceState({}, null, '/test.html?application_id=1');
});

describe('vueXStatePlugin#xState', () => {
  test('mapQueryParamsToContext adds data to context defined in the url query state', async () => {
    const activeIndex = 3;
    window.history.pushState(
      {},
      null,
      `/test.html?active_index=${activeIndex}`
    );
    const Component = {
      data() {
        return { test: true };
      },
      xState: {
        machine() {
          return createDebounceMachine();
        },

        mapQueryParamsToContext({ active_index }) {
          return { activeIndex: parseInt(active_index, 10) };
        },
      },
    };

    const dataSpy = jest.spyOn(mixin, 'data');
    const componentSpy = jest.spyOn(Component, 'data');
    vueXstatePlugin.install(Vue);

    /* eslint-disable-next-line no-unused-vars */
    const component = new Vue(Component);

    await flushMicrotasks();
    // console.log(componentSpy.mock.results[0].value.x.context)
    // await flushMicrotasks()

    expect(dataSpy).not.toThrow();
    expect(componentSpy.mock.results[0].value).toHaveProperty('x');
    expect(componentSpy.mock.results[0].value.x).toHaveProperty('context');
    expect(componentSpy.mock.results[0].value.x.context).toHaveProperty(
      'activeIndex',
      activeIndex
    );

    dataSpy.mockRestore();
    componentSpy.mockRestore();
  });

  test('mapStateToQueryParams syncs the state to the query params', () => {
    const Component = {
      data() {
        return { test: true };
      },
      xState: {
        machine() {
          return createDebounceMachine();
        },

        mapStateToQueryParams(statePaths, prevStatePaths) {
          if (
            statePaths.includes('debouncing') &&
            prevStatePaths.includes('idle')
          ) {
            return { debouncing: true };
          } else {
            return { debouncing: undefined };
          }
        },
      },
    };

    const dataSpy = jest.spyOn(mixin, 'data');
    const componentSpy = jest.spyOn(Component, 'data');
    vueXstatePlugin.install(Vue);
    const component = new Vue(Component);

    expect(dataSpy).not.toThrow();
    expect(componentSpy.mock.results[0].value).toHaveProperty('x');
    expect(componentSpy.mock.results[0].value.x).toHaveProperty('state');
    expect(componentSpy.mock.results[0].value.x).toHaveProperty('service');

    const { service } = component.x;
    service.send('GO');

    expect(componentSpy.mock.results[0].value.x.state).toHaveProperty(
      'value',
      'debouncing'
    );
    expect(window.location.search).toEqual('?application_id=1&debouncing=true');

    service.send('CANCEL');

    expect(componentSpy.mock.results[0].value.x.state).toHaveProperty(
      'value',
      'idle'
    );
    expect(window.location.search).toEqual('?application_id=1');

    dataSpy.mockRestore();
    componentSpy.mockRestore();
  });
});
