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

<template>
  <SchemaFormAdmin :sections="computedSections" full-width>
    <template v-slot:sections="{ sections, renderSection }">
      <div class="tui-mod_approval-workflowFormAdmin__sections">
        <template v-for="section in sections">
          <div
            :key="section.key"
            class="tui-mod_approval-workflowFormAdmin__section"
            :class="{
              'tui-mod_approval-workflowFormAdmin__section--hidden': !getSectionVisibility(
                section
              ),
            }"
          >
            <div class="tui-mod_approval-workflowFormAdmin__sectionHeading">
              <h3 class="tui-mod_approval-workflowFormAdmin__sectionTitle">
                {{ section.title || sectionTitle(section) }}
              </h3>
              <ToggleSet
                v-if="editable"
                :aria-label="
                  $str(
                    'visibility_of_section_x',
                    'mod_approval',
                    section.title || sectionTitle(section)
                  )
                "
                :value="getSectionVisibility(section)"
                @input="updateSectionVisibility(section, $event)"
              >
                <ToggleButton
                  :value="true"
                  :text="$str('visible', 'mod_approval')"
                  :aria-label="$str('visible', 'mod_approval')"
                >
                  <VisibleIcon />
                </ToggleButton>
                <ToggleButton
                  :value="false"
                  :text="$str('hidden', 'mod_approval')"
                  :aria-label="$str('hidden', 'mod_approval')"
                >
                  <HiddenIcon />
                </ToggleButton>
              </ToggleSet>
            </div>
            <div
              v-if="getSectionVisibility(section)"
              class="tui-mod_approval-workflowFormAdmin__sectionContent"
            >
              <render :vnode="renderSection(section)" />
            </div>
          </div>
        </template>
      </div>
    </template>

    <template v-slot:rows="{ fields, renderRows }">
      <div class="tui-mod_approval-workflowFormAdmin__rows">
        <render :vnode="renderRows(fields)" />
      </div>
    </template>

    <template v-slot:row="{ field, renderRow }">
      <div
        class="tui-mod_approval-workflowFormAdmin__row"
        :class="{
          'tui-mod_approval-workflowFormAdmin__row--readonly':
            getFieldVisibility(field) === FormviewVisibility.READ_ONLY,
          'tui-mod_approval-workflowFormAdmin__row--hidden':
            getFieldVisibility(field) === FormviewVisibility.HIDDEN,
        }"
      >
        <render :vnode="renderRow(field)" />
      </div>
    </template>

    <template v-slot:actions="{ field, supports }">
      <Dropdown v-if="editable" :separator="false" position="bottom-right">
        <template v-slot:trigger="{ toggle, isOpen }">
          <ButtonIcon
            :aria-expanded="String(isOpen)"
            :aria-label="
              $str(
                'field_visibility_options_for_x_name_value',
                'mod_approval',
                {
                  name: field.label,
                  value: getVisibilityOption(
                    getFieldVisibility(field),
                    supports
                  ).label,
                }
              )
            "
            :caret="true"
            :text="
              getVisibilityOption(getFieldVisibility(field), supports).label
            "
            :styleclass="{ stealth: true, small: true }"
            @click="toggle"
          >
            <component
              :is="
                getVisibilityOption(getFieldVisibility(field), supports).icon
              "
            />
          </ButtonIcon>
        </template>
        <DropdownItem
          v-for="option in getVisibilityOptions(supports)"
          :key="option.value"
          @click="updateFieldVisibility(field, option.value)"
        >
          <div
            class="tui-mod_approval-workflowFormAdmin__visibilityOption"
            :class="{
              'tui-mod_approval-workflowFormAdmin__visibilityOption--selected':
                getFieldVisibility(field) === option.value,
            }"
          >
            <div
              class="tui-mod_approval-workflowFormAdmin__visibilityOption-label"
            >
              <component :is="option.icon" />
              <div
                class="tui-mod_approval-workflowFormAdmin__visibilityOption-text"
              >
                {{ option.label }}
              </div>
            </div>
            <div
              class="tui-mod_approval-workflowFormAdmin__visibilityOption-selectedMark"
            >
              <SelectedIcon :alt="$str('selected', 'totara_core')" />
            </div>
          </div>
        </DropdownItem>
      </Dropdown>
      <div
        v-else
        class="tui-mod_approval-workflowFormAdmin__readonlyVisibilityOption"
      >
        <component
          :is="getVisibilityOption(getFieldVisibility(field), supports).icon"
        />
        <div
          class="tui-mod_approval-workflowFormAdmin__readonlyVisibilityOption-label"
        >
          {{ getVisibilityOption(getFieldVisibility(field), supports).label }}
        </div>
      </div>
    </template>
  </SchemaFormAdmin>
