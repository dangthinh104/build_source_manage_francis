/**
 * VeeValidate Composable
 * 
 * Provides form validation functionality using VeeValidate
 */

import { useForm } from 'vee-validate';
import { validationMessages } from '@/Utils/validationRules';

/**
 * Create a validated form with VeeValidate
 * 
 * @param {Object} validationSchema - VeeValidate validation schema
 * @param {Object} initialValues - Initial form values
 * @returns {Object} VeeValidate form instance
 */
export function useValidatedForm(validationSchema, initialValues = {}) {
    const form = useForm({
        validationSchema,
        initialValues,
    });

    // Configure custom error messages
    form.configure({
        generateMessage: (context) => {
            const message = validationMessages[context.rule.name];
            if (!message) return `${context.field} is invalid`;

            return message
                .replace('{field}', context.field)
                .replace('{min}', context.rule.params?.[0] || '')
                .replace('{max}', context.rule.params?.[0] || '');
        },
    });

    return form;
}

/**
 * Validate a single field
 * 
 * @param {String} fieldName
 * @param {Any} value
 * @param {Object} rules
 * @returns {Promise<Boolean>}
 */
export async function validateField(fieldName, value, rules) {
    const { validate } = useForm({
        validationSchema: { [fieldName]: rules },
        initialValues: { [fieldName]: value },
    });

    const result = await validate();
    return result.valid;
}
