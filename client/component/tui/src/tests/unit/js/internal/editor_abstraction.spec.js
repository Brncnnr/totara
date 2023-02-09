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

import { getEditorConfig } from '../../../../js/internal/editor_abstraction';
import textareaFallback from '../../../../js/internal/editor_textarea_fallback';

jest.mock('tui/tui', () => ({
  import(path) {
    return { path };
  },

  defaultExport(path) {
    return path;
  },
}));

jest.mock('tui/apollo_client', () => {
  const configQuery = require('core/graphql/editor');
  return {
    async query({ query, variables }) {
      if (query === configQuery) {
        if (variables.format == 999) {
          return {
            data: {
              editor: {
                js_module: null,
                variant: {
                  name: 'standard',
                  options: null,
                },
                context_id: 99,
              },
            },
          };
        }
        return {
          data: {
            editor: {
              js_module: 'editor_foo/interface',
              variant: {
                name: 'primary',
                options: '{"foo": true}',
              },
              context_id: 99,
            },
          },
        };
      }
    },
  };
});

describe('getEditorConfig', () => {
  it('fetches editor config data from the server', async () => {
    let result;
    result = await getEditorConfig({});

    expect(await result.loadInterface()).toEqual({
      path: 'editor_foo/interface',
    });
    expect(result.getEditorOptions()).toEqual({ foo: true });
    expect(result.getContextId()).toBe(99);

    result = await getEditorConfig({ format: 999 });

    expect(await result.loadInterface()).toEqual(textareaFallback);
    expect(result.getEditorOptions()).toEqual({});
    expect(result.getContextId()).toBe(99);
  });
});
