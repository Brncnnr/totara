/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Simon Chester <simon.chester@totaralearning.com>
 * @module tui
 */

// standalone error overlay code
// should not have any dependencies on anything (except Vue) as they might not
// be available

import Vue from 'vue';

const styles = {
  overlay: {
    background: 'rgba(0,0,0,0.85)',
    color: '#e8e8e8',
    lineHeight: '1.4',
    whiteSpace: 'pre-wrap',
    fontFamily: 'Menlo, Consolas, monospace',
    fontSize: '13px',
    position: 'fixed',
    zIndex: 99999,
    padding: '20px',
    left: 0,
    right: 0,
    top: 0,
    bottom: 0,
    overflow: 'auto',
  },
  close: {
    color: '#e8e8e8',
    lineHeight: '16px',
    fontSize: '32px',
    cursor: 'pointer',
    position: 'absolute',
    padding: '20px',
    top: 0,
    right: 0,
  },
  problem: {
    marginBottom: '26px',
  },
  errorBadge: {
    backgroundColor: '#e36049',
    color: '#fff',
    padding: '2px 4px',
    borderRadius: '2px',
  },
  link: {
    color: '#7cafc2',
  },
  detail: {
    margin: '10px 0 0 10px',
  },
};

const Overlay = Vue.extend({
  data() {
    return {
      problems: [],
    };
  },

  mounted() {
    document.addEventListener('keydown', this.handleKeyDown);
  },

  destroyed() {
    document.removeEventListener('keydown', this.handleKeyDown);
  },

  methods: {
    close() {
      this.$emit('close');
    },

    handleKeyDown(e) {
      if (e.key == 'Escape') {
        e.preventDefault();
        e.stopPropagation();
        this.close();
      }
    },
  },

  render(h) {
    return h('div', { style: styles.overlay }, [
      this.problems.map(problem => h(Problem, { props: problem })),
      h('div', { style: styles.close, on: { click: this.close } }, ['×']),
    ]);
  },
});

const Problem = Vue.extend({
  props: {
    type: String,
    message: String,
    href: String,
  },

  data() {
    return {
      wantsDetail: false,
      detail: null,
    };
  },

  async mounted() {
    // styles_debug failed to load, see if there's an exception we can report
    if (/tui\/styles.php\/.*\/-1\/dl?\//.test(this.href)) {
      this.wantsDetail = true;
      try {
        const result = await fetch(
          this.href +
            (this.href.indexOf('?') === -1 ? '?' : '&') +
            'report=json',
          {
            credentials: 'same-origin',
            method: 'get',
          }
        );
        this.detail = await result.json();
      } catch (e) {
        this.detail = { message: 'Unknown error' };
      }
    }
  },

  render(h) {
    const { type, message, href, wantsDetail, detail } = this;
    return h('div', { style: styles.problem }, [
      h('div', [
        h('span', { style: styles.errorBadge }, type),
        ' ' + message + ' ',
        href && h('a', { domProps: { href }, style: styles.link }, href),
      ]),
      wantsDetail &&
        h('div', { style: styles.detail }, [
          detail ? detail.stack || detail.message : 'Loading...',
        ]),
    ]);
  },
});

let overlay;

/**
 * Show the error overlay if it is not already visible.
 */
function showOverlay() {
  if (overlay) {
    return;
  }
  overlay = new Overlay();
  overlay.$mount();
  overlay.$on('close', () => {
    overlay.$destroy();
    overlay.$el.remove();
    overlay = null;
  });
  document.body.appendChild(overlay.$el);
}

/**
 * Add an error to the overlay and show it if is not visible.
 *
 * @param {object} error Object containing message property and optionally href and type
 */
export function displayError(error) {
  showOverlay();
  overlay.problems.push(Object.assign({ type: 'ERROR' }, error));
}

/**
 * Handle event object produced by 'error' event on script/link element.
 *
 * @param {Event} event
 */
export function handleLoadError(event) {
  if (event.target && event.target.href) {
    displayError({
      message: 'unable to load',
      href: event.target.href,
    });
  } else {
    console.error('load error', event);
  }
}
