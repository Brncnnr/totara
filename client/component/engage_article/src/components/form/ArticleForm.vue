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

  @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
  @module engage_article
-->

<template>
  <Form :vertical="true" input-width="full" class="tui-engageArticleForm">
    <FormRow
      v-slot="{ id }"
      :hidden="true"
      :label="$str('articletitle', 'engage_article')"
      :required="true"
      class="tui-engageArticleForm__title"
    >
      <InputText
        :id="id"
        v-model="name"
        name="article-title"
        :maxlength="75"
        :placeholder="$str('entertitle', 'engage_article')"
        :required="true"
      />
    </FormRow>

    <div class="tui-engageArticleForm__description">
      <FormRow
        v-slot="{ id }"
        :hidden="true"
        :label="$str('content', 'engage_article')"
        :required="true"
        class="tui-engageArticleForm__description-formRow"
        :is-stacked="false"
      >
        <Weka
          v-if="draftId"
          :id="id"
          v-model="content"
          class="tui-engageArticleForm__editor"
          :usage-identifier="{
            component: 'engage_article',
            area: 'content',
          }"
          variant="full"
          :extra-extensions="['mention', 'hashtag']"
          :file-item-id="draftId"
          :placeholder="$str('entercontent', 'engage_article')"
        />
      </FormRow>

      <div class="tui-engageArticleForm__description-tip">
        <p>{{ $str('contributetip', 'totara_engage') }}</p>
        <InfoIconButton :is-help-for="$str('hashtags', 'totara_engage')">
          {{ $str('contributetip_help', 'totara_engage') }}
        </InfoIconButton>
      </div>
    </div>

    <ButtonGroup class="tui-engageArticleForm__buttons">
      <Button
        :styleclass="{ primary: 'true' }"
        :disabled="disabled"
        :aria-label="$str('createarticleshort', 'engage_article')"
        :text="$str('next', 'core')"
        @click="submit"
      />

      <CancelButton @click="$emit('cancel')" />
    </ButtonGroup>

    <UnsavedChangesWarning v-if="hasChanges" :value="{ hasChanges }" />
  </Form>
</template>

<script>
import InputText from 'tui/components/form/InputText';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import CancelButton from 'tui/components/buttons/Cancel';
import Button from 'tui/components/buttons/Button';
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import InfoIconButton from 'tui/components/buttons/InfoIconButton';
import UnsavedChangesWarning from 'totara_engage/components/form/UnsavedChangesWarning';

// GraphQL queries
import fileDraftId from 'core/graphql/file_unused_draft_item_id';

export default {
  components: {
    InputText,
    ButtonGroup,
    Button,
    CancelButton,
    Weka,
    Form,
    FormRow,
    InfoIconButton,
    UnsavedChangesWarning,
  },

  props: {
    articleContent: {
      type: String,
      default: '',
    },

    articleName: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      // Caching the name separately
      name: this.articleName,
      content: WekaValue.empty(),
      draftId: null,
    };
  },

  computed: {
    disabled() {
      return this.name.length === 0 || this.content.isEmpty;
    },
    hasChanges() {
      return this.name.length > 0 || !this.content.isEmpty;
    },
  },

  watch: {
    articleContent: {
      immediate: true,
      /**
       *
       * @param {String} value
       */
      handler(value) {
        if (!value) {
          return;
        }

        try {
          this.content = WekaValue.fromDoc(JSON.parse(value));
        } catch (e) {
          // Silenced any invalid json string.
          this.content = WekaValue.empty();
        }
      },
    },

    hasChanges: {
      handler() {
        this.$emit('unsaved-changes');
      },
    },
  },

  async mounted() {
    await this.$_loadDraftId();
  },

  methods: {
    async $_loadDraftId() {
      const {
        data: { item_id },
      } = await this.$apollo.mutate({ mutation: fileDraftId });
      this.draftId = item_id;
    },

    submit() {
      const params = {
        name: this.name,
        content: JSON.stringify(this.content.getDoc()),
        itemId: this.draftId,
      };

      this.$emit('next', params);
      this.$_loadDraftId();
    },
  },
};
</script>

<lang-strings>
  {
    "core": [
      "next"
    ],
    "totara_core": [
      "save"
    ],
    "engage_article": [
      "entertitle",
      "entercontent",
      "articletitle",
      "content",
      "createarticleshort"
    ],
    "totara_engage": [
      "contributetip",
      "contributetip_help",
      "hashtags"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-engageArticleForm {
  display: flex;
  flex-basis: 0;
  flex-direction: column;
  flex-grow: 1;
  min-height: 0;

  &__title {
    // TODO: should not be overriding tui-formRow styles
    &.tui-formRow {
      // Reset form row margin.
      margin-bottom: 0;
    }
    // Reset the margin of label section when it is hidden. So that it does not give us any extra spaces.
    .tui-formRow {
      &__desc {
        margin: 0;
      }

      &__action {
        max-width: none;
      }
    }
  }

  &__description {
    display: flex;
    flex-basis: 0;
    flex-direction: column;
    flex-grow: 1;
    min-height: 0;
    margin-top: var(--gap-8);

    &-formRow {
      flex-basis: 0;
      flex-grow: 1;
      min-height: 0;

      // TODO: should not be overriding tui-formRow styles
      .tui-formRow {
        // Reset the margin of label section when it is hidden. So that it does not give us any extra spaces.
        &__desc {
          margin: 0;
        }

        &__action {
          flex-basis: 0;
          // Expand the box.
          flex-grow: 1;
          max-width: none;
          min-height: 0;
        }

        // override flex wrap to make video not over flow
        &__inner {
          flex-basis: 0;
          flex-wrap: nowrap;
          min-height: 0;
        }
      }
    }

    &-tip {
      position: relative;
      display: flex;
      margin-top: var(--gap-2);
    }
  }

  &__editor {
    flex-basis: 0;
    flex-grow: 1;
    min-height: 0;
  }

  &__buttons {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-8);
  }
}
</style>
