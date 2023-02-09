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

import TopicsSelector from 'totara_engage/components/form/access/EngageTopicsSelector';
import { shallowMount } from '@vue/test-utils';

describe('Engage EngageTopicsSelector', function() {
  it('Checks snapshot', () => {
    let wrapper = shallowMount(TopicsSelector, {
      mocks: {
        $str(id, component) {
          return `${id}, ${component}`;
        },
        $id: x => 'id-' + (x || 'topic'),
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });
});
