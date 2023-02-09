<!--
  This file is part of Totara Enterprise Extensions.

  Copyright (C) 2020 onwards Totara Learning Solutions LTD

  Totara Enterprise Extensions is provided only to Totara
  Learning Solutions LTD’s customers and partners, pursuant to
  the terms and conditions of a separate agreement with Totara
  Learning Solutions LTD or its affiliate.

  If you do not have an agreement with Totara Learning Solutions
  LTD, you may not access, use, modify, or distribute this software.
  Please contact [licensing@totaralearning.com] for more information.

  @author Simon Chester <simon.chester@totaralearning.com>
  @module samples
-->

<template>
  <div v-if="draftId" class="tui-sampleEditor">
    <div :style="{ height: values.height }">
      <Editor
        v-if="showEditor"
        v-model="content"
        class="tui-sampleEditor__editor"
        :default-format="values.format"
        :variant="values.variant"
        :compact="values.compact"
        :sticky-toolbar="values.stickyToolbar"
        :context-id="values.contextId"
        :usage-identifier="values.identifier"
        :extra-extensions="values.extraExtensions"
        :loading="values.loading"
        :disabled="values.disabled"
        aria-label="Sample editor"
      />
    </div>
    <hr />
    <Uniform :initial-values="values" @change="v => (values = v)">
      <FormRow label="Default format">
        <FormSelect name="format" :options="formatOptions" />
        <FormRowDetails>
          Used when there is no content, otherwise the format of the content
          will take priority.
        </FormRowDetails>
        <FormRowDetails>
          Note: after editing, you will have to use the "Reset content" button
          to make this take effect, as editing produces an EditorContent with a
          set format.
        </FormRowDetails>
      </FormRow>
      <FormRow label="Variant">
        <FormSelect name="variant" :options="variantOptions" />
      </FormRow>
      <FormRow label="Compact">
        <FormToggleSwitch name="compact" text="Compact" toggle-first />
      </FormRow>
      <FormRow label="Sticky toolbar">
        <FormToggleSwitch
          name="stickyToolbar"
          text="Sticky toolbar"
          toggle-first
        />
      </FormRow>
      <FormRow label="Context ID">
        <FormSelect name="contextId" :options="contextIdOptions" />
      </FormRow>
      <FormRow label="Usage Identifier">
        <FormSelect name="identifier" :options="identifierOptions" />
        <FormRowDetails>
          The entry that is being edited. Passed to extensions so that they can
          alter their configuration based off what they are editing, e.g. only
          allowing mentions of users that can view the identifier.
        </FormRowDetails>
        <FormRowDetails>
          This does not need to be unique per editor, you can have multiple
          editors with the exact same identifier prop editing different fields.
        </FormRowDetails>
      </FormRow>
      <FormRow label="Preset content">
        <FormSelect name="contentPreset" :options="contentPresetOptions" />
      </FormRow>
      <FormRow label="Files">
        <FormToggleSwitch
          name="passDraftId"
          text="Pass draft item ID"
          toggle-first
        />
        <FormRowDetails>
          Draft item ID must be passed to enable file uploads.
        </FormRowDetails>
      </FormRow>
      <FormRow label="Loading">
        <FormToggleSwitch name="loading" text="Loading" toggle-first />
      </FormRow>
      <FormRow label="Height">
        <FormSelect name="height" :options="heightOptions" />
      </FormRow>
      <FormRow label="Extra extensions">
        <FormCheckboxGroup name="extraExtensions">
          <Checkbox
            v-for="option in extraExtensionOptions"
            :key="option"
            :value="option"
            ><code>{{ option }}</code></Checkbox
          >
        </FormCheckboxGroup>
      </FormRow>
      <FormRow label="Disabled">
        <FormToggleSwitch name="disabled" text="Disabled" toggle-first />
      </FormRow>
      <FormRow>
        <Button text="Reset content" @click="reset" />
        <Button text="Toggle editor" @click="showEditor = !showEditor" />
      </FormRow>
    </Uniform>
    <hr />
    <div class="tui-sampleEditor__sourceFormat" v-text="sourceFormat" />
    <div class="tui-sampleEditor__source" v-text="source" />
  </div>
</template>

<script>
import { Format, EditorContent } from 'tui/editor';

