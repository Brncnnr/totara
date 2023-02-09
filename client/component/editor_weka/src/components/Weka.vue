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

  @author Simon Chester <simon.chester@totaralearning.com>
  @module editor_weka
-->

<template>
  <div v-if="ready" :id="uid" v-focus-within class="tui-weka no-yui-ids">
    <Toolbar v-if="showToolbar" :items="toolbarItems" :sticky="stickyToolbar" />
    <div
      ref="editorHost"
      class="tui-weka__editorHost tui-rendered"
      :data-placeholder="placeholder"
    />

    <div ref="extras">
      <!-- This is where modals/custom components will be added/removed -->
    </div>

    <div ref="extrasLive" aria-live="polite" role="status">
      <!-- This is where live-updated UI will be added/removed -->
    </div>
  </div>
  <EditorLoading v-else :id="uid" :compact="compact" />
</template>

<script>
import tui from 'tui/tui';
import { throttle } from 'tui/util';
import pending from 'tui/pending';
import Editor from '../js/Editor';
import EditorLoading from 'tui/components/editor/EditorLoading';
import Toolbar from 'editor_weka/components/toolbar/Toolbar';
import { loadLangStrings } from 'tui/i18n';
import editorWeka from 'editor_weka/graphql/weka';
import editorWekaNoSession from 'editor_weka/graphql/weka_nosession';
import FileStorage from '../js/helpers/file';
import WekaValue from '../js/WekaValue';
import { createImmutablePropWatcher } from 'tui/vue_util';

const propEqual = (val, old) => JSON.stringify(old) == JSON.stringify(val);
const warnChange = prop => createImmutablePropWatcher('Weka', prop, propEqual);

function checkUsageIdentifier(vm) {
  if (!vm.options && vm.usageIdentifier && !vm.variant) {
    console.warn(
      '[Weka] Passing usage-identifier without variant (which will construct ' +
        'a variant from usage-identifier) was deprecated in Totara 17. ' +
        'Please update your code to pass a variant explicitly ' +
        '(e.g. variant="standard") as in a future release, variant will ' +
        'default to "standard" even when usage-identifier is passed.'
    );
  }
}

