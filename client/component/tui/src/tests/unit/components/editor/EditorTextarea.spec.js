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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @module tui
 */

import { shallowMount } from '@vue/test-utils';
import EditorTextarea from 'tui/components/editor/EditorTextarea';

describe('EditorTextarea', () => {
  let wrapper = {};

  beforeEach(() => {
    wrapper = shallowMount(EditorTextarea, {
      propsData: {
        value: { content: 'text value', format: 1 },
      },
    });
  });

  it('matches snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });
});