import Editor from 'tui/components/editor/Editor';
import Button from 'tui/components/buttons/Button';
import {
  Uniform,
  FormRow,
  FormSelect,
  FormToggleSwitch,
  FormCheckboxGroup,
} from 'tui/components/uniform';
import FormRowDetails from 'tui/components/form/FormRowDetails';
import Checkbox from 'tui/components/form/Checkbox';

// GraphQL queries
import getFileUnusedDraftId from 'core/graphql/file_unused_draft_item_id';

export default {
  components: {
    Editor,
    Button,
    Uniform,
    FormRow,
    FormRowDetails,
    FormSelect,
    FormToggleSwitch,
    FormCheckboxGroup,
    Checkbox,
  },

  data() {
    return {
      showEditor: true,
      content: null,
      source: '',
      sourceFormat: null,
      draftId: null,
      values: {
        format: null,
        variant: 'standard',
        compact: false,
        stickyToolbar: true,
        contextId: null,
        identifier: null,
        passDraftId: false,
        contentPreset: null,
        loading: false,
        height: null,
        disabled: false,
      },
      formatOptions: [
        { id: null, label: 'Any' },
        { id: Format.HTML, label: 'HTML' },
        { id: Format.MARKDOWN, label: 'Markdown' },
        { id: Format.JSON_EDITOR, label: 'JSON Editor' },
      ],
      variantOptions: [
        'full',
        { id: 'standard', label: 'standard (default)' },
        'basic',
        'simple',
      ],
      contextIdOptions: [
        { id: null, label: 'None' },
        { id: 2, label: 'Context id 2' },
      ],
      identifierOptions: [
        { id: null, label: 'None' },
        {
          id: { component: 'core', area: 'course', instanceId: 1 },
          label: 'Course 1',
        },
      ],
      contentPresetOptions: [
        { id: null, label: 'No content' },
        {
          id: 1,
          label: 'Empty content (JSON_EDITOR)',
          value: {
            format: Format.JSON_EDITOR,
            content: '',
          },
        },
        {
          id: 2,
          label: 'Simple content (JSON_EDITOR)',
          value: {
            format: Format.JSON_EDITOR,
            content:
              '{"type":"doc","content":[{"type": "paragraph","content":[{"type":"text","text": "hello"}]}]}',
          },
        },
      ],
      heightOptions: [
        { id: null, label: 'Unset (intrinsic)' },
        '70px',
        '200px',
      ],
      extraExtensionOptions: ['mention', 'hashtag'],
    };
  },

  computed: {
    contentPreset() {
      const option = this.contentPresetOptions.find(
        x => x.id == this.values.contentPreset
      );
      return option ? option.value : null;
    },

    passDraftId() {
      return this.values.passDraftId;
    },
  },

  watch: {
    content(value) {
      if (value) {
        let source = value.getContent();
        // prettify json
        if (source && source.charAt(0) == '{') {
          try {
            source = JSON.stringify(JSON.parse(source), null, 2);
          } catch (e) {
            // guess it's not JSON after all
          }
        }
        this.source = source;

        this.sourceFormat = value.format;
        Object.entries(Format).forEach(([key, formatVal]) => {
          if (formatVal == value.format) {
            this.sourceFormat = key;
          }
        });
      } else {
        this.source = '';
        this.sourceFormat = null;
      }
    },

    contentPreset() {
      this.reset();
    },

    passDraftId() {
      this.reset();
    },
  },

  async mounted() {
    const {
      data: { item_id },
    } = await this.$apollo.mutate({
      mutation: getFileUnusedDraftId,
    });
    this.draftId = item_id;
  },

  methods: {
    reset() {
      if (!this.contentPreset) {
        if (this.values.passDraftId) {
          this.content = new EditorContent({
            fileItemId: this.values.passDraftId ? this.draftId : null,
          });
        } else {
          this.content = null;
        }
      } else {
        const result = this.contentPreset;
        if (!result) {
          this.content = null;
          return;
        }
        this.content = new EditorContent({
          format: result.format,
          content: result.content,
          fileItemId: this.values.passDraftId ? this.draftId : null,
        });
      }
    },
  },
};
</script>

<style lang="scss">
.tui-sampleEditor {
  &__editor {
    height: 100%;
  }

  &__source {
    white-space: pre;
  }
}
</style>
