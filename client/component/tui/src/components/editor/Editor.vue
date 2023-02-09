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
  @module tui
-->

<script>
// eslint-disable-next-line no-unused-vars
import { EditorContent, Format } from 'tui/editor';
import {
  reconcileFormats,
  getEditorConfig,
  // eslint-disable-next-line no-unused-vars
  EditorIdentifier,
} from '../../js/internal/editor_abstraction';
import EditorLoading from 'tui/components/editor/EditorLoading';
import { showError } from 'tui/errors';
import { unique } from 'tui/util';
import tui from 'tui/tui';

const getChangedFields = (a, b, compare) => {
  const keys = unique(Object.keys(a).concat(Object.keys(b)));
  const changed = [];
  keys.forEach(key => {
    if (!compare(a[key], b[key])) {
      changed.push(key);
    }
  });
  return changed;
};

export default {
  components: {
    EditorLoading,
  },

  props: {
    value: {
      type: EditorContent,
    },

    /**
     * Default format. Only used if value is not provided.
     *
     * @type {import('vue').PropType<?Format>}
     */
    defaultFormat: Number,

    /**
     * Configuration variant. Controls which extensions are loaded.
     */
    variant: {
      type: [String, Array],
      default: 'standard',
    },

    compact: Boolean,

    contextId: Number,

    disabled: Boolean,

    /**
     * The entry that is being edited. Passed to extensions so that they can
     * alter their configuration based off what they are editing, e.g. only
     * allowing mentions of users that can view the identifier.
     *
     * @type {import('vue').PropType<?EditorIdentifier>}
     */
    usageIdentifier: {
      type: Object,
      validator: value =>
        value === null || (value.component != null && value.area != null),
    },

    loading: Boolean,

    /**
     * Whether to enable the sticky toolbar.
     * Defaults to true.
     */
    stickyToolbar: {
      type: Boolean,
      default: true,
    },

    placeholder: String,

    /**
     * Array of extra extensions to load. Each entry should be an object with
     * a "name" key, and optionally an "options" key.
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

    ariaLabel: String,
    ariaLabelledby: String,
    ariaDescribedby: String,
    ariaInvalid: String,
    lockFormat: Boolean,
  },

  data() {
    return {
      loaded: false,
      vueComponent: null,
      vueComponentProps: null,
      // if no format is passed in via EditorContent or defaultFormat, we'll
      // use the default format of the chosen editor
      defaultFallbackFormat: null,
      generatedDefaultContent: null,
      // incremented to force recreation of the underlying editor component,
      // which may not appreciate having its configuration changed
      iter: 0,
    };
  },

  computed: {
    requestedFormat() {
      if (this.value && this.value._originalFormat !== null) {
        return this.value._originalFormat;
      }

      if (this.value && this.value.format !== null) {
        return this.value.format;
      }

      if (this.defaultFormat != null) {
        return this.defaultFormat;
      }

      return null;
    },

    activeFormat() {
      if (
        this.editorInterface &&
        !this.editorInterface.supportsFormat(this.requestedFormat)
      ) {
        return this.defaultFallbackFormat;
      }

      if (this.value && this.value.format != null) {
        return this.value.format;
      }

      if (this.defaultFormat != null) {
        return this.defaultFormat;
      }

      return this.defaultFallbackFormat;
    },

    computedExtraExtensions() {
      if (this.extraExtensions) {
        return this.extraExtensions.map(x =>
          typeof x === 'string' ? { name: x } : x
        );
      }
      return null;
    },

    editorFixedConfig() {
      return {
        format: this.requestedFormat,
        fileItemId: this.activeFileItemId,
        variant: this.variant,
        usageIdentifier: this.usageIdentifier,
        contextId: this.contextId,
        placeholder: this.placeholder,
        compact: this.compact,
        stickyToolbar: this.stickyToolbar,
        extraExtensions: this.computedExtraExtensions,
        disabled: this.disabled,
        ariaLabel: this.ariaLabel,
        ariaLabelledby: this.ariaLabelledby,
        ariaDescribedby: this.ariaDescribedby,
        ariaInvalid: this.ariaInvalid,
        lockFormat: this.lockFormat,
      };
    },

    activeFileItemId() {
      return this.value && this.value.fileItemId ? this.value.fileItemId : null;
    },
  },

  watch: {
    editorFixedConfig(val, old) {
      const changed = getChangedFields(
        val,
        old,
        (a, b) => JSON.stringify(a) === JSON.stringify(b)
      );

      if (changed.length === 0) {
        return;
      }

      if (
        // if format has changed and nothing else
        changed.length === 1 &&
        changed[0] === 'format' &&
        // and it has changed from null to defaultFallbackFormat
        old.format === null &&
        val.format === this.defaultFallbackFormat
      ) {
        // no actual change has occurred, skip update
        return;
      }

      this.updateEditorProps();
    },

    loading() {
      this.updateEditorProps();
    },
  },

  async mounted() {
    await this.reconfigure();
  },

  methods: {
    reconfigure() {
      if (this.currentReconfigure) {
        this.reconfigureAgain = true;
      } else {
        this.currentReconfigure = this.$_reconfigure();
        const clear = () => {
          this.currentReconfigure = null;
        };
        this.currentReconfigure.then(clear, clear);
      }
      return this.currentReconfigure;
    },

    async $_reconfigure() {
      this.loaded = false;
      if (this.loading) {
        return;
      }

      const fixedConfig = this.editorFixedConfig;

      // load editor configuration from the server
      const config = await getEditorConfig({
        format: fixedConfig.format,
        variant: fixedConfig.variant,
        usageIdentifier: fixedConfig.usageIdentifier,
        contextId: fixedConfig.contextId,
        extraExtensions: fixedConfig.extraExtensions,
      });

      // load the js module for the editor
      const editor = await config.loadInterface();
      this.editorInterface = editor;

      // this is the format we will use if none is specified
      this.defaultFallbackFormat = editor.getPreferredFormat();

      // this is the content we will use if none is specified
      this.generatedDefaultContent = new EditorContent({
        format: this.activeFormat,
      });

      // typically these would be synchronous, but leave the option open
      [this.vueComponent, this.vueComponentProps] = await Promise.all([
        editor.getComponent(),
        editor.getProps({
          contextId: config.getContextId(),
          config: config.getEditorOptions(),
          format: this.activeFormat,
          fileItemId:
            this.value && this.value.fileItemId ? this.value.fileItemId : null,
          placeholder: fixedConfig.placeholder,
          compact: fixedConfig.compact,
          stickyToolbar: fixedConfig.stickyToolbar,
          usageIdentifier: fixedConfig.usageIdentifier,
          extraExtensions: fixedConfig.extraExtensions,
          disabled: fixedConfig.disabled,
          ariaLabel: fixedConfig.ariaLabel,
          ariaLabelledby: fixedConfig.ariaLabelledby,
          ariaDescribedby: fixedConfig.ariaDescribedby,
          ariaInvalid: fixedConfig.ariaInvalid,
          lockFormat: fixedConfig.lockFormat,
        }),
      ]);

      await tui.loadRequirements(this.vueComponent);

      if (this.reconfigureAgain) {
        this.reconfigureAgain = false;
        this.currentReconfigure = null;
        return this.reconfigure();
      } else {
        this.loaded = true;
        if (editor.forceRecreate) {
          this.iter++;
        }
      }
    },

    handleEditorInput(value) {
      let oldValue = this.value;
      if (!oldValue) {
        oldValue = new EditorContent({
          format: this.requestedFormat,
          content: null,
          fileItemId: null,
        });
      }
      const newValue = oldValue._updateNativeValue(this.editorInterface, value);
      this.$emit('input', newValue);
    },

    async updateEditorProps() {
      try {
        await this.reconfigure();
      } catch (e) {
        showError(e, { vm: this });
      }
    },

    handleReady() {
      this.$emit('ready');
    },

    $_getNativeValue() {
      if (!this.value) {
        return this.generatedDefaultContent._getNativeValue(
          this.editorInterface
        );
      }

      if (this.editorInterface.supportsFormat(this.value.format)) {
        return this.value._getNativeValue(this.editorInterface);
      }

      return reconcileFormats(this.value, {
        from: this.requestedFormat,
        to: this.defaultFallbackFormat,
      })._getNativeValue(this.editorInterface);
    },
  },

  render(h) {
    if (!this.loaded) {
      return h(EditorLoading, {
        props: {
          compact: this.compact,
        },
      });
    }

    return h(this.vueComponent, {
      key: this.iter,
      props: Object.assign({}, this.vueComponentProps, {
        value: this.$_getNativeValue(),
      }),
      on: {
        input: this.handleEditorInput,
        ready: this.handleReady,
      },
    });
  },
};
</script>
