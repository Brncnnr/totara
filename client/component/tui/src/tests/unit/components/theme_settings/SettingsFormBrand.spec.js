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
import SettingsFormBrand from 'tui/components/theme_settings/SettingsFormBrand';

describe('SettingsFormBrand', () => {
  let wrapper = {};

  beforeEach(() => {
    wrapper = shallowMount(SettingsFormBrand, {
      propsData: {
        fileFormFieldData: [{ ui_key: 'sitelogo' }, { ui_key: 'sitefavicon' }],
      },
    });
  });

  it('matches snapshot', () => {
    expect(wrapper.element).toMatchSnapshot();
  });

  it('Rows function works as expected', () => {
    let name = 'formbrand_field_notificationstextfooter';
    wrapper.vm.initialValues[name].value = '\n\n\n\n';

    expect(wrapper.vm.rows(name, 5, 10)).toBe(5);
    expect(wrapper.vm.rows(name, 1, 10)).toBe(5);
    expect(wrapper.vm.rows(name, 1, 3)).toBe(3);

    wrapper.vm.valuesForm = {
      formbrand_field_notificationstextfooter: {
        value: '\n\n',
      },
    };
    expect(wrapper.vm.rows(name, 5, 10)).toBe(5);
    expect(wrapper.vm.rows(name, 1, 10)).toBe(3);
    expect(wrapper.vm.rows(name, 1, 2)).toBe(2);
  });
});
