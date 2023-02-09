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
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @module editor_weka
 */

import EmojiSelector from 'editor_weka/components/editing/EmojiSelector';
import { shallowMount } from '@vue/test-utils';

describe('EmojiSelector', function() {
  let wrapper;

  beforeAll(() => {
    wrapper = shallowMount(EmojiSelector, {
      propsData: {
        emojis: [
          {
            id: 1,
            shortcode: '1F641',
          },
        ],
      },
    });
  });

  it('matches snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
