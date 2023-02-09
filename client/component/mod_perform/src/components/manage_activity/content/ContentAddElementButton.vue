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

  @author Samantha Jayasinghe <samantha.jayasinghe@totaralearning.com>
  @module mod_perform
-->
<template>
  <div class="tui-performEditSectionContentAddElement">
    <Dropdown>
      <template v-slot:trigger="{ toggle, isOpen }">
        <ButtonIcon
          :aria-expanded="isOpen ? 'true' : 'false'"
          :aria-label="btnString"
          :text="btnString"
          :title="btnString"
          :caret="true"
          @click.prevent="toggle"
        >
          <AddIcon size="200" />
        </ButtonIcon>
      </template>

      <DropdownGroup
        v-if="questionElement.length > 0"
        :separator="true"
        :text="$str('section_dropdown_question_elements', 'mod_perform')"
      >
        <DropdownButton
          v-for="plugin in questionElement"
          :key="plugin.plugin_name"
          @click="addElementPlugin(plugin)"
        >
          {{ plugin.name }}
        </DropdownButton>
      </DropdownGroup>

      <DropdownGroup
        v-if="otherElement.length > 0"
        :separator="true"
        :text="$str('section_dropdown_other_elements', 'mod_perform')"
      >
        <DropdownButton
          v-for="plugin in otherElement"
          :key="plugin.plugin_name"
          @click="addElementPlugin(plugin)"
        >
          {{ plugin.name }}
        </DropdownButton>
      </DropdownGroup>
    </Dropdown>
  </div>
</template>
<script>
import AddIcon from 'tui/components/icons/Add';
import ButtonIcon from 'tui/components/buttons/ButtonIcon';
import Dropdown from 'tui/components/dropdown/Dropdown';
import DropdownButton from 'tui/components/dropdown/DropdownButton';
import DropdownGroup from 'tui/components/dropdown/DropdownGroup';

export default {
  components: {
    AddIcon,
    ButtonIcon,
    Dropdown,
    DropdownButton,
    DropdownGroup,
  },

  props: {
    elementPlugins: {
      type: Array,
      required: true,
    },
    forChildElements: Boolean,
  },

  computed: {
    btnString: function() {
      if (this.forChildElements) {
        return this.$str('section_add_child_element', 'mod_perform');
      }

      return this.$str('section_add_element', 'mod_perform');
    },

    questionElement: function() {
      return this.elementPlugins.filter(function(plugin) {
        if (plugin.group === '1') {
          return plugin;
        }
      });
    },
    otherElement: function() {
      return this.elementPlugins.filter(function(plugin) {
        if (plugin.group === '2') {
          return plugin;
        }
      });
    },
  },

  methods: {
    addElementPlugin(plugin) {
      this.$emit('add-element-item', plugin);
    },
  },
};
</script>

<lang-strings>
  {
    "mod_perform": [
      "section_element_questions",
      "section_add_element",
      "section_add_child_element",
      "section_dropdown_other_elements",
      "section_dropdown_question_elements"
    ]
  }
</lang-strings>
