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

  @author Kian Nguyen <kian.nguyen@totaralearning.com>
  @module totara_engage
-->

<template>
  <div class="tui-engageTopicsSelector">
    <Label
      :label="$str('assigntopics', 'totara_engage')"
      :for-id="generatedId"
      class="tui-engageTopicsSelector__label"
    />

    <InfoIconButton
      :is-help-for="$str('assigntopics', 'totara_engage')"
      class="tui-engageTopicsSelector__icon"
    >
      <p>
        {{ $str('assigntopicshelpinfoworkspaces', 'totara_engage') }}
      </p>
      <p>
        {{ $str('assigntopicshelpinfoworkspaces2', 'totara_engage') }}
      </p>
    </InfoIconButton>

    <TopicsSelector
      :id="generatedId"
      :selected-topics="selectedTopics"
      :input-placeholder="$str('enter_topics', 'totara_engage')"
      @change="$emit('change', $event)"
    />
  </div>
</template>

<script>
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import TopicsSelector from 'totara_topic/components/form/TopicsSelector';
import Label from 'tui/components/form/Label';

const has = Object.prototype.hasOwnProperty;

export default {
  components: {
    InfoIconButton,
    Label,
    TopicsSelector,
  },

  props: {
    selectedTopics: {
      type: [Array, Object],
      default() {
        return [];
      },

      validator(prop) {
        let items = Array.prototype.slice.call(prop);
        for (let i in items) {
          if (!has.call(items, i)) {
            continue;
          }

          let item = items[i];
          if (!has.call(item, 'value') || !has.call(item, 'id')) {
            return false;
          }
        }

        return true;
      },
    },
  },

  computed: {
    generatedId() {
      return this.$id();
    },
  },
};
</script>

<lang-strings>
  {
    "totara_engage": [
      "assigntopics",
      "assigntopicshelpinfoworkspaces",
      "assigntopicshelpinfoworkspaces2",
      "enter_topics"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageTopicsSelector {
  margin-bottom: var(--gap-9);
  &__label.tui-formLabel {
    @include tui-font-heading-label();
    margin-bottom: var(--gap-2);
  }

  &__icon {
    display: inline-flex;
  }
}
</style>
