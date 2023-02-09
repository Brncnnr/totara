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

  @author Jaron Steenson <jaron.steenson@totaralearning.com>
  @module mod_perform
-->

<template>
  <HideShow
    class="tui-performParticipantElementDescription"
    :label="$str('element_description', 'mod_perform')"
    :aria-region-label="ariaRegionLabel"
    :initially-expanded="!initiallyClosed"
  >
    <template v-slot:trigger="{ expanded, toggleContent, controls }">
      <ButtonIcon
        :styleclass="{
          small: true,
          transparent: true,
        }"
        :aria-controls="controls"
        :text="$str('element_description', 'mod_perform')"
        :aria-label="
          expanded
            ? $str('element_description_hide', 'mod_perform')
            : $str('element_description_show', 'mod_perform')
        "
        @click="toggleContent"
      >
        <component
          :is="expanded ? 'CollapseIcon' : 'ExpandIcon'"
          aria-hidden="true"
          size="100"
        />
      </ButtonIcon>
    </template>
    <template v-slot:content>
      <RenderedContent
        class="tui-performParticipantElementDescription__content"
        :content-html="contentHtml"
      />
    </template>
  </HideShow>
</template>

<script>
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import CollapseIcon from 'tui/components/icons/Collapse';
import ExpandIcon from 'tui/components/icons/Expand';
import HideShow from 'tui/components/collapsible/HideShow';
import RenderedContent from 'tui/components/editor/RenderedContent';

export default {
  components: {
    ButtonIcon,
    CollapseIcon,
    ExpandIcon,
    HideShow,
    RenderedContent,
  },
  props: {
    ariaRegionLabel: String,
    contentHtml: {
      type: String,
      required: true,
    },
    initiallyClosed: Boolean,
  },
};
</script>

<style lang="scss">
.tui-performParticipantElementDescription {
  &__content {
    margin-left: var(--gap-2);
  }
}
</style>

<lang-strings>
{
  "mod_perform": [
    "element_description",
    "element_description_hide",
    "element_description_show"
  ]
}
</lang-strings>
