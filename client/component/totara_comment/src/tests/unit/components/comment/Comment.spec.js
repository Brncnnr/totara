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

import Comment from 'totara_comment/components/comment/Comment';
import { shallowMount } from '@vue/test-utils';

jest.mock('tui/apollo_client', () => null);
jest.mock('tui/tui', () => null);

describe('Comment', function() {
  const propsData = {
    content: 'Hello world',
    userFullName: 'xx fwf',
    userId: 15,
    userProfileImageUrl: 'http://example.com',
    updateAble: true,
    deleteAble: true,
    totalReplies: 21,
    reportAble: false,
    timeDescription: '5th September 2019',
    commentId: 42,
    component: 'totara_comment',
    area: 'comment',
    instanceId: 22,
    edited: false,
    deleted: false,
  };

  const mocks = {
    $str(id, component) {
      return `${id}, ${component}`;
    },

    $url(url, params) {
      return `${url}?${params.toString()}`;
    },

    $apollo: {
      queries: {
        replies: {
          loading: false,
        },
      },
    },
  };

  it('Checks snapshot', function() {
    const wrapper = shallowMount(Comment, { propsData, mocks });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks snapshot without thumbs up', () => {
    const wrapper = shallowMount(Comment, {
      propsData: Object.assign({}, propsData, { showLikeButton: false }),
      mocks,
    });
    expect(wrapper.element).toMatchSnapshot();
  });
});
