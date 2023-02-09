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

import traverse from 'traverse';
import { set } from 'tui/util';
import { assign, createMachine, spawn, interpret } from 'tui_xstate/xstate';
import { parseQueryString } from 'tui/util';
import apollo from 'tui/apollo_client';
import { Observable } from 'apollo-link';
import extractEvents from './extract_events';
import { showError } from '../util';
import { $SHOW_ERROR, $QUERY_ALL } from '../constants';

const observers = new Map();

/**
 * @typedef {import('xstate).StateNode} StateNode
 */

/**
 * @param {Map<string, object>} instances
 * @param {import('xstate').StateMachine} machine
 * @param {?import('../vue_xstate_plugin').MapQueryParamsToContext} mapQueryParamsToContext
 * @param {object} componentThis Vue component instance
 * @returns {import('xstate').StateMachine|StateNode}
 */
export default function configureMachine(
  instances,
  machine,
  mapQueryParamsToContext,
  componentThis
) {
  const state = machine.config;
  // NOTE: preserveActionOrder: true will become the default behaviour in XState v5
  // This config can be removed after an upgrade to v5.
  state.preserveActionOrder = true;

  const registeredQueries = machine.options.queries || {};
  const queryServices = {};
  const updateServices = {};
  const pluginActions = {
    [$SHOW_ERROR]: showError,
  };

  Object.keys(machine.options.services).forEach(serviceId => {
    const baseService = machine.options.services[serviceId];

    if (registeredQueries[serviceId]) {
      queryServices[serviceId] = baseService;
    }

    // recursively configure child machines
    if (baseService.machine) {
      const configuredMachine = configureMachine(
        instances,
        baseService.machine
      );
      updateServices[serviceId] = configuredMachine;

      const service = interpret(configuredMachine, { devTools: true });
      const events = extractEvents(configuredMachine.config);

      // construct the "x" object that will be attached to the Vue component
      instances.set(configuredMachine.config.id, {
        parentId: machine.config.id,
        state: service.initialState,
        initialState: service.initialState,
        context: configuredMachine.context,
        service,
        machine: configuredMachine,
        e: events,
        selectors: configuredMachine.options.selectors,
        children: {},
      });
    }
  });

  // Top level 'on' used for the load Events
  if (!state.on) {
    state.on = {};
  }

  Object.keys(queryServices).forEach(queryId => {
    const service = queryServices[queryId];
    const eventType = `${queryId}.LOADED`;
    const loadAction = `load_${queryId}`;

    pluginActions[`observe_${queryId}`] = assign({
      [`_${queryId}_observer`]: (context, event) => {
        const queryOptions = service(context, event);

        if (!queryOptions) {
          return null;
        }

        const key = `${queryId}|${JSON.stringify(
          queryOptions.variables || {}
        )}`;
        const observerId = `observe_${queryId}`;

        if (observers.has(key)) {
          const observable = observers.get(key);
          return spawn(observable, observerId);
        }

        const observable = Observable.from(
          apollo.watchQuery(queryOptions)
        ).map(result =>
          Object.assign({}, result, { type: eventType, queryId })
        );

        /* If there is a mismatch between an entity defined in the query and in a mutation response
         * Apollo will throw an error when writing the mutation response to the cache.
         * Without the code below, this error is swallowed.
         * Its possible that Apollo could be configured to be more flexible.
         * For the time being, the log below assists the developer in identifying where graphql definitions have diverged.
         */
        observable.subscribe({
          error(err) {
            console.error({ err, queryOptions });
          },
        });

        observers.set(key, observable);
        return spawn(observable, observerId);
      },
    });

    updateServices[queryId] = (context, event) => {
      const queryOptions = service(context, event);
      if (!queryOptions) {
        return;
      }

      return apollo.query(queryOptions);
    };

    const on = state.on[eventType]
      ? state.on[eventType]
      : { actions: [loadAction] };

    if (!on.actions) {
      on.actions = [loadAction];
    } else if (!Array.isArray(on.actions)) {
      on.actions = [on.actions, loadAction];
    }

    state.on[eventType] = on;

    pluginActions[loadAction] = assign((context, event) => {
      const result = Object.assign({}, event);

      if (service.updateContext) {
        delete result.type;
        return service.updateContext(context, result);
      }

      if (!result.data || Object.keys(result.data).length === 0) {
        return {};
      }

      // query response event data merged into context underneath the queryId
      return {
        [queryId]: Object.assign({}, context[queryId] || {}, result.data),
      };
    });
  });

  /**
   * if there are > 1 queries then create a $query_all service
   * to call specfied queries in a Promise.all
   */
  if (Object.keys(queryServices).length > 1) {
    updateServices[$QUERY_ALL] = (context, event, { src: { queries } }) => {
      return Promise.all(
        queries.map(queryId => {
          const service = queryServices[queryId];
          const queryOptions = service(context, event);
          if (!queryOptions) {
            return Promise.resolve();
          }

          return apollo.query(queryOptions);
        })
      );
    };
  }

  const invokeIds = {};
  traverse(state).forEach(function(node) {
    if (node && node.invoke) {
      const location = `machine: "${machine.config.id}", state: "${this.key ||
        'root'}"`;

      const update = updateNode({
        location,
        node,
        key: this.key,
        parent: this.parent,
        queryServices,
        invokeIds,
      });

      if (update !== node) {
        this.update(update, true);
      }
    }
  });

  const actions = Object.assign({}, machine.options.actions, pluginActions);
  const services =
    Object.keys(updateServices).length > 0
      ? Object.assign({}, machine.options.services, updateServices)
      : machine.options.services;
  const options = Object.assign({}, machine.options, { actions, services });
  const configuredMachine = createMachine(state, options);

  let paramSyncdMachine;
  if (mapQueryParamsToContext && componentThis) {
    const params = parseQueryString(window.location.search);
    const context = mapQueryParamsToContext.call(componentThis, params);

    paramSyncdMachine = configuredMachine.withContext(
      Object.assign({}, configuredMachine.context, context)
    );
  }

  return paramSyncdMachine || configuredMachine;
}

