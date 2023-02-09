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

  @author Valerii Kuznetsov <valerii.kuznetsov@totaralearning.com>
  @module tui
-->

<template>
  <Upload :repository-id="repositoryId" :item-id="itemId" :href="href">
    <slot />
    <template
      v-slot:default="{
        selectEvents,
        inputEvents,
        dragEvents,
        dragAttrs,
        deleteDraft,
        files,
        isDrag,
      }"
    >
      <div
        class="tui-uploadWrapper"
        :class="{ 'tui-uploadWrapper--highlight': isDrag }"
        v-on="dragEvents"
      >
        <input
          v-show="false"
          ref="inputFile"
          type="file"
          multiple
          v-on="inputEvents"
        />
        <a href="#" v-on="selectEvents">Select files</a>
        <ul>
          <li
            v-for="file in files"
            :key="file.name"
            :class="{ 'tui-uploadWrapper__file--done': file.done }"
          >
            {{ file.name }} {{ file.progress }}%
            <ButtonIcon
              :styleclass="{ transparent: true }"
              :aria-label="$str('delete', 'core')"
              @click="deleteDraft(file)"
            >
              <DeleteIcon />
            </ButtonIcon>
          </li>
        </ul>
      </div>
    </template>
  </Upload>
</template>

<script>
import Upload from 'tui/components/form/Upload';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import DeleteIcon from 'tui/components/icons/Delete';

export default {
  components: {
    Upload,
    ButtonIcon,
    DeleteIcon,
  },

  props: {
    href: {
      type: String,
      required: true,
    },
    itemId: {
      type: Number,
      required: true,
    },
    repositoryId: {
      type: Number,
      required: true,
    },
  },
};
</script>

<lang-strings>
{
  "core": [
    "delete"
  ]
}
</lang-strings>
