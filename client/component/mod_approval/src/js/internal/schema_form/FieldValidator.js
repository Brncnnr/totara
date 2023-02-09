import { makeFieldValidator, requiredValidator } from './validation';

export default class FieldValidator {
  constructor() {
    this._map = new WeakMap();
  }

  /**
   * Get validator for field.
   *
   * @param {object} handle Handle used for caching to uniquely identify the field
   * @param {*} field
   * @returns
   */
  getValidator(handle, field) {
    const options = this._getValidatorOptions(field);
    const instKey = JSON.stringify(options);
    const existing = this._map.get(handle);
    if (existing && existing.instKey == instKey) {
      return existing.value;
    }

    const value = this._getValidatorImpl(options);
    this._map.set(handle, {
      instKey,
      value,
    });
    return value;
  }

  _getValidatorOptions(field) {
    return {
      required: field.required,
      validations: field.validations,
    };
  }

  _getValidatorImpl(options) {
    const validations = [];
    if (options.required) {
      validations.push(requiredValidator);
    }
    if (options.validations) {
      validations.push(...options.validations);
    }
    if (validations.length > 0) {
      return makeFieldValidator(validations);
    }
    return null;
  }
}
