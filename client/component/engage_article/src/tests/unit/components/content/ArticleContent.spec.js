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
 * @author Alvin Smith <alvin.smith@totaralearning.com>
 * @module engage_article
 */

import ArticleContent from 'engage_article/components/content/ArticleContent';
import { shallowMount } from '@vue/test-utils';

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
    scan() {
      return 'content';
    },
  };
});

jest.mock('editor_weka/api', () => ({
  __esModule: true,
  getLinkMetadata: () => null,
}));

describe('Engage ArticleContent', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(ArticleContent, {
      propsData: {
        resourceId: 'ArticleContent',
        content: '',
        title: '',
        updateAble: true,
        editing: false,
      },

      mocks: {
        $str(a, b) {
          return `[${a}-${b}]`;
        },
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.html()).toMatchSnapshot();
  });
});
