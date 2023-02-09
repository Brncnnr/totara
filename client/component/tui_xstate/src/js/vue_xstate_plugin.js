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
 * @author Simon Tegg <simon.tegg@totaralearning.com
 * @module tui_xstate
 */

import { interpret } from './xstate';
import { formatParams, parseQueryString } from 'tui/util';
import configureMachine from './internal/configure_machine';
import extractEvents from './internal/extract_events';

const instances = new Map();

/**
 * `xState` property on Vue component options.
 *
 * @typedef {Object} XStateVueComponentOptions
 * @property {?string|() => string} machineId
 * @property {?() => import('xstate').StateMachine} machine
 * @property {?MapStateToQueryParams} mapStateToQueryParams
 * @property {?MapContextToQueryParams} mapContextToQueryParams
 * @property {?MapQueryParamsToContext} mapQueryParamsToContext
 */

/**
 * @callback MapStateToQueryParams
 * @param {string[]} statePaths
 * @param {string[]} prevStatePaths
 * @param {object} context
 * @returns {?QueryParams}
 */

/**
 * @callback MapContextToQueryParams
 * @param {object} context
 * @param {object} prevContext
 * @returns {?QueryParams}
 */

/**
 * @callback MapQueryParamsToContext
 * @param {QueryParams} context
 * @returns {object}
 */

/**
 * Defines keys and values to merge into url query string ?key=value.
 *
 * @typedef {Object} QueryParams
 */

/* vue_xstate_plugin is implemented as a global mixin.
 * The plugin attaches $send, $context, $matches and $e to a Vue component,
 * and allows communication with a specific machine
 * (registered with machine() or machineId in the xState config section
 * In Vue 3.x this will likely be swapped for a hook that does something similar.
 */
export const mixin = {
  data() {
    if (this && this.$options.xState) {
      const { machineId } = this.$options.xState;

      /* Block below checks that the xState config has been properly set up */
      if (machineId) {
        const id =
          typeof machineId === 'function' ? machineId.call(this) : machineId;

        if (instances.has(id)) {
          return { x: instances.get(id) };
        } else {
          throw Error(`No machine with id: "${id}" registered`);
        }
      }

      if (!this.$options.xState.machine) {
        throw new Error(
          'xState config needs a "machine()" or a "machineId" option'
        );
      }

      const machine = this.$options.xState.machine.call(this);

      if (!machine.config.id) {
        throw new Error('machine does not have an id');
      }

      // The machine is already registered. Return this machine
      if (instances.has(machine.config.id)) {
        return { x: instances.get(machine.config.id) };
      }
      /* end of config check block */

      const configuredMachine = configureMachine(
        instances,
        machine,
        this.$options.xState.mapQueryParamsToContext,
        this
      );

      const service = interpret(configuredMachine, { devTools: true });
      const events = extractEvents(configuredMachine.config);

      // construct the "x" object that will be attached to the Vue component
      instances.set(configuredMachine.config.id, {
        parent: null,
        state: service.initialState,
        initialState: service.initialState,
        context: configuredMachine.context,
        service,
        machine: configuredMachine,
        e: events,
        selectors: configuredMachine.options.selectors,
        children: {},
      });

      return { x: instances.get(configuredMachine.config.id) };
    }

    return {};
  },

  computed: {
    $context() {
      return this.x ? this.x.context : null;
    },

    $selectors() {
      return this.x ? this.x.selectors : null;
    },

    $e() {
      return this.x ? this.x.e : null;
    },
  },

  created() {
    const hasService = this.x && this.x.service;

    if (hasService) {
      this.x.service.onTransition(state => {
        this.x.state = state;
        this.x.context = state.context;

        if (this.$options.xState.mapStateToQueryParams) {
          const { mapStateToQueryParams } = this.$options.xState;
          const statePaths = state.toStrings();
          const prevStatePaths = state.history ? state.history.toStrings() : [];
          const toMap = mapStateToQueryParams(
            statePaths,
            prevStatePaths,
            state.context
          );

          if (toMap && Object.keys(toMap).length > 0) {
            const params = parseQueryString(window.location.search);
            const updatedParams = Object.assign(params, toMap);

            // remove undefined from params
            Object.entries(updatedParams).forEach(
              ([key, value]) => value === undefined && delete updatedParams[key]
            );

            const formattedParams = formatParams(updatedParams);
            const url = `${window.location.pathname}?${formattedParams}`;
            window.history.replaceState(null, null, url);
          }
        }

        this.x.service.children.forEach((service, machineId) => {
          if (!service.machine) {
            return;
          }

          const x = instances.get(machineId) || {};

          x.service = service;
          x.state = service._state;
          x.context = Object.assign(
            {},
            x.context || {},
            service.machine.context
          );
          instances.set(machineId, x);

          service.onTransition(childState => {
            const x = instances.get(machineId);
            x.state = childState;
            x.context = childState.context;
            instances.set(machineId, x);
          });
        });
      });
    }

    if (hasService && this.$options.xState.mapContextToQueryParams) {
      const { mapContextToQueryParams } = this.$options.xState;
      this.x.service.onChange((context, prevContext) => {
        if (prevContext) {
          const toMap = mapContextToQueryParams.call(
            this,
            context,
            prevContext
          );

          if (toMap && Object.keys(toMap).length > 0) {
            const params = parseQueryString(window.location.search);
            const updatedParams = Object.assign(params, toMap);

            // remove undefined from params
            Object.entries(updatedParams).forEach(
              ([key, value]) => value === undefined && delete updatedParams[key]
            );

            const formattedParams = formatParams(updatedParams);
            const url = `${window.location.pathname}?${formattedParams}`;
            window.history.replaceState(null, null, url);
          }
        }
      });
    }

    if (hasService) {
      this.x.service.start();
    }
  },

  methods: {
    /**
     * @typedef {import('xstate').Event} Event
     */

    /**
     * @param {Event} event - the event to send to the linked machine
     */
    $send(event) {
      if (this.x.service) {
        this.x.service.send(event);
      }
    },

    /**
     * @param {string} path - A dot separated path to a machine state
     * @return {boolean}
     */
    $matches(path) {
      const { state } = this.x;
      return (
        state.matches(path) ||
        // if we're on a final state, also try matching the previous state.
        // this is useful, as usually on a final state we care more about what
        // state we were in before the machine completed.
        (state.done && state.history && state.history.matches(path))
      );
    },
  },
};

export default {
  install(Vue) {
    Vue.mixin(mixin);
  },
};
