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

  @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
  @module performelement_linked_review
-->

<template>
  <div class="tui-linkedReviewAdminView">
    <Card class="tui-linkedReviewAdminView__card" :no-border="true">
      <Component :is="getTypeComponent()" :data="data" />
    </Card>

    <div class="tui-linkedReviewAdminView__questions">
      <PerformAdminChildElements
        v-if="!reportPreview"
        :activity-context-id="activityContextId"
        :activity-id="activityId"
        :activity-state="activityState"
        :addable-element-plugins="compatibleElementPlugins"
        :element-id="elementId"
        :section-element="sectionElement"
        :section-id="sectionId"
        @child-update="$emit('child-update', $event)"
        @unsaved-child="$emit('unsaved-child', $event)"
      />
    </div>

    <component
      :is="getFooterComponent()"
      class="tui-linkedReviewAdminView__footer"
      :settings="data.content_type_settings"
    />
  </div>
</template>

<script>
import Card from 'tui/components/card/Card';
import PerformAdminChildElements from 'mod_perform/components/element/PerformAdminChildElements';

export default {
  components: {
    Card,
    PerformAdminChildElements,
  },

  inheritAttrs: false,

  props: {
    activityContextId: Number,
    activityId: Number,
    activityState: Object,
    data: Object,
    elementId: [Number, String],
    elementPlugins: Array,
    reportPreview: Boolean,
    sectionElement: Object,
    sectionId: Number,
  },

  computed: {
    /**
     * Provide the plugin list for elements that can be added
     *
     */
    compatibleElementPlugins() {
      let compatibleChildPlugins = this.sectionElement.element.data
        .compatible_child_element_plugins;

      if (
        this.reportPreview ||
        this.activityState.code ||
        !compatibleChildPlugins
      ) {
        return [];
      }

      return this.elementPlugins.filter(elementPlugin => {
        return compatibleChildPlugins.includes(elementPlugin.plugin_name);
      });
    },
  },

  methods: {
    /**
     * Get type specific content for preview
     *
     * @return {function}
     */
    getFooterComponent() {
      if (!this.data.components || !this.data.components.admin_content_footer) {
        return null;
      }
      return tui.asyncComponent(this.data.components.admin_content_footer);
    },

    /**
     * Get type specific content for preview
     *
     * @return {function}
     */
    getTypeComponent() {
      if (!this.sectionElement.element.data.components) {
        return null;
      }

      return tui.asyncComponent(
        this.sectionElement.element.data.components.admin_view
      );
    },
  },
};
</script>

<style lang="scss">
.tui-linkedReviewAdminView {
  & > * + * {
    margin-top: var(--gap-4);
  }

  &__card {
    flex-direction: column;
    padding: var(--gap-4);
    background: var(--color-neutral-3);
  }

  &__footer {
    margin-top: var(--gap-8);
  }
}
</style>