</template>

<script>
import { produce } from 'tui/immutable';
import Button from 'tui/components/buttons/Button';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import EditableIcon from 'tui/components/icons/Editable';
import HiddenIcon from 'tui/components/icons/Hidden';
import ReadOnlyIcon from 'tui/components/icons/ReadOnly';
import RequiredIcon from 'tui/components/icons/Required';
import SelectedIcon from 'tui/components/icons/Selected';
import VisibleIcon from 'tui/components/icons/Visible';
import ToggleButton from 'tui/components/toggle/ToggleButton';
import ToggleSet from 'tui/components/toggle/ToggleSet';
import { FormviewVisibility } from 'mod_approval/constants';
import SchemaFormAdmin from 'mod_approval/components/schema_form/SchemaFormAdmin';

export default {
  components: {
    Button,
    ButtonIcon,
    Dropdown,
    DropdownItem,
    HiddenIcon,
    SelectedIcon,
    VisibleIcon,
    ToggleButton,
    ToggleSet,
    SchemaFormAdmin,
  },

  props: {
    sections: {
      type: Array,
      required: true,
    },
    fieldConfig: {
      type: Object,
      required: true,
    },
    sectionConfig: {
      type: Object,
      required: true,
    },
    editable: {
      type: Boolean,
      required: true,
    },
  },

  data() {
    return {
      FormviewVisibility,
      visibilityOptions: {
        [FormviewVisibility.EDITABLE]: {
          value: FormviewVisibility.EDITABLE,
          label: this.$str('field_visibility_editable', 'mod_approval'),
          icon: EditableIcon,
        },
        [FormviewVisibility.EDITABLE_AND_REQUIRED]: {
          value: FormviewVisibility.EDITABLE_AND_REQUIRED,
          label: this.$str(
            'field_visibility_editable_and_required',
            'mod_approval'
          ),
          icon: RequiredIcon,
        },
        [FormviewVisibility.READ_ONLY]: {
          value: FormviewVisibility.READ_ONLY,
          label: this.$str('field_visibility_read_only', 'mod_approval'),
          icon: ReadOnlyIcon,
        },
        [FormviewVisibility.HIDDEN]: {
          value: FormviewVisibility.HIDDEN,
          label: this.$str('field_visibility_hidden', 'mod_approval'),
          icon: HiddenIcon,
        },
      },
      // Alternate version of "Editable" for field types that don't support editing
      visibleOption: {
        value: FormviewVisibility.EDITABLE,
        label: this.$str('field_visibility_visible', 'mod_approval'),
        icon: VisibleIcon,
      },
    };
  },

  computed: {
    computedSections() {
      if (!this.sections) {
        return [];
      }
      return produce(this.sections, sections => {
        sections.forEach(section => {
          if (section.fields) {
            section.fields.forEach(field => {
              field.required =
                this.getFieldVisibility(field) ===
                FormviewVisibility.EDITABLE_AND_REQUIRED;
            });
          }
        });
        if (!this.editable) {
          sections = sections.filter(x => this.getSectionVisibility(x));
        }
        return sections;
      });
    },
  },

  methods: {
    sectionTitle(section) {
      return `${section.line} - ${section.label}`;
    },

    getFieldConfig(field) {
      return this.fieldConfig[field.key] || {};
    },

    getFieldVisibility(field) {
      return this.getFieldConfig(field).visibility || FormviewVisibility.HIDDEN;
    },

    getVisibilityOption(value, supports) {
      if (value === FormviewVisibility.EDITABLE && supports.edit === false) {
        return this.visibleOption;
      }
      return this.visibilityOptions[value] || {};
    },

    updateFieldConfig(field, newValues) {
      const prev = this.fieldConfig[field.key];
      const updated = Object.assign({}, prev || {}, newValues);
      this.$emit('update-field-config', field.key, updated, prev);
    },

    updateFieldVisibility(field, value) {
      this.updateFieldConfig(field, { visibility: value });
    },

    getSectionVisibility(section) {
      const config = this.sectionConfig[section.key] || {};
      const value = config.visible;
      return value == null || Boolean(value);
    },

    updateSectionVisibility(section, value) {
      const prev = this.sectionConfig[section.key];
      const updated = Object.assign({}, prev || { visible: true }, {
        visible: value,
      });
      this.$emit('update-section-config', section.key, updated, prev);
    },

    getVisibilityOptions(supports) {
      const options = [];
      if (supports.edit === false) {
        options.push(this.visibleOption);
      } else {
        options.push(this.visibilityOptions[FormviewVisibility.EDITABLE]);
        options.push(
          this.visibilityOptions[FormviewVisibility.EDITABLE_AND_REQUIRED]
        );
      }
      if (supports.disable !== false) {
        options.push(this.visibilityOptions[FormviewVisibility.READ_ONLY]);
      }
      options.push(this.visibilityOptions[FormviewVisibility.HIDDEN]);
      return options;
    },
  },
};
</script>

