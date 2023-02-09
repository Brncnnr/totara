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
 * @module engage_article
 */

import EditArticleTitleForm from 'engage_article/components/form/EditArticleTitleForm';
import { shallowMount } from '@vue/test-utils';

describe('Engage EditArticleTitleForm', function() {
  let wrapper = null;

  beforeAll(function() {
    wrapper = shallowMount(EditArticleTitleForm, {
      mocks: {
        $str(identifier, component) {
          return `${identifier}, ${component}`;
        },
      },
      propsData: {
        title: 'ArticleTitle',
      },
    });
  });

  it('Checks snapshot', function() {
    expect(wrapper.html()).toMatchSnapshot();
  });
});
