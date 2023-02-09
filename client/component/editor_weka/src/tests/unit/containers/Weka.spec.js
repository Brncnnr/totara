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
 * @module editor_weka
 */

import { mount, shallowMount } from '@vue/test-utils';
import Toolbar from 'editor_weka/components/toolbar/Toolbar';
import Weka from 'editor_weka/components/Weka';

// Mock tui.import
jest.mock('tui/tui', function() {
  return {
    import: async function(id) {
      switch (id) {
        case 'editor_weka/extensions/link':
          return require('../../../js/extensions/link');

        case 'editor_weka/extensions/text':
          return require('../../../js/extensions/text');

        default:
          throw `No module found for id ${id}`;
      }
    },

    defaultExport(x) {
      return x.default || x;
    },

    loadRequirements: () => new Promise(resolve => resolve()),
  };
});

jest.mock('editor_weka/api', () => ({
  __esModule: true,
  getLinkMetadata: () => null,
}));

const EXTENSIONS = [
  {
    name: 'link',
    tuicomponent: 'editor_weka/extensions/link',
  },
  {
    name: 'text',
    tuicomponent: 'editor_weka/extensions/text',
  },
];

/**
 *
 * @param {Object} option
 * @param {Number} instanceId
 * @param {Boolean} compact=false
 * @param {Boolean} useFullMount=false
 * @return {Wrapper<Vue>}
 */
const factory = (option, instanceId, compact, useFullMount) => {
  const mountFunction = useFullMount ? mount : shallowMount;

  return mountFunction(Weka, {
    directives: {
      'focus-within': jest.fn(),
    },
    propsData: {
      options: option,
      variant: 'standard',
      usageIdentifier: {
        component: 'editor_weka',
        area: 'default',
        instanceId: instanceId,
      },
      compact,
      ariaLabel: 'Weka label',
    },
    mocks: {
      $apollo: {
        query: query => {
          if (query.variables.instance_id) {
            return {
              data: { editor: { showtoolbar: true, extensions: EXTENSIONS } },
            };
          }
        },
      },
      uid: 'uid-weka',
    },
  });
};

describe('Weka', () => {
  it('toolbar is showing and render correctly', async () => {
    const wrapper = factory({ extensions: EXTENSIONS }, 42, false);

    // Since the editor would have to be mounted once all the elements within the component rendered, therefore,
    // this test should be waiting for that mounting event to be finished to run the assertion.
    await new Promise(resolve => {
      wrapper.vm.$on('ready', () => {
        resolve('mounted');
      });
    });

    expect(wrapper.find(Toolbar).exists()).toBeTrue();
    expect(wrapper.element).toMatchSnapshot();
  });

  it('toolbar is hidden when compact is set to false no matter extension load or not', async () => {
    const wrapper = factory({ extensions: EXTENSIONS }, null, true);
    wrapper.setData({ toolbarItems: [{ foo: 'bar' }] });
    await new Promise(resolve => {
      wrapper.vm.$on('ready', resolve);
    });
    expect(wrapper.find(Toolbar).exists()).toBeFalse();
  });

  it('toolbar is showing when compact is not set and there are extensions', async () => {
    const wrapper = factory(null, 15);
    wrapper.setData({ toolbarItems: [{ foo: 'bar' }] });
    await new Promise(resolve => {
      wrapper.vm.$on('ready', resolve);
    });
    expect(wrapper.find(Toolbar).exists()).toBeTrue();
  });

  it('pasting links', async () => {
    const wrapper = factory(null, 15, false, true);
    await new Promise(resolve => {
      wrapper.vm.$on('ready', resolve);
    });

    const e = {
      clipboardData: {
        getData() {
          return 'http://example.com user@example.com\r\nhttps://example.com mailto:admin@example.com';
        },
      },
    };

    const proseMirror = wrapper.find('.ProseMirror');
    expect(proseMirror.text()).toBe('');

    proseMirror.trigger('paste', e);

    expect(proseMirror.text()).not.toBe('');

    const links = proseMirror.findAll('a');

    const httpLink = links.wrappers[0];
    expect(httpLink.text()).toBe('http://example.com');
    expect(httpLink.attributes('href')).toBe('http://example.com');

    const emailLink = links.wrappers[1];
    expect(emailLink.text()).toBe('user@example.com');
    expect(emailLink.attributes('href')).toBe('mailto:user@example.com');

    const httpsLink = links.wrappers[2];
    expect(httpsLink.text()).toBe('https://example.com');
    expect(httpsLink.attributes('href')).toBe('https://example.com');

    // Full mailto link, does not get special treatment, the email part of the url will be converted to a link only.
    const fullMailtoUrl = links.wrappers[3];
    expect(fullMailtoUrl.text()).toBe('admin@example.com');
    expect(fullMailtoUrl.attributes('href')).toBe('mailto:admin@example.com');
  });
});
