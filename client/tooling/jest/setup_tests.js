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

import { config } from '@vue/test-utils';
import './matchers';
import { toHaveNoViolations } from 'jest-axe';

import * as i18n from 'tui/i18n';

jest.mock('tui/storage');
jest.mock('tui/internal/lang_string_store');
jest.mock('tui/config');
jest.mock('tui/apollo_client', () => null);
jest.mock('tui/tui');
jest.mock('tui/pending');
jest.mock('tui/i18n');

expect.extend(toHaveNoViolations);

jest.mock('tui/components/icons/implementation/SvgIconWrap', () => {
  return {
    render: h => h('span'),
  };
});

// temporarily disable aria-allowed-attr to match behavior of older jest-axe
jest.mock('jest-axe', () => {
  const actual = jest.requireActual('jest-axe');
  return {
    ...actual,
    axe: actual.configureAxe({
      rules: {
        'aria-allowed-attr': { enabled: false },
      },
    }),
  };
});

config.mocks.$str = (key, comp, a) => {
  if (i18n.hasString(key, comp)) {
    return i18n.getString(key, comp, a);
  }
  return a
    ? `[[${key}, ${comp}, ${JSON.stringify(a)}]]`
    : `[[${key}, ${comp}]]`;
};

config.mocks.uid = 'id';
config.mocks.$id = x => (x ? 'id-' + x : 'id');

const upperFirst = str => str.slice(0, 1).toUpperCase() + str.slice(1);

// throw an error if output is printed to the console
['log', 'warn', 'error'].forEach(method => {
  const original = global.console[method];
  global.console['debug' + upperFirst(method)] = original;
  global.console[method] = (...args) => {
    original(...args);
    throw new Error('Console output triggered');
  };
});

// Work around JSDOM throwing an error when jest-axe calls window.getComputedStyle(elt, pseudoElt)
// https://github.com/nickcolley/jest-axe/issues/147
const dummyStyle = global.getComputedStyle(document.createElement('div'));
const originalComputedStyle = global.getComputedStyle;
global.getComputedStyle = (elt, pseudoElt) =>
  pseudoElt ? dummyStyle : originalComputedStyle(elt);
