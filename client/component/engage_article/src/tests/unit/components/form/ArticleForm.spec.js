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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @module engage_article
 */

import ArticleForm from 'engage_article/components/form/ArticleForm';
import { shallowMount } from '@vue/test-utils';

// Mock tui.import
jest.mock('tui/tui', function() {
  return {
    import: async function(id) {
      switch (id) {
        case 'editor_weka/extensions/link':
          return require('editor_weka/extensions/link');

        case 'editor_weka/extensions/text':
          return require('editor_weka/extensions/text');

        default:
          throw `No module found for id ${id}`;
      }
    },

    loadRequirements: () => new Promise(resolve => resolve()),
  };
});

jest.mock('editor_weka/api', () => ({
  __esModule: true,
  getLinkMetadata: () => null,
}));

describe('Engage ArticleForm', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(ArticleForm, {
      mocks: {
        $str(identifier, component) {
          return `${identifier}, ${component}`;
        },

        $id() {
          return 'Some random string';
        },

        $apollo: {
          mutate() {
            return Promise.resolve({ data: { item_id: 500 } });
          },
        },
      },
      propsData: {
        articleName: 'ArticleForm',
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.element).toMatchSnapshot();
  });
});
