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
 * @module mod_approval
 */
import { assign, actions, createMachine } from 'tui_xstate/xstate';
const { escalate } = actions;

export const DEBOUNCE_MACHINE_ID = 'debounceId';

/**
 * @see {@link https://xstate-catalogue.com/machines/debounce}
 */
export default function createDebounceMachine() {
  const state = {
    id: DEBOUNCE_MACHINE_ID,
    initial: 'idle',
    context: {},
    states: {
      idle: {
        always: [
          {
            cond: ({ willError }) => willError,
            target: 'throwingError',
          },
        ],

        on: {
          GO: {
            actions: 'assignActionToContext',
            target: 'debouncing',
          },
          GO_NESTED: 'nested',
        },
      },
      nested: {
        initial: 'childState',
        states: {
          childState: {},
        },
      },
      debouncing: {
        on: {
          GO: {
            actions: 'assignActionToContext',
            target: 'debouncing',
          },
          CANCEL: 'idle',
        },
        after: {
          2000: {
            target: 'idle',
            actions: 'performAction',
          },
        },
      },
      throwingError: {
        invoke: {
          src: 'throwError',
          onError: { actions: 'escalate' },
        },
      },
    },
  };

  const options = {
    actions: {
      clearAction: assign({
        action: undefined,
      }),
      assignActionToContext: assign((context, event) => {
        return {
          action: event.action,
        };
      }),
      performAction: context => {
        return context.action();
      },

      // event.data instanceof Error
      escalate: escalate((context, event) => event),
    },

    services: {
      // service must be async for error handler to recognise error throw
      throwError: async () => {
        throw new Error('oh no!');
      },
    },
  };

  return createMachine(state, options);
}
