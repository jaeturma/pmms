import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ResultWorkflowController::store
 * @see app/Http/Controllers/ResultWorkflowController.php:62
 * @route '/results/{result}/attachments'
 */
export const store = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/results/{result}/attachments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::store
 * @see app/Http/Controllers/ResultWorkflowController.php:62
 * @route '/results/{result}/attachments'
 */
store.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return store.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::store
 * @see app/Http/Controllers/ResultWorkflowController.php:62
 * @route '/results/{result}/attachments'
 */
store.post = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ResultWorkflowController::download
 * @see app/Http/Controllers/ResultWorkflowController.php:107
 * @route '/results/{result}/attachments/{attachment}'
 */
export const download = (args: { result: number | { id: number }, attachment: string | number | { id: string | number } } | [result: number | { id: number }, attachment: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/results/{result}/attachments/{attachment}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ResultWorkflowController::download
 * @see app/Http/Controllers/ResultWorkflowController.php:107
 * @route '/results/{result}/attachments/{attachment}'
 */
download.url = (args: { result: number | { id: number }, attachment: string | number | { id: string | number } } | [result: number | { id: number }, attachment: string | number | { id: string | number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                    attachment: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                                attachment: typeof args.attachment === 'object'
                ? args.attachment.id
                : args.attachment,
                }

    return download.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace('{attachment}', parsedArgs.attachment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ResultWorkflowController::download
 * @see app/Http/Controllers/ResultWorkflowController.php:107
 * @route '/results/{result}/attachments/{attachment}'
 */
download.get = (args: { result: number | { id: number }, attachment: string | number | { id: string | number } } | [result: number | { id: number }, attachment: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ResultWorkflowController::download
 * @see app/Http/Controllers/ResultWorkflowController.php:107
 * @route '/results/{result}/attachments/{attachment}'
 */
download.head = (args: { result: number | { id: number }, attachment: string | number | { id: string | number } } | [result: number | { id: number }, attachment: string | number | { id: string | number } ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})
const attachments = {
    store: Object.assign(store, store),
download: Object.assign(download, download),
}

export default attachments