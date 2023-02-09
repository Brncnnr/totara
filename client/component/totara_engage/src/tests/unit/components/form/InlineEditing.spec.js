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
 * @module totara_engage
 */

import InlineEditing from 'totara_engage/components/form/InlineEditing';
import { shallowMount } from '@vue/test-utils';
const eventFunc = jest.fn();

describe('Engage InlineEditing', function() {
  it('Checks snapshot', () => {
    let wrapper = shallowMount(InlineEditing, {
      mocks: {
        $str(id, component) {
          return `${id}, ${component}`;
        },
      },
      propsData: {
        updateAble: true,
      },
      directives: {
        'focus-within': eventFunc,
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });
});
