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
import { createMachine, assign } from 'tui_xstate/xstate';
import debounceMachine from './debounce_test_machine';

export const makeState = ({ id, context, onError }) => {
  if (onError === undefined) {
    onError = { actions: assign({ hasErrored: true }) };
  }

  return {
    id,
    initial: 'inactive',
    context,
    states: {
      active: {
        on: {
          TOGGLE: 'inactive',
        },
      },
      inactive: {
        invoke: {
          src: 'debounceMachine',
          data: {
            willError: ({ willError }) => willError,
          },
          onError,
        },
        on: {
          TOGGLE: 'active',
        },
      },
    },
  };
};

export const options = {
  services: {
    debounceMachine,
  },
};

export default function createParentMachine({
  id,
  context = { willError: false, hasErrored: false },
}) {
  const state = makeState({ id, context });
  return createMachine(state, options);
}
