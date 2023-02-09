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

import Reply from 'totara_comment/components/reply/Reply';
import { shallowMount } from '@vue/test-utils';

jest.mock('tui/apollo_client', () => null);

describe('Reply', () => {
  const propsData = {
    userFullName: 'Admin user',
    content: 'Hello world',
    updateAble: true,
    deleteAble: true,
    timeDescription: '5th of September 1996',
    userProfileImageUrl: 'http://example.com',
    reportAble: false,
    userId: 42,
    replyId: 12,
    commentId: 45,
    edited: false,
    deleted: false,
    component: 'totara_comment',
  };

  const mocks = {
    $url(path) {
      return path;
    },

    $str(x, y) {
      return `${x}-${y}`;
    },
  };

  it('Checks snapshot', () => {
    const wrapper = shallowMount(Reply, { propsData, mocks });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Checks snapshot without thumbs up', () => {
    const wrapper = shallowMount(Reply, {
      propsData: Object.assign({}, propsData, { showLikeButton: false }),
      mocks,
    });
    expect(wrapper.element).toMatchSnapshot();
  });
});