export default {
  components: {
    EditorLoading,
    Toolbar,
  },

  props: {
    placeholder: {
      type: String,
      default: '',
    },
    fileItemId: [Number, String],
    /**
     * Context ID. You can pass the context ID directly here if you don't want to
     * derive it from instanceId in usageIdentifier.
     */
    contextId: [Number, String],

    /**
     * The entry that is being edited. Passed to extensions so that they can
     * alter their configuration based off what they are editing, e.g. only
     * allowing mentions of users that can view the identifier.
     *
     * @type {import('vue').PropType<?{ component: string, area: string, instanceId?: number }>}
     */
    usageIdentifier: {
      type: Object,
      validator: prop => 'component' in prop && 'area' in prop,
      default() {
        return {
          component: 'editor_weka',
          area: 'learn',
        };
      },
    },

    variant: String,

    /**
     * Preloaded configuration data. Internal (used by Editor abstraction integration), do not use directly.
     * @internal
     */
    options: {
      type: Object,
      validator: prop => prop.extensions !== undefined,
    },

    value: WekaValue,

    /**
     * The compact mode is to determine whether we are showing the tool bar or not.
     */
    compact: Boolean,

    /**
     * Whether the toolbar should be sticky within the editor container
     */
    stickyToolbar: {
      type: Boolean,
      default: true,
    },

    /**
     * If false, loads the editor without a user logged in and disables file support.
     */
    isLoggedIn: {
      type: Boolean,
      default: true,
    },

    /**
     * Extra extensions to load. Has the following structure:
     *  [
     *    "extension_one",
     *    { "name": "extension_two" },
     *    { "name": "extension_three", "options": { "option": "value" }}
     *  ]
     * @type {import('vue').PropType<Array<(string | { name: string, options?: object })>>}
     */
    extraExtensions: {
      type: Array,
      validator(extensions) {
        return extensions.every(extension => {
          // The key 'name' must exist in the extension object.
          // The key 'options' is optional for it to appear in the extension object.
          return typeof extension === 'string' || 'name' in extension;
        });
      },
    },

    disabled: Boolean,
    ariaLabel: String,
    ariaLabelledby: String,
    ariaDescribedby: String,
    ariaInvalid: String,
  },

  data() {
    return {
      toolbarItems: [],

      ready: false,
    };
  },

  computed: {
    showToolbar() {
      if (this.compact) {
        return false;
      }

      return this.toolbarItems.length > 0;
    },
    /** @internal */
    variantUsageIdentifierCombo() {
      return [this.variant, this.usageIdentifier];
    },
    computedExtraExtensions() {
      if (this.extraExtensions) {
        return this.extraExtensions.map(x =>
          typeof x === 'string' ? { name: x } : x
        );
      }
      return null;
    },
  },

  watch: {
    value(value) {
      if (this.displayedValue === value) {
        return;
      }
      this.displayedValue = value || WekaValue.empty();
      if (this.editor) {
        this.editor.setValue(this.displayedValue);
      }
    },

    variant: warnChange('variant'),
    options: warnChange('options'),
    extraExtensions: warnChange('extraExtensions'),

    /**
     * @param {Number|String} value
     */
    fileItemId(value) {
      if (this.editor && this.isLoggedIn) {
        this.editor.updateFileItemId(value);
      }
    },

    disabled(value) {
      if (!this.editor) {
        return;
      }
      this.editor.setEditable(!value);
      if (this.editor.view) {
        this.updateToolbar();
        this.editor.forceRerenderView();
      }
    },
  },

  created() {
    this.updateToolbarThrottled = throttle(this.updateToolbar, 100);
    if (this.value) {
      this.displayedValue = this.value;
    } else {
      this.displayedValue = WekaValue.empty();
    }
    this.finalOptions = null;
    // deprecation warning
    this.$watch(
      'variantUsageIdentifierCombo',
      () => checkUsageIdentifier(this),
      { immediate: true }
    );
  },

  mounted() {
    this.createEditor();
  },

  beforeDestroy() {
    if (this.editor) {
      this.editor.destroy();
    }
  },

  methods: {
    /**
     * Setup the editor options such as showing toolbar, the extensions that the editor needs.
     */
    async setupOptions() {
      if (this.options) {
        this.finalOptions = Object.assign({}, this.options);
        return;
      }

      // Start populating the options from the graphql call.
      const result = await this.$apollo.query({
        query: this.isLoggedIn ? editorWeka : editorWekaNoSession,
        fetchPolicy: 'no-cache',
        variables: {
          instance_id: this.usageIdentifier.instanceId,
          component: this.usageIdentifier.component,
          area: this.usageIdentifier.area,
          context_id: this.contextId || undefined,
          extra_extensions: this.computedExtraExtensions
            ? JSON.stringify(this.computedExtraExtensions)
            : undefined,
          variant_name:
            this.variant ||
            `${this.usageIdentifier.component}-${this.usageIdentifier.area}`,
        },
      });

      this.finalOptions = Object.assign({}, result.data.editor);
    },

    /**
     * This function will try to fetch the extensions via HTTP call. This function will not cache the
     * modules that had already been loaded.
     *
     * @return {Promise<[]>}
     */
    async getExtensions() {
      await this.setupOptions();
      let extensions = [];

      if (Array.isArray(this.finalOptions.extensions)) {
        extensions = await Promise.all(
          this.finalOptions.extensions.map(({ tuicomponent, options }) => {
            let opt = {};

            if (options != null) {
              if (typeof options === 'object') {
                opt = options;
              } else {
                opt = JSON.parse(options);
              }
            }

            return tui
              .import(tuicomponent)
              .then(ext => tui.defaultExport(ext)(opt));
          })
        );
      }

      return extensions;
    },

    async createEditor() {
      if (this.editor) {
        return;
      }

      let pendingDone = pending('weka');

      const extensions = await this.getExtensions();

      let fileStorage = new FileStorage({
        itemId: this.fileItemId,
        contextId: this.finalOptions.context_id || null,
      });

      this.editor = new Editor({
        value: this.displayedValue,
        placeholder: this.placeholder,
        parent: this,
        extensions: extensions,
        fileStorage: fileStorage,
        onUpdate: this.$_onUpdate.bind(this),
        contextId: this.finalOptions.context_id || null,
        component: this.usageIdentifier.component || null,
        area: this.usageIdentifier.area || null,
        instanceId: this.usageIdentifier.instanceId || null,
        ariaLabel: this.ariaLabel,
        ariaLabelledby: this.ariaLabelledby,
        ariaDescribedby: this.ariaDescribedby,
        ariaInvalid: this.ariaInvalid,
        editable: () => !this.disabled,
        onTransaction: () => {
          this.updateToolbarThrottled();
        },
        onFocus: e => {
          this.$emit('focus', e);
        },
        onBlur: e => {
          this.$emit('blur', e);
        },
      });

      const components = this.editor.allVueComponents();

      await Promise.all([
        tui.loadRequirements({ components }),
        loadLangStrings(this.editor.allStrings()),
      ]);

      this.ready = true;

      await this.$nextTick();

      this.editor.setElements({
        viewExtras: this.$refs.extras,
        viewExtrasLive: this.$refs.extrasLive,
      });

      this.view = this.editor.createView(this.$refs.editorHost);

      this.updateToolbar();

      pendingDone();

      // Event emitted to make the parent component knowing that this editor has been mounted properly.
      this.$emit('ready');
    },

    updateToolbar() {
      if (!this.editor.destroyed) {
        this.toolbarItems = this.editor.getToolbarItems();
      }
    },

    /**
     * Emit new value.
     *
     * @param {WekaValue} value
     */
    $_onUpdate(value) {
      this.displayedValue = value;
      this.$emit('input', value);
    },
  },
};
</script>

