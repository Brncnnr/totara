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

const { getThemeExportedVars } = require('../index');

let css;

describe('getThemeExportedVars', () => {
  it('extracts custom property values from CSS', () => {
    expect(
      getThemeExportedVars(':root { /* theme:var */ --val: 5px; }')
    ).toEqual({
      vars: {
        val: { type: 'value', value: '5px' },
      },
    });

    css = `:root {
      /* theme:var */ --tui-w: 5px;
      /* theme:var */ --tui-w2: var(--tui-w);
      /* theme:var */ --tui-h2: var(--tui-h);
      /* theme:var */ --tui-h: 10px;
      /* theme:var *//* --tui-h: 20px; */ --tui-m: 1px;
      /* theme:var */--tui-m2: var(--tui-m);
      /* theme:var */--tui-m: 2px;
      /* theme:var */--tui-p: calc(var(--tui-m) * 2);
      /* theme:var */--tui-color: var(--tui-x, #fff);
      /* theme:var brand-color */ --foo: #f00;
      /* theme:var */ --bar: #ff0;
      /* theme:var */
      /* theme:derive scale(var(--color-state),  -20) */
      --bar-dark: #aa0;
      /* theme:var */
      /* theme:derive adjust-hex-value-brightness(var(--color-state), -27) */
      --bar-dark-2: #aa0;
      --tui-not-exported: #ff0;
    }`;
    const vars = getThemeExportedVars(css);
    expect(vars).toEqual({
      vars: {
        'tui-w': { type: 'value', value: '5px' },
        'tui-w2': { type: 'var', value: 'tui-w' },
        'tui-h2': { type: 'var', value: 'tui-h' },
        'tui-h': { type: 'value', value: '10px' },
        'tui-m': { type: 'value', value: '2px' },
        'tui-m2': { type: 'var', value: 'tui-m' },
        'tui-p': { type: 'value', value: 'calc(var(--tui-m) * 2)' },
        'tui-color': { type: 'var', value: 'tui-x', default: '#ffffff' },
        // theme:var arg is ignored
        foo: { type: 'value', value: '#ff0000' },
        bar: { type: 'value', value: '#ffff00' },
        'bar-dark': {
          type: 'value',
          value: '#aaaa00',
          transform: {
            type: 'var',
            call: 'scale',
            source: 'color-state',
            args: [-20],
          },
        },
        'bar-dark-2': {
          type: 'value',
          value: '#aaaa00',
          transform: {
            type: 'var',
            call: 'adjust-hex-value-brightness',
            source: 'color-state',
            args: [-27],
          },
        },
      },
    });
  });
});
