<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD's customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module mod_perform
-->
<template>
  <Card
    class="tui-performAdminCustomElement"
    :has-hover-shadow="!isActive"
    :class="{
      'tui-performAdminCustomElement--dragging': dragging,
    }"
  >
    <div v-if="draggable" class="tui-performAdminCustomElement__moveIcon">
      <DragHandleIcon />
    </div>

    <div
      v-if="sectionElement.element.element_plugin"
      class="tui-performAdminCustomElement__actions"
    >
      <EditButton
        v-if="!isActive && sectionComponent.type === 'view'"
        class="tui-performAdminCustomElement__actions-item"
        :aria-label="
          $str(
            'edit_element',
            'mod_perform',
            sectionElement.element.title
              ? sectionElement.element.title
              : sectionElement.element.element_plugin.name
          )
        "
        @click="edit"
      />
      <Dropdown
        v-if="!isActive && sectionComponent.type === 'view'"
        position="bottom-right"
        class="tui-performAdminCustomElement__actions-item"
      >
        <template v-slot:trigger="{ toggle }">
          <MoreButton
            :no-padding="true"
            :aria-label="$str('element_action_options', 'mod_perform')"
            @click="toggle"
          />
        </template>
        <DropdownItem v-if="isMultiSectionActive" @click="move">
          {{ $str('move_to_other_section', 'mod_perform') }}
        </DropdownItem>
        <DropdownItem @click="remove">
          {{ $str('delete', 'core') }}
        </DropdownItem>
      </Dropdown>

      <ButtonIcon
        v-if="isActive && sectionComponent.type === 'view'"
        :aria-label="
          $str(
            'setting_element',
            'mod_perform',
            sectionElement.element.title
              ? sectionElement.element.title
              : sectionElement.element.element_plugin.name
          )
        "
        :styleclass="{ transparentNoPadding: true }"
        @click="displayRead"
      >
        <SettingsIcon />
      </ButtonIcon>
    </div>

    <div class="tui-performAdminCustomElement__content">
      <h3
        v-if="sectionComponent.type == 'view' && sectionElement.element.title"
        class="tui-performAdminCustomElement__content-title"
      >
        <span
          v-if="sectionElement.element.element_plugin"
          class="tui-performAdminCustomElement__content-accessibleTitle"
        >
          {{
            $str(
              'element_type_heading_a11y',
              'mod_perform',
              sectionElement.element.element_plugin.name
            )
          }}
        </span>
        <span class="tui-performAdminCustomElement__content-titleText">
          {{ sectionElement.element.title }}
        </span>
        <span
          v-if="sectionElement.element.is_required"
          class="tui-performAdminCustomElement__content-titleRequired"
        >
          *
        </span>
      </h3>

      <component
        :is="sectionComponent.component"
        ref="sectionElements"
        :key="sectionElement.id"
        :activity-state="activityState"
        :aria-hidden="true"
        :data="sectionElement.element.data"
        :element-id="sectionElement.element.id"
        :element-plugins="elementPlugins"
        :identifier="sectionElement.element.identifier"
        :is-required="sectionElement.element.is_required"
        :raw-data="sectionElement.element.raw_data"
        :raw-title="sectionElement.element.raw_title"
        :section="section"
        :section-element="sectionElement"
        :section-id="sectionId"
        :settings="sectionComponent.settings"
        :title="sectionElement.element.title"
        :activity-context-id="activityContextId"
        :current-activity-id="activityId"
        :extra-plugin-config-data="extraPluginConfigData"
        @child-update="$emit('child-update', $event)"
        @display="$emit('display', sectionElement)"
        @display-read="$emit('display-read', sectionElement)"
        @unsaved-child="$emit('unsaved-child', $event)"
        @update="$emit('update', $event)"
      />
    </div>

    <div
      v-if="
        sectionElement.element.identifier &&
          !isActive &&
          sectionComponent.type !== 'editing'
      "
      class="tui-performAdminCustomElement__lozenge"
    >
      <Lozenge :text="sectionElement.element.identifier" type="neutral" />
    </div>
  </Card>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Card from 'tui/components/card/Card';
import DeleteButton from 'tui/components/buttons/DeleteIcon';
import DragHandleIcon from 'tui/components/icons/DragHandle';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownItem from 'tui/components/dropdown/DropdownItem';
import EditButton from 'tui/components/buttons/EditIcon';
import Lozenge from 'tui/components/lozenge/Lozenge';
import MoreButton from 'tui/components/buttons/MoreIcon';
import SettingsIcon from 'tui/components/icons/Settings';

export default {
  components: {
    ButtonIcon,
    Card,
    DeleteButton,
    DragHandleIcon,
    Dropdown,
    DropdownItem,
    EditButton,
    Lozenge,
    MoreButton,
    SettingsIcon,
  },
  props: {
    activityState: Object,
    activityId: Number,
    draggable: Boolean,
    dragging: Boolean,
    elementPlugins: Array,
    errors: Object,
    isMultiSectionActive: {
      type: Boolean,
      required: true,
    },
    sectionComponent: Object,
    sectionElement: Object,
    section: Object,
    sectionId: Number,
    activityContextId: [Number, String],
    extraPluginConfigData: {
      type: [Object, Array],
    },
  },

  computed: {
    isActive() {
      return this.activityState.name === 'ACTIVE';
    },
  },

  methods: {
    edit() {
      this.$emit('edit');
    },
    remove() {
      this.$emit('remove');
    },
    displayRead() {
      this.$emit('display-read');
    },
    move() {
      this.$emit('move');
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "edit_element",
      "element_action_options",
      "element_type_heading_a11y",
      "move_to_other_section",
      "section_element_tag_optional",
      "section_element_tag_required",
      "setting_element"
    ],
    "core": [
      "delete"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-performAdminCustomElement {
  position: relative;
  display: flex;
  flex-direction: column;
  padding: var(--gap-4);
  background: var(--color-neutral-1);

  &__actions {
    display: flex;
    flex-direction: row;
    justify-content: flex-end;

    &-item {
      padding: 0 var(--gap-1);
    }
  }

  &__lozenge {
    display: flex;
    flex-direction: row;
    justify-content: flex-end;
    margin-top: var(--gap-2);
  }

  &__content {
    margin-top: var(--gap-1);

    & > * + * {
      margin-top: var(--gap-4);
    }

    &-accessibleTitle {
      @include sr-only();
    }

    &-title {
      margin: 0;
      @include tui-font-heading-x-small;
    }

    &-titleRequired {
      @include tui-font-heading-label();
      color: var(--color-prompt-alert);
    }
  }

  &__moveIcon {
    position: absolute;
    top: var(--gap-1);
    left: var(--gap-2);
    display: none;
  }

  &--dragging > &__moveIcon,
  &:hover > &__moveIcon {
    display: block;
  }
}
</style>
