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
 * @module totara_engage
 */

import { shallowMount } from '@vue/test-utils';
import NameListModal from 'totara_engage/components/modal/NameListModal';

describe('Engage NameListModal', function() {
  let mocks = null;

  beforeAll(function() {
    mocks = {
      $str(id, component) {
        return `${id}, ${component}`;
      },
    };
  });

  it('Matchs snapshot', () => {
    let wrapper = shallowMount(NameListModal, {
      mocks,
      propsData: {
        title: 'You like me?',
        allLoaded: false,
        loading: true,
        profiles: [
          { id: 1, name: 'John', src: 'url' },
          { id: 2, name: 'Smith', src: 'url1' },
          { id: 3, name: 'at', src: 'url2' },
          { id: 4, name: 'home', src: 'url3' },
        ],
      },
    });

    expect(wrapper.element).toMatchSnapshot();
  });
});
