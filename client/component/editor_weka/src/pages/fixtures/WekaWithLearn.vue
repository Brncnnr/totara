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
  @module editor_weka
-->
<template>
  <div class="tui-wekaWithLearn">
    <h3 class="tui-wekaWithLearn__title">
      {{ $str('pluginname', 'editor_weka') }}
    </h3>

    <Form
      method="POST"
      :action="
        $url('/lib/editor/weka/tests/fixtures/weka_with_learn.php', {
          sesskey: sesskey,
        })
      "
      class="tui-wekaWithLearn__form"
      input-width="full"
      :vertical="true"
    >
      <FormRow
        v-slot="{ id }"
        :label="$str('pluginname', 'editor_weka')"
        class="tui-wekaWithLearn__formRow"
      >
        <Weka
          :id="id"
          v-model="content"
          :usage-identifier="{
            component: 'editor_weka',
            area: 'learn',
          }"
          variant="full"
          :instance-id="instanceId"
          :file-item-id="instanceId"
        />
      </FormRow>

      <FormRow
        v-show="false"
        v-slot="{ id }"
        :hidden="true"
        class="tui-wekaWithLearn__formRow"
      >
        <InputText
          :id="id"
          name="json_content"
          :value="documentValue"
          :hidden="true"
        />
      </FormRow>

      <FormRow
        v-show="false"
        v-slot="{ id }"
        :hidden="true"
        class="tui-wekaWithLearn__formRow"
      >
        <InputText :id="id" name="item_id" :value="itemId" :hidden="true" />
      </FormRow>

      <ButtonGroup class="tui-wekaWithLearn__buttonGroup">
        <Submit />
        <Button
          type="submit"
          name="cancel"
          value="1"
          :text="$str('cancel', 'core')"
        />
      </ButtonGroup>
    </Form>

    <hr />

    <pre class="tui-wekaWithLearn__code">{{ jsonPretty }}</pre>
  </div>
</template>

<script>
import Form from 'tui/components/form/Form';
import FormRow from 'tui/components/form/FormRow';
import Weka from 'editor_weka/components/Weka';
import WekaValue from 'editor_weka/WekaValue';
import ButtonGroup from 'tui/components/buttons/ButtonGroup';
import InputText from 'tui/components/form/InputText';
import Submit from 'tui/components/buttons/Submit';
import Button from 'tui/components/buttons/Button';

export default {
  components: {
    Form,
    FormRow,
    Weka,
    ButtonGroup,
    InputText,
    Submit,
    Button,
  },

  props: {
    defaultDoc: {
      type: String,
      default: '',
    },

    instanceId: [String, Number],
    sesskey: {
      type: String,
      required: true,
    },
  },

  data() {
    return {
      content: null,
      itemId: this.instanceId,
    };
  },

  computed: {
    documentValue() {
      return this.content ? JSON.stringify(this.content.getDoc()) : '';
    },

    jsonPretty() {
      return this.content
        ? JSON.stringify(this.content.getDoc(), undefined, 2)
        : '';
    },
  },

  watch: {
    defaultDoc: {
      immediate: true,
      handler(value) {
        if (value) {
          this.content = WekaValue.fromDoc(JSON.parse(value));
        }
      },
    },

    content(value) {
      if (value) {
        this.itemId = value.fileStorageItemId;
      }
    },
  },
};
</script>

<lang-strings>
  {
    "editor_weka": [
      "pluginname"
    ],
    "core": [
      "cancel"
    ]
  }
</lang-strings>

<style lang="scss">
.tui-wekaWithLearn {
  display: flex;
  flex-direction: column;

  &__title {
    @include tui-font-heading-small();
    margin-bottom: var(--gap-8);
  }

  &__form {
    display: flex;
    flex: 1;
    flex-direction: column;
  }

  &__row {
    flex: 1;
  }

  &__buttonGroup {
    display: flex;
    justify-content: flex-end;
    margin-top: var(--gap-2);
  }

  &__code {
    display: flex;
    padding-left: var(--gap-2);
  }
}
</style>
