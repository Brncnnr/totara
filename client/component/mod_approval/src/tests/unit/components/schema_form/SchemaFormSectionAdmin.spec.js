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
 * @author Simon Tegg <simon.tegg@totaralearning.com>
 * @module mod_approval
 */
import simpleMount from './simple_mount';
import SchemaFormSectionAdmin from 'mod_approval/components/schema_form/SchemaFormSectionAdmin';
import form from '../../../fixtures/test_form.json';
import { axe, toHaveNoViolations } from 'jest-axe';
expect.extend(toHaveNoViolations);

jest.mock('tui/tui', function() {
  return {
    import: async function(path) {
      return require(path);
    },

    defaultExport(x) {
      return x.default || x;
    },

    loadRequirements: () => new Promise(resolve => resolve()),
  };
});

jest.mock('tui/apollo_client', function() {
  return {
    query() {
      return Promise.resolve({
        data: {
          editor: {
            context_id: 1,
            name: 'textarea',
          },
        },
      });
    },
  };
});

const key = 'training_course';
const help = 'the title';
const propsData = {
  fields: [
    {
      key,
      type: 'text',
      label: 'Course title',
      required: true,
      help,
    },
  ],
};

describe('SchemaFormSctionAdmin', () => {
  it('should render the form field with help text', () => {
    const wrapper = simpleMount(SchemaFormSectionAdmin, propsData);
    const helpEl = wrapper.find('[role="tooltip"]');
    expect(helpEl.exists()).toBe(true);
    expect(helpEl.text()).toBe(help);
  });

  form.sections.forEach(section => {
    it(`Section: ${section.key} should not have any accessibility violations`, async () => {
      const wrapper = simpleMount(SchemaFormSectionAdmin, {
        fields: section.fields,
      });
      const results = await axe(wrapper.element, {
        rules: {
          region: { enabled: false },
        },
      });
      expect(results).toHaveNoViolations();
    });
  });
});
