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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

import { shallowMount, mount } from '@vue/test-utils';
import Arrow from 'tui/components/decor/Arrow';
import { isRtl } from 'tui/i18n';

describe('Arrow', () => {
  beforeAll(() => {
    isRtl.mockReset().mockReturnValue(false);
  });

  it('matches snapshot', () => {
    const wrapper = shallowMount(Arrow, { propsData: { side: 'top' } });
    expect(wrapper.element).toMatchSnapshot();
  });

  it('RTL works as expected', () => {
    let wrapper = mount(Arrow, { propsData: { side: 'top', distance: 12 } });
    let props = wrapper.find('.tui-arrow').attributes().style;
    expect(props).toEqual('left: 12px;');

    wrapper = mount(Arrow, { propsData: { side: 'bottom', distance: 13 } });
    props = wrapper.find('.tui-arrow').attributes().style;
    expect(props).toEqual('left: 13px;');

    wrapper = mount(Arrow, { propsData: { side: 'left', distance: 14 } });
    props = wrapper.find('.tui-arrow').attributes().style;
    expect(props).toEqual('top: 14px;');

    wrapper = mount(Arrow, { propsData: { side: 'right', distance: 15 } });
    props = wrapper.find('.tui-arrow').attributes().style;
    expect(props).toEqual('top: 15px;');

    // And now Rtl
    isRtl.mockImplementation(() => true);
    wrapper = shallowMount(Arrow, { propsData: { side: 'top', distance: 16 } });
    props = wrapper.find('.tui-arrow').attributes().style;
    expect(props).toEqual('right: 16px;');

    wrapper = mount(Arrow, { propsData: { side: 'bottom', distance: 17 } });
    props = wrapper.find('.tui-arrow').attributes().style;
    expect(props).toEqual('right: 17px;');

    wrapper = mount(Arrow, { propsData: { side: 'left', distance: 18 } });
    props = wrapper.find('.tui-arrow').attributes().style;
    expect(props).toEqual('top: 18px;');

    wrapper = mount(Arrow, { propsData: { side: 'right', distance: 19 } });
    props = wrapper.find('.tui-arrow').attributes().style;
    expect(props).toEqual('top: 19px;');
  });
});
