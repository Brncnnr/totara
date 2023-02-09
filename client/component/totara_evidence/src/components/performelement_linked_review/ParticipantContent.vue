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

  @author Brian Barnes <brian.barnes@totaralearning.com>
  @package totara_evidence
-->

<template>
  <div class="tui-linkedReviewViewEvidence">
    <h3 class="tui-linkedReviewViewEvidence__heading">
      {{ content.display_name }}
    </h3>
    <dl class="tui-linkedReviewViewEvidence__customFields">
      <div
        v-for="{ label, type, content: customField } in fields"
        :key="label"
        class="tui-linkedReviewViewEvidence__customFields-field"
      >
        <dt>{{ label }}</dt>
        <dd v-if="type !== 'file'" v-html="customField.html" />
        <dd v-else>
          <FileCard
            v-if="customField.file_name"
            :file-size="customField.file_size"
            :filename="customField.file_name"
            :download-url="customField.url"
          />
          <p v-else>
            {{ customField.html }}
          </p>
        </dd>
      </div>
    </dl>
    <div class="tui-linkedReviewViewEvidence__type">
      {{ content.type.name }}
    </div>
  </div>
</template>

<script>
import FileCard from 'tui/components/file/FileCard';

export default {
  components: {
    FileCard,
  },

  /**
   * These props are setup in ParticipantContentPicker.
   */
  props: {
    content: {
      type: Object,
      default: () => ({
        display_name: '',
        id: '',
        type: {
          id: '0',
          name: '',
        },
        content_type: '',
        created_at: '',
        fields: [],
      }),
    },
  },

  data() {
    return {
      fields: this.content.fields.map(field => {
        return Object.assign({}, field, {
          content: JSON.parse(field.content),
        });
      }),
    };
  },
};
</script>

<style lang="scss">
.tui-linkedReviewViewEvidence {
  &__heading {
    @include tui-font-heading-x-small();
    margin: 0;
  }

  &__customFields {
    margin-top: var(--gap-4);

    &-field {
      @include tui-layout-sidebar(
        $side-width: 22rem,
        $content-min-width: 60%,
        $gutter: var(--gap-1),
        $sidebar-selector: 'dt',
        $content-selector: 'dd'
      );

      & + & {
        margin-top: var(--gap-2);
      }
    }
  }

  &__type {
    color: var(--color-neutral-6);
  }
}
</style>
