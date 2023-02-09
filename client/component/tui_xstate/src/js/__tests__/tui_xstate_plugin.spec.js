/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
import createDebounceMachine, {
  DEBOUNCE_MACHINE_ID,
} from './fixtures/debounce_test_machine';
import createParentMachine from './fixtures/parent_test_machine';
import { StateNode } from 'xstate';
import { flushMicrotasks } from '../../../../tui/src/tests/unit/util';

let Vue;
beforeEach(() => {
  jest.resetModules();
  Vue = require('vue');
  Vue.config.errorHandler = jest.fn();
});

describe('vueXStatePlugin#xState', () => {
  it('throws an error without proper config', () => {
    const spy = jest.spyOn(mixin, 'data');
    vueXstatePlugin.install(Vue);

    const badConfig = { xState: {} };
    // eslint-disable-next-line no-unused-vars
    const containerComponent = new Vue(badConfig);

    expect(spy.mock.results[0]).toHaveProperty('type', 'throw');
    spy.mockRestore();
  });

  it('throws an error when given a non-existent machine id', () => {
    const spy = jest.spyOn(mixin, 'data');
    vueXstatePlugin.install(Vue);

    const badConfig = { xState: { machineId: 'does-not-exist' } };
    // eslint-disable-next-line no-unused-vars
    const containerComponent = new Vue(badConfig);

    expect(spy.mock.results[0]).toHaveProperty('type', 'throw');
    spy.mockRestore();
  });

  it('does not throw an error when given a real machine creating function', () => {
    const config = {
      xState: { machine: () => createDebounceMachine() },
    };
    const dataSpy = jest.spyOn(mixin, 'data');
    const machineSpy = jest.spyOn(config.xState, 'machine');
    vueXstatePlugin.install(Vue);

    // eslint-disable-next-line no-unused-vars
    const containerComponent = new Vue(config);

    expect(dataSpy).toHaveBeenCalledTimes(1);
    expect(dataSpy.mock.results[0]).toHaveProperty('type', 'return');
    expect(machineSpy).toHaveBeenCalled();
    expect(machineSpy.mock.results[0].value).toBeInstanceOf(StateNode);

    dataSpy.mockRestore();
    machineSpy.mockRestore();
  });

  it('attaches a registered machine to a child component with "machineId"', () => {
    const Child = {
      data() {
        return {};
      },
      xState: { machineId: DEBOUNCE_MACHINE_ID },
    };

    const Parent = {
      components: {
        Child,
      },
      data() {
        return { test: true };
      },
      xState: {
        machine: () => createDebounceMachine(),
      },
    };

    const componentSpy = jest.spyOn(Parent, 'data');
    const childSpy = jest.spyOn(Child, 'data');
    vueXstatePlugin.install(Vue);

    /* eslint-disable no-unused-vars */
    const containerComponent = new Vue(Parent);
    const childComponent = new Vue(Child);
    /* eslint-enable no-unused-vars */

    expect(componentSpy).toHaveBeenCalled();
    expect(componentSpy.mock.results[0].value).toHaveProperty('test');
    expect(componentSpy.mock.results[0].value).toHaveProperty('x');
    expect(childSpy.mock.results[0].value).toHaveProperty('x');

    componentSpy.mockRestore();
    childSpy.mockRestore();
  });

  it('registers a child machine instantiated inside a parent machine', () => {
    const parentMachineId = 'parent';
    const Child = {
      data() {
        return {};
      },
      xState: { machineId: DEBOUNCE_MACHINE_ID },
    };

    const Parent = {
      data() {
        return { test: true };
      },
      xState: {
        machine() {
          return createParentMachine({
            id: parentMachineId,
          });
        },
      },
    };

    const dataSpy = jest.spyOn(mixin, 'data');
    const componentSpy = jest.spyOn(Parent, 'data');
    const childSpy = jest.spyOn(Child, 'data');
    vueXstatePlugin.install(Vue);

    /* eslint-disable no-unused-vars */
    const containerComponent = new Vue(Parent);
    const childComponent = new Vue(Child);
    /* eslint-enable no-unused-vars */

    expect(dataSpy).not.toThrow();
    expect(componentSpy).not.toThrow();
    expect(componentSpy).toHaveBeenCalled();
    expect(componentSpy.mock.results[0].value).toHaveProperty('test');
    expect(componentSpy.mock.results[0].value).toHaveProperty('x');
    expect(childSpy).not.toThrow();
    expect(childSpy).toHaveBeenCalled();
    expect(childSpy.mock.results[0].value).toHaveProperty('x');

    dataSpy.mockRestore();
    componentSpy.mockRestore();
    childSpy.mockRestore();
  });

  test('escalates and handles an explicity handled Error from a child machine', async () => {
    const Component = {
      data() {
        return { test: true };
      },
      xState: {
        machine() {
          return createParentMachine({
            id: 'parent-with-child-error',
            context: { willError: true },
          });
        },
      },
    };

    const componentSpy = jest.spyOn(Component, 'data');
    vueXstatePlugin.install(Vue);
    //  eslint-disable-next-line no-unused-vars
    const component = new Vue(Component);

    await flushMicrotasks();
    expect(componentSpy.mock.results[0].value.x.context).toHaveProperty(
      'hasErrored',
      true
    );
    componentSpy.mockRestore();
  });
});
