import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ResultWorkflowController::validate
 * @see app/Http/Controllers/ResultWorkflowController.php:202
 * @route '/results/{result}/event-secretariat-validation'
 */
export const validate = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validate.url(args, options),
    method: 'post',
})

validate.definition = {
    methods: ["post"],
    url: '/results/{result}/event-secretariat-validation',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::validate
 * @see app/Http/Controllers/ResultWorkflowController.php:202
 * @route '/results/{result}/event-secretariat-validation'
 */
validate.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return validate.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::validate
 * @see app/Http/Controllers/ResultWorkflowController.php:202
 * @route '/results/{result}/event-secretariat-validation'
 */
validate.post = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: validate.url(args, options),
    method: 'post',
})
const eventSecretariat = {
    validate: Object.assign(validate, validate),
}

export default eventSecretariat