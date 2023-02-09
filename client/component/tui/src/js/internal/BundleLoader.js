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

import apollo from '../apollo_client';
import bundleQuery from 'totara_tui/graphql/bundles_nosession';
import { config } from '../config';
import pending from '../pending';
import BatchingLoadQueue from './BatchingLoadQueue';

/**
 * Possible states for a bundle
 *
 * @enum
 * @private
 */
const BundleStatus = {
  UNLOADED: undefined,
  LOADING: 1,
  LOADED: 2,
  FAILED: 3,
  NOT_FOUND: 4,
};

/**
 * Load and track bundles for Tui components
 *
 * @private
 */
export default class BundleLoader {
  constructor() {
    this._componentBundleStatus = {};
    this._queue = new BatchingLoadQueue({
      handler: this._loadBundles.bind(this),
      wait: 10,
      serial: true,
    });
  }

  /**
   * Register a component bundle as having been loaded
   *
   * @param {string} comp
   */
  registerComponent(comp) {
    this._componentBundleStatus[comp] = BundleStatus.LOADED;
  }

  /**
   * Check if a component bundle has been loaded
   *
   * @param {string} comp
   * @return {boolean}
   */
  isComponentLoaded(comp) {
    return this._componentBundleStatus[comp] == BundleStatus.LOADED;
  }

  /**
   * Determine if component is in final status (loaded or not found)
   *
   * @param {string} status
   * @returns {boolean}
   */
  isComponentFinal(comp) {
    return this._isFinalStatus(this._componentBundleStatus[comp]);
  }

  /**
   * Load the bundles needed for the provided Tui component
   *
   * @param {string} tuiComponent
   * @returns {Promise<void>}
   */
  async loadBundle(tuiComponent) {
    await this.loadBundles([tuiComponent]);
  }

  /**
   * Load the bundles needed for the provided Tui components
   *
   * @param {string[]} tuiComponent
   * @returns {Promise<void>}
   */
  async loadBundles(tuiComponents) {
    if (tuiComponents.every(x => this.isComponentLoaded(x))) {
      return Promise.resolve();
    }
    const result = await this._queue.enqueueMany(tuiComponents);

    result.forEach(bundleResult => {
      if (
        tuiComponents.includes(bundleResult.component) &&
        bundleResult.status == 'error'
      ) {
        const error = new Error(bundleResult.error);
        error.code = bundleResult.code;
        throw error;
      }
    });
  }

  /**
   * Load the provided Tui components
   *
   * @private
   * @param {string[]} tuiComponents
   * @returns {Promise<object[]>}
   */
  async _loadBundles(tuiComponents) {
    // filter request to bundles that we don't already have
    tuiComponents = tuiComponents.filter(x =>
      this._isLoadableStatus(this._componentBundleStatus[x])
    );

    if (tuiComponents.length == 0) {
      return [];
    }

    // TODO: could potentially cache this in localstorage to improve performance
    // (after taking into account config.rev.js value and theme)
    const result = await apollo.query({
      query: bundleQuery,
      variables: {
        components: tuiComponents,
        theme: config.theme.name,
      },
      fetchPolicy: 'no-cache',
    });

    // filter result to bundles that we don't already have
    const bundles = result.data.bundles.filter(x =>
      this._isLoadableStatus(this._componentBundleStatus[x.component])
    );
    // mark them as loading
    bundles.forEach(bundle => {
      this._componentBundleStatus[bundle.component] = BundleStatus.LOADING;
    });

    // find bundles we want but did not get back in response
    const missing = tuiComponents.filter(
      x => !result.data.bundles.some(y => y.component == x)
    );
    // mark as not found so we don't keep requesting them from the server
    missing.forEach(
      comp => (this._componentBundleStatus[comp] = BundleStatus.NOT_FOUND)
    );

    return Promise.all(
      bundles
        .map(async bundle => {
          try {
            await this._loadFromDefinition(bundle);
            if (bundle.type == 'js') {
              this._componentBundleStatus[bundle.component] =
                BundleStatus.LOADED;
            }
            return { component: bundle.component, status: 'success' };
          } catch (e) {
            if (bundle.type == 'js') {
              this._componentBundleStatus[bundle.component] =
                BundleStatus.FAILED;
            }
            return {
              component: bundle.component,
              status: 'error',
              error: `Unable to load bundle "${bundle.name}" for "${bundle.component}"`,
              code: 'BUNDLE_FAILED',
            };
          }
        })
        // report missing bundles
        .concat(
          missing.map(x => ({
            component: x,
            status: 'error',
            error: `Unable to find a TUI bundle for "${x}"`,
            code: 'BUNDLE_NOT_FOUND',
          }))
        )
    );
  }

  /**
   * Load a bundle from a bundle definition
   *
   * @private
   * @param {object} bundle
   * @returns {Promise}
   */
  _loadFromDefinition(bundle) {
    switch (bundle.type) {
      case 'js':
        return this._loadScript(bundle.url);
      case 'css':
        return this._loadStyle(bundle.url);
      default:
        throw new Error('Unknown bundle type');
    }
  }

  /**
   * Load the script at the specified URL.
   *
   * @private
   * @param {string} url
   * @returns {Promise} resolving if script loads and rejecting if it fails
   */
  _loadScript(url) {
    return new Promise((resolve, reject) => {
      const done = pending('script-loading');
      const script = document.createElement('script');
      script.src = url;
      script.addEventListener('load', () => {
        script.remove();
        done();
        resolve();
      });
      script.addEventListener('error', () => {
        script.remove();
        done();
        reject();
      });
      document.head.appendChild(script);
    });
  }

  /**
   * Load the stylesheet at the specified URL.
   *
   * @private
   * @param {string} url
   * @returns {Promise} resolving if stylesheet loads and rejecting if it fails
   */
  _loadStyle(url) {
    return new Promise((resolve, reject) => {
      const done = pending('styles-loading');
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = url;
      link.addEventListener('load', () => {
        done();
        return resolve();
      });
      link.addEventListener('error', () => {
        done();
        return reject();
      });

      // last tui_scss link should be the theme css, so insert directly before that.
      const tuiLinks = document.head.querySelectorAll(
        'link[rel=stylesheet][href*="tui/styles.php"]'
      );
      const lastTuiLink = tuiLinks[tuiLinks.length - 1];
      if (lastTuiLink) {
        lastTuiLink.parentNode.insertBefore(link, lastTuiLink);
      } else {
        document.head.appendChild(link);
      }
    });
  }

  /**
   * Determine if bundle is in final status (loaded or not found)
   *
   * @private
   * @param {*} status
   * @returns {boolean}
   */
  _isFinalStatus(status) {
    return status == BundleStatus.LOADED || status == BundleStatus.NOT_FOUND;
  }

  /**
   * Determine if bundle is in loadable status (unloaded or failed)
   *
   * @private
   * @param {*} status
   * @returns {boolean}
   */
  _isLoadableStatus(status) {
    return status == BundleStatus.UNLOADED || status == BundleStatus.FAILED;
  }
}
