/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @module totara_comment
 */

import { shallowMount } from '@vue/test-utils';
import { SIZE_SMALL } from 'totara_comment/size';
import ReplyBox from 'totara_comment/components/box/ReplyBox.vue';

jest.mock('tui/apollo_client', () => null);
jest.mock('tui/tui', () => null);

describe('ReplyBox', () => {
  const propsData = {
    component: 'totara_comment',
    area: 'comment',
    commentId: 15,
    totalReplies: 1,
    size: SIZE_SMALL,
    showReplyForm: true,
    replyAble: true,
  };

  const mocks = {
    $apollo: {
      loading: false,

      queries: {
        replies: {
          loading: false,
        },
      },
    },

    $str(x, y) {
      return `${x}-${y}`;
    },
  };

  const data = () => ({
    replies: [
      {
        edited: false,
        deleted: false,
        id: 12,
        commentid: 11,
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
        interactor: {
          can_update: true,
          can_delete: true,
          can_report: true,
          can_follow_reply: true,
          can_react: true,
          can_view_author: true,
        },
        totalreactions: 56,
      },
    ],
  });

  it('Checks snapshot', () => {
    const wrapper = shallowMount(ReplyBox, { propsData, mocks, data });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks snapshot without thumbs up', () => {
    const wrapper = shallowMount(ReplyBox, {
      propsData: Object.assign({}, propsData, { showLikeButton: false }),
      mocks,
      data,
    });
    expect(wrapper.element).toMatchSnapshot();
  });
});