<lang-strings>
{
  "mod_approval": [
    "field_visibility_editable_and_required",
    "field_visibility_editable",
    "field_visibility_hidden",
    "field_visibility_options_for_x_name_value",
    "field_visibility_read_only",
    "field_visibility_visible",
    "hidden",
    "visibility_of_section_x",
    "visible"
  ],
  "totara_core": [
    "selected"
  ]
}
</lang-strings>

<style lang="scss">
.tui-mod_approval-workflowFormAdmin {
  &__sections {
    @include tui-stack-vertical(var(--gap-4));
  }

  &__section {
    &--hidden {
      background: repeating-linear-gradient(
        -45deg,
        var(--color-neutral-3),
        var(--color-neutral-3) 3rem,
        transparent 3rem,
        transparent 5rem
      );
    }
  }

  &__sectionHeading {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    padding: var(--gap-2);
  }

  &__sectionTitle {
    flex-grow: 1;
    margin: 0;
    @include tui-font-heading-small();
  }

  &__sectionContent {
    padding: var(--gap-2) 0 var(--gap-8) 0;
  }

  &__rows {
    @include tui-stack-vertical(var(--gap-2));
  }

  &__row {
    padding: var(--gap-2);

    &--readonly {
      background: var(--color-neutral-2);
    }
    &--hidden {
      background: repeating-linear-gradient(
        -45deg,
        var(--color-neutral-3),
        var(--color-neutral-3) 3rem,
        transparent 3rem,
        transparent 5rem
      );
    }
  }

  &__visibilityOption {
    display: flex;

    &-label {
      display: flex;
      flex-grow: 1;
      align-items: center;
    }

    &-text {
      margin-left: var(--gap-2);
    }

    &-selectedMark {
      margin-left: var(--gap-4);
      visibility: hidden;
    }
  }

  &__visibilityOption--selected &__visibilityOption-selectedMark {
    visibility: visible;
  }

  &__readonlyVisibilityOption {
    display: flex;
    align-items: center;
    height: var(--form-input-height);

    &-label {
      margin-left: var(--gap-1);
    }
  }
}
</style>
