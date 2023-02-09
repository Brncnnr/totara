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
 */
import { isDraft } from 'tui/immutable';
import { createSelectorCreator, defaultMemoize } from 'reselect';
import { showError as tuiShowError } from 'tui/errors';
export const createSelector = createSelectorCreator(customMemoize);

/**
 * Custom createSelector wrapper.
 *
 * Will bypass the selector memoization/cache when passed a draft object.
 */

/**
 * Custom memoize -- bypass the memoization when passed a draft object to avoid
 * invalidating the cache.
 */
function customMemoize(func, equalityCheckOrOptions) {
  const defaultMemoized = defaultMemoize(func, equalityCheckOrOptions);
  const memoized = function(...args) {
    return args.some(x => isDraft(x))
      ? func.apply(null, arguments)
      : defaultMemoized.apply(null, arguments);
  };
  memoized.clearCache = defaultMemoized.clearCache;
  return memoized;
}

/**
 * XState wrapper for Tui showError Modal
 *
 * @param {object} context
 * @param {import('xstate').Event} event
 */
export function showError(context, event) {
  if (event.data instanceof Error) {
    tuiShowError(event.data);
    return;
  }

  if (event.type == 'xstate.error' && event.data.data instanceof Error) {
    tuiShowError(event.data.data);
    return;
  }

  console.error(
    `[Tui XState] tui_xstate/util showError passed an event without an Error.
    Original params:`,
    { context, event }
  );
}
