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
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module mod_approval
 */

import LayoutPrintColumn from 'mod_approval/components/schema_form/print/LayoutPrintColumn';
import Field from 'mod_approval/components/schema_form/print/layout_cells/Field';
import Title from 'mod_approval/components/schema_form/print/layout_cells/Title';
import Label from 'mod_approval/components/schema_form/print/layout_cells/Label';
import SectionLabel from 'mod_approval/components/schema_form/print/layout_cells/SectionLabel';
import Approvals from 'mod_approval/components/schema_form/print/layout_cells/Approvals';
import { prepareApprovers } from '../fields/approvals';
import { MM_PX, PX_PT } from '../constants';

export default {
  column: {
    getComponent(item, context) {
      return {
        component: LayoutPrintColumn,
        props: {
          rows: item.rows,
          context: context,
          inField: true,
        },
      };
    },
  },

  field: {
    getComponent(item, context) {
      const field = context.getField(item.field);
      if (!field) {
        return {};
      }

      const scale = context.getOption('scale') || 1;
      const minFont = context.getOption('min_font_size');

      const maxLabel = context.getEntryOption(
        'field',
        item,
        'max_label_height'
      );
      const maxContent = context.getEntryOption(
        'field',
        item,
        'max_content_height'
      );

      return {
        component: Field,
        props: {
          field,
          showLabel: item.label !== false,
          valueText: field.resolved.valueText,
          valueComponent: field.resolved.component
            ? {
                component: field.resolved.component,
                props: field.resolved.props,
              }
            : null,
          minFontSize: minFont ? minFont / PX_PT : 9,
          maxLabelHeight: maxLabel ? maxLabel * MM_PX * scale : null,
          maxContentHeight: maxContent ? maxContent * MM_PX * scale : null,
          showLineNumber: context.getEntryOption('field', item, 'line_number'),
        },
        disabled: field.disabled,
      };
    },
  },

  title: {
    getComponent(item) {
      return { component: Title, props: { text: item.text } };
    },
  },

  label: {
    getComponent(item, context) {
      const scale = context.getOption('scale') || 1;
      const minFont = context.getOption('min_font_size');
      const maxLabel = context.getEntryOption(
        'field',
        item,
        'max_label_height'
      );

      return {
        component: Label,
        props: {
          text: item.text,
          minFontSize: minFont ? minFont / PX_PT : 9,
          maxLabelHeight: maxLabel ? maxLabel * MM_PX * scale : null,
        },
      };
    },
  },

  section_label: {
    getComponent(item, context) {
      let text;
      if (item.text) {
        text = item.text;
      } else {
        const section = context.schema.sections.find(
          x => x.key === item.section
        );
        if (!section) {
          return {};
        }
        const line = context.getEntryOption('section', item, 'line_number');
        text = (line ? section.line + ' - ' : '') + section.label;
      }
      return {
        component: SectionLabel,
        props: {
          text,
        },
      };
    },
  },

  approvals: {
    getComponent(item, context) {
      const approvers = context.getExtraData('approvers') || [];
      return {
        component: Approvals,
        props: {
          approvers: prepareApprovers(approvers, item.date_format),
        },
      };
    },
  },
};
