import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ResultWorkflowController::recalculate
 * @see app/Http/Controllers/ResultWorkflowController.php:253
 * @route '/results/{result}/medal-awards/recalculate'
 */
export const recalculate = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recalculate.url(args, options),
    method: 'post',
})

recalculate.definition = {
    methods: ["post"],
    url: '/results/{result}/medal-awards/recalculate',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::recalculate
 * @see app/Http/Controllers/ResultWorkflowController.php:253
 * @route '/results/{result}/medal-awards/recalculate'
 */
recalculate.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return recalculate.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::recalculate
 * @see app/Http/Controllers/ResultWorkflowController.php:253
 * @route '/results/{result}/medal-awards/recalculate'
 */
recalculate.post = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: recalculate.url(args, options),
    method: 'post',
})
const medalAwards = {
    recalculate: Object.assign(recalculate, recalculate),
}

export default medalAwards