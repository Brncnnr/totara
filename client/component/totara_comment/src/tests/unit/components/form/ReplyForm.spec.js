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

import ReplyForm from 'totara_comment/components/form/ReplyForm';
import { shallowMount } from '@vue/test-utils';

jest.mock('tui/apollo_client', () => null);
jest.mock('tui/tui', () => null);

describe('ReplyForm', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(ReplyForm, {
      propsData: {
        commentId: 14,
      },

      mocks: {
        $str(x, y) {
          return `${x}-${y}`;
        },

        $url(path, params) {
          return `${path}/${params.toString()}`;
        },

        $apollo: {
          queries: {
            editorOption: {
              loading: false,
            },
          },
          mutate() {
            return Promise.resolve({ data: { item_id: 100 } });
          },
        },
      },

      data() {
        return {
          user: {
            id: 15,
            fullname: 'Damin seru',
            profileimageurl: 'http://example.com',
          },
        };
      },
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
