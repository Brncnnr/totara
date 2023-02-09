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
 * @module tui_xstate
 */

import { interpret } from 'tui_xstate/xstate';
import createDebounceMachine from './fixtures/debounce_test_machine';
import pending from 'tui/pending';

describe('interpret', () => {
  it('calls pending during delays', () => {
    jest.useFakeTimers();

    const machine = createDebounceMachine({ id: 'debounce' });
    const service = interpret(machine);
    let state;
    service.onTransition(s => {
      state = s;
    });
    service.start();
    expect(state.value).toEqual('idle');

    expect(pending.__started()).toBe(0);
    expect(pending.__completed()).toBe(0);

    service.send({ type: 'GO', action() {} });

    expect(state.value).toEqual('debouncing');

    expect(pending.__started()).toBe(1);
    expect(pending.__completed()).toBe(0);

    jest.runAllTimers();

    expect(state.value).toEqual('idle');

    expect(pending.__started()).toBe(1);
    expect(pending.__completed()).toBe(1);
  });
});