/**
 * @callback Service
 * @param {object} context
 * @param {import('xstate').Event} event
 */

/**
 * @typedef {Object} UpdateNodeOptions
 * @property {string} location
 * @property {StateNode} node
 * @property {{ [serviceId: string]: Service }} queryServices
 * @property {{ [invokeId: string]: boolean }} invokeIds
 */

/**
 * @param {UpdateNodeOptions} options
 * @returns {StateNode}
 */
export const updateNode = ({
  location,
  node,
  key,
  parent,
  queryServices,
  invokeIds,
}) => {
  const updates = [];
  const entryUpdates = [];
  const errorMessage = src =>
    `Invoked service: ${
      typeof src === 'object' ? JSON.stringify(src) : src
    } in ${location},
    is missing an "onError" handler.
    Invoked services without "onError" handlers are given fallback { target: defaultErrorTargetId, actions: '${$SHOW_ERROR}' },
    but State config is missing an { id: "ID", meta: { defaultErrorTarget: true } } in an error state.`;

  /**
   * Set an unassigned invoke ids to be the same as the src.
   * This is needed for onDone handlers to trigger and helps with readability.
   * @link https://github.com/statelyai/xstate/pull/2864
   */

  if (
    !Array.isArray(node.invoke) &&
    !node.invoke.id &&
    typeof node.invoke.src === 'string'
  ) {
    if (node.invoke.src === key) {
      throw new Error(
        `invoke "src" name matches its state name: ${node.invoke.src} at ${location}`
      );
    }

    if (invokeIds[node.invoke.src]) {
      throw new Error(`
        Generated invoke ids have the same value: ${node.invoke.src} at ${location}.
        Give these invoke objects unique ids.
      `);
    }

    invokeIds[node.invoke.src] = true;

    updates.push({
      path: ['invoke', 'id'],
      value: node.invoke.src,
    });
  }

  /**
   * Fallback to the default Tui showError handler
   * when the onError transition is not handled.
   */

  if (!Array.isArray(node.invoke) && !node.invoke.onError) {
    const defaultErrorTargetId = findDefaultErrorTargetId(node, key, parent);
    if (!defaultErrorTargetId) {
      throw new Error(errorMessage(node.invoke.src));
    }

    updates.push({
      path: ['invoke', 'onError'],
      value: {
        actions: $SHOW_ERROR,
        target: defaultErrorTargetId,
      },
    });
  }

  if (Array.isArray(node.invoke)) {
    const invokeUpdates = [];
    node.invoke.forEach(invoke => {
      if (!invoke.onError) {
        const defaultErrorTargetId = findDefaultErrorTargetId(
          node,
          key,
          parent
        );
        if (!defaultErrorTargetId) {
          throw new Error(errorMessage(invoke.src));
        }

        invokeUpdates.push(
          Object.assign({}, invoke, {
            onError: { target: defaultErrorTargetId, actions: $SHOW_ERROR },
          })
        );
      }
    });

    if (invokeUpdates.length > 0) {
      updates.push({
        path: ['invoke'],
        value: invokeUpdates,
      });
    }
  }

  /**
   * Add `observe_{QUERY_ID}` to entry actions of any state that invokes these queries
   */

  if (typeof node.invoke.src === 'string' && queryServices[node.invoke.src]) {
    entryUpdates.push(`observe_${node.invoke.src}`);
  }

  if (Array.isArray(node.invoke)) {
    node.invoke.forEach(serviceDef => {
      entryUpdates.push(`observe_${serviceDef.src}`);
    });
  }

  if (
    node.invoke.src &&
    Array.isArray(node.invoke.src.queries) &&
    node.invoke.src.type === $QUERY_ALL
  ) {
    node.invoke.src.queries.forEach(queryId => {
      entryUpdates.push(`observe_${queryId}`);
    });
  }

  if (entryUpdates.length > 0) {
    if (Array.isArray(node.entry)) {
      node.entry.forEach(action => {
        entryUpdates.unshift(action);
      });
    } else if (node.entry) {
      entryUpdates.unshift(node.entry);
    }

    updates.push({
      path: ['entry'],
      value: entryUpdates,
    });
  }

  if (updates.length === 0) {
    return node;
  }

  return updates.reduce((update, { path, value }) => {
    set(update, path, value);
    return update;
  }, Object.assign({}, node));
};

/**
 * Searches state config for
 * Cycles through current state with an invoke, then sibling states, then parent state recursively.
 *
 * @param {StateNode} node
 * @param {string} key
 * @param {?object} parent - parent node wrapper created by traverse module
 * @returns {?string}
 */
export const findDefaultErrorTargetId = (node, key, parent) => {
  if (node.meta && node.meta.defaultErrorTarget) {
    if (node.id) {
      return `#${node.id}`;
    }

    throw new Error(`${JSON.stringify(node)} is missing an "id" property`);
  }

  if (parent) {
    if (parent.key === 'states') {
      const siblingStateNames = Object.keys(parent.node).filter(
        state => state !== key
      );

      for (let i = 0; i < siblingStateNames.length; i++) {
        const siblingStateName = siblingStateNames[i];
        const siblingNode = parent.node[siblingStateName];
        const defaultErrorTargetId = findDefaultErrorTargetId(
          siblingNode,
          siblingStateName
        );
        if (defaultErrorTargetId) {
          return defaultErrorTargetId;
        }
      }
    }

    return findDefaultErrorTargetId(parent.node, parent.key, parent.parent);
  }

  return null;
};