<style lang="scss">
.tui-weka {
  display: flex;
  flex-direction: column;
  width: 100%;
  background-color: var(--color-neutral-1);
  border: var(--border-width-thin) solid var(--form-input-border-color);

  &.tui-focusWithin {
    background: var(--form-input-bg-color-focus);
    border: var(--form-input-border-size) solid;
    border-color: var(--form-input-border-color-focus);
    outline: none;
    box-shadow: var(--form-input-shadow-focus);
  }

  &__placeholder {
    // Styling for the place holder.
    &:before {
      color: var(--color-neutral-6);
      content: attr(data-placeholder);
    }
  }

  &__editorHost {
    display: flex;
    flex-direction: column;
    flex-grow: 1;
    overflow: auto;

    > .tui-weka-editor {
      flex-grow: 1;
    }
  }

  .ProseMirror-focused {
    .tui-weka {
      &__placeholder {
        &:before {
          content: '';
        }
      }
    }
  }

  .ProseMirror {
    padding: var(--gap-4);
    @include tui-weka-whitespace();
    word-wrap: break-word;
    -webkit-font-variant-ligatures: none;
    font-variant-ligatures: none;
    font-feature-settings: 'liga' 0; /* the above doesn't seem to work in Edge */

    &:focus {
      outline: none;
    }

    hr {
      margin: 0 0 var(--gap-2) 0;
    }

    pre {
      white-space: pre-wrap;
    }

    ol,
    ul {
      margin: 0;
      padding-left: var(--gap-4);
    }

    ul ul {
      list-style-type: circle;
    }
    ul ul ul {
      list-style-type: square;
    }

    li {
      position: relative;
    }
  }

  .ProseMirror-hideselection *::selection,
  .ProseMirror-hideselection *::-moz-selection {
    background: transparent;
  }

  .ProseMirror-hideselection {
    caret-color: transparent;
  }

  .ProseMirror-selectednode {
    outline: var(--border-width-normal) solid var(--color-secondary);
  }

  /* Make sure li selections wrap around markers */

  li.ProseMirror-selectednode {
    outline: none;
  }

  li.ProseMirror-selectednode:after {
    position: absolute;
    top: -2px;
    right: -2px;
    bottom: -2px;
    left: -32px;
    border: var(--border-width-normal) solid var(--color-secondary);
    content: '';
    pointer-events: none;
  }

  .ProseMirror-gapcursor {
    position: relative;
    margin-bottom: var(--paragraph-gap);
  }

  .ProseMirror-gapcursor:before {
    // insert an nbsp to make gapcursor expand to full line height
    content: '\00a0';
  }

  // Add a 'fake' blinking cursor to the gapcursor element
  .ProseMirror-gapcursor:after {
    position: absolute;
    top: -2px;
    display: block;
    height: 20px;
    border-left: 1px solid black;
    animation: ProseMirror-cursor-blink 1.1s steps(2, start) infinite;
    content: '';
  }

  @keyframes ProseMirror-cursor-blink {
    to {
      visibility: hidden;
    }
  }
}

.ie .tui-weka__editorHost > .tui-weka-editor {
  // IE11: Work around issues with empty space below ImageBlock
  // https://github.com/philipwalton/flexbugs/issues/75
  // Not enabled in other browsers as it causes issues with spacing at the end
  // of the editor with layouts. Ironically, it does not in IE.
  min-height: 1px;
}
</style>
