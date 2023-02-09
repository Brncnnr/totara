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

/**
 * @typedef {Object} Scope
 * @property {Array<import('./index').State>} drafts
 */

let currentScope = null;

/**
 * Gets the current scope.
 *
 * @returns {Scope}
 */
export function getCurrentScope() {
  return currentScope;
}

/**
 * Enters a new scope.
 *
 * @throws {Error} if already in a scope
 * @returns {Scope}
 */
export function enterScope() {
  if (currentScope) {
    throw new Error('Nested produce is not supported');
  }
  currentScope = {
    drafts: [],
  };
  return currentScope;
}

/**
 * Leaves the current scope.
 *
 * @throws {Error} if not in a scope
 * @param {Scope} scope
 */
export function leaveScope(scope) {
  /* istanbul ignore else */
  if (scope === currentScope) {
    currentScope = null;
  } else {
    throw new Error('Scope mismatch');
  }
}
