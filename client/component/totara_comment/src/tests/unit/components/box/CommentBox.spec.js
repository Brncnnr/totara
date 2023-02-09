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

import CommentBox from 'totara_comment/components/box/CommentBox';
import { shallowMount } from '@vue/test-utils';
import { SIZE_SMALL } from 'totara_comment/size';

jest.mock('tui/apollo_client', () => null);
jest.mock('tui/tui', () => null);

describe('CommentBox', () => {
  const propsData = {
    component: 'totara_comment',
    area: 'comment',
    instanceId: 15,
    size: SIZE_SMALL,
  };

  const mocks = {
    $apollo: {
      loading: false,

      queries: {
        comments: {
          loading: false,
        },

        totalComments: {
          loading: false,
        },
      },
    },

    $str(x, y) {
      return `${x}-${y}`;
    },
  };

  const data = () => ({
    totalComments: 1,
    comments: [
      {
        edited: false,
        deleted: false,
        id: 12,
        content: 'Hello world',
        updateable: true,
        deleteable: true,
        totalreplies: 42,
        reportable: false,
        timedescription: '5th of September 1996',
        user: {
          id: 42,
          fullname: 'Admin user',
          profileimageurl: 'http://example.com',
          profileimagealt: 'Hello world',
        },
      },
    ],
  });

  it('Checks snapshot', () => {
    const wrapper = shallowMount(CommentBox, { propsData, mocks, data });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks snapshot without thumbs up', () => {
    const wrapper = shallowMount(CommentBox, {
      propsData: Object.assign({}, propsData, { showLikeButton: false }),
      mocks,
      data,
    });
    expect(wrapper.element).toMatchSnapshot();
  });
});
