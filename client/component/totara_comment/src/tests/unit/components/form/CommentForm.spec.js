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
 * @module totara_comment
 */
import CommentForm from 'totara_comment/components/form/CommentForm';
import { shallowMount } from '@vue/test-utils';

jest.mock('tui/apollo_client', () => null);
jest.mock('tui/tui', () => null);

describe('CommentForm', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(CommentForm, {
      propsData: {
        usageIdentifier: {
          component: 'totara_comment',
          area: 'comment',
          instanceId: 42,
        },
      },

      data() {
        return {
          user: {
            id: 15,
            fullname: 'Admin user',
            profileimageurl: 'http://example.com',
            profileimagealt: 'Text',
          },
        };
      },

      mocks: {
        $url(path, params) {
          return `${path}/${params.toString()}`;
        },

        $str(x, y) {
          return `${x}-${y}`;
        },

        $apollo: {
          queries: {
            editorOption: {
              loading: false,
            },
          },

          mutate() {
            return Promise.resolve({ data: { item_id: 500 } });
          },
        },
      },
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
