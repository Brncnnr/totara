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
import { unique } from 'tui/util';
import Loader from 'tui/components/loading/Loader';
import { Uniform } from 'tui/components/uniform';
import SchemaFormSectionAdmin from 'mod_approval/components/schema_form/SchemaFormSectionAdmin';
import { loadSchemaData } from 'mod_approval/schema_form';
import { getFieldDef } from '../../js/internal/schema_form/defs';
import { getAllFields } from '../../js/internal/schema_form/schema';
import { produce } from 'tui/immutable';

export default {
  components: {
    Uniform,
    SchemaFormSectionAdmin,
  },

  props: {
    display: {
      type: Boolean,
      default: true,
    },
    sections: Array,
    spacing: String,
    fullWidth: Boolean,
  },

  data() {
    return {
      loaded: false,
      formState: { values: {} },
    };
  },

  mounted() {
    this.$_prepare();
  },

  methods: {
    /**
     * Set up the form.
     *
     * - make sure we have the components we need available
     * - load resources for the components
     *
     * @private
     */
    async $_prepare() {
      const schema = { sections: this.sections };
      await loadSchemaData(schema);
      // load component requirements
      const fieldTypes = unique(getAllFields(schema).map(x => x.type));
      const comps = [];
      fieldTypes.forEach(type => {
        const spec = getFieldDef(type);
        if (spec && spec.rowComponent) {
          comps.push(spec.rowComponent);
        }
        if (spec && spec.fieldComponent) {
          comps.push(spec.fieldComponent);
        }
      });
      await Promise.all(comps.map(comp => tui.loadRequirements(comp)));
      this.loaded = true;
      this.$emit('loaded');
    },

    handleStateUpdate(state) {
      this.formState = state;
      // Clear out form state after every change to prevent setting/changing
      // the value of any inputs (not all inputs support readonly).
      // Vue doesn't support real controlled inputs, so we need to let the
      // inputs see the new value being passed in before reverting it.
      // Otherwise -- no change, no rerender with the value from the prop.
      this.$nextTick(() => {
        if (Object.keys(this.formState.values).length > 0) {
          this.formState = produce(this.formState, draft => {
            draft.values = {};
          });
        }
      });
    },
  },

  render(h) {
    if (!this.display) {
      return h(Loader, { props: { loading: false } });
    }

    if (!this.loaded) {
      return h(Loader, { props: { loading: true } });
    }

    const renderSection = section => {
      return h(SchemaFormSectionAdmin, {
        key: section.key,
        props: {
          fields: section.fields,
          spacing: this.spacing,
          fullWidth: this.fullWidth,
        },
        scopedSlots: {
          rows: this.$scopedSlots.rows,
          row: this.$scopedSlots.row,
          field: this.$scopedSlots.field,
          actions: this.$scopedSlots.actions,
        },
      });
    };

    const renderSections = sections =>
      sections.map(section =>
        this.$scopedSlots.section
          ? this.$scopedSlots.section({
              section,
              renderSection,
            })
          : renderSection(section)
      );

    return h(
      Uniform,
      {
        props: { state: this.formState },
        on: { 'update:state': this.handleStateUpdate },
      },
      [
        this.$scopedSlots.sections
          ? this.$scopedSlots.sections({
              renderSection,
              renderSections,
              sections: this.sections,
            })
          : renderSections(this.sections),
        this.$scopedSlots.below && this.$scopedSlots.below(),
      ]
    );
  },
};
</script>
