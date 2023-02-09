<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2021 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module mod_approval
-->

<script>
import FormRow from 'tui/components/form/FormRow';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import FormRowStack from 'tui/components/form/FormRowStack';
import {
  getFieldDef,
  getFieldDefSupports,
} from '../../js/internal/schema_form/defs';

export default {
  components: {
    FormRow,
    FormRowDetails,
    FormRowStack,
  },

  props: {
    fields: {
      type: Array,
      required: true,
    },
    spacing: String,
    fullWidth: Boolean,
  },

  render(h) {
    const renderField = field => {
      const spec = getFieldDef(field.type);
      if (!spec) {
        return null;
      }
      const comp = spec && spec.fieldComponent;
      field = Object.assign({}, field, {
        disabled: false,
      });
      return h(comp, { props: { field, view: 'admin', readonly: true } });
    };

    const renderRow = field => {
      const spec = getFieldDef(field.type);
      if (!spec) {
        return null;
      }
      const rowComp = spec && spec.rowComponent;
      const supports = getFieldDefSupports(spec);
      const descId = field.instruction ? this.$id(field.key + '-desc') : '';

      const rowVnode = rowComp
        ? h(rowComp, {
            key: field.key,
            props: {
              heading: field.label,
              view: 'admin',
              readonly: true,
            },
          })
        : h(
            FormRow,
            {
              key: field.key,
              props: {
                label: field.label,
                required: field.required,
                ariaDescribedby: descId,
                fullWidth: this.fullWidth,
              },
              scopedSlots: {
                ['help-message']:
                  field.help || field.help_html
                    ? () =>
                        field.help_html
                          ? h('div', {
                              domProps: { innerHTML: field.help_html },
                            })
                          : field.help
                    : null,
              },
            },
            [
              this.$scopedSlots.field
                ? this.$scopedSlots.field({ field, renderField })
                : renderField(field),
              field.instruction
                ? h(FormRowDetails, { attrs: { id: descId } }, [
                    field.instruction,
                  ])
                : null,
            ]
          );

      return h(
        'div',
        { class: 'tui-mod_approval-schemaFormSectionAdmin__adminRow' },
        [
          h(
            'div',
            { class: 'tui-mod_approval-schemaFormSectionAdmin__rowWrap' },
            [rowVnode]
          ),
          h(
            'div',
            { class: 'tui-mod_approval-schemaFormSectionAdmin__rowControls' },
            this.$scopedSlots.actions &&
              this.$scopedSlots.actions({ field, supports })
          ),
        ]
      );
    };

    const renderRows = fields =>
      fields &&
      fields.reduce((acc, field) => {
        // only render configuration for field types that exist
        if (getFieldDef(field.type)) {
          acc.push(
            this.$scopedSlots.row
              ? this.$scopedSlots.row({ field, renderRow })
              : renderRow(field)
          );
        }
        return acc;
      }, []);

    return h(
      FormRowStack,
      { props: { spacing: this.spacing } },
      this.$scopedSlots.rows
        ? this.$scopedSlots.rows({ fields: this.fields, renderRows })
        : renderRows(this.fields)
    );
  },
};
</script>

<style lang="scss">
.tui-mod_approval-schemaFormSectionAdmin {
  &__adminRow {
    display: flex;
    flex-wrap: wrap;
  }

  &__rowWrap {
    flex-grow: 1;
    margin-right: var(--gap-4);
  }
}
</style>
