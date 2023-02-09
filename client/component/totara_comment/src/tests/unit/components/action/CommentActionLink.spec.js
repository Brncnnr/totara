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
import CommentActionLink from 'totara_comment/components/action/CommentActionLink';
import { shallowMount } from '@vue/test-utils';

describe('CommentActionLink', () => {
  let wrapper = null;

  beforeAll(() => {
    wrapper = shallowMount(CommentActionLink, {
      propsData: {
        text: 'Some random txt',
      },

      mocks: {
        $str(x, y) {
          return `${x}-${y}`;
        },
      },
    });
  });

  it('Checks snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
