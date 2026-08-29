import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\ProtestController::index
 * @see app/Http/Controllers/ProtestController.php:33
 * @route '/protests'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/protests',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ProtestController::index
 * @see app/Http/Controllers/ProtestController.php:33
 * @route '/protests'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProtestController::index
 * @see app/Http/Controllers/ProtestController.php:33
 * @route '/protests'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ProtestController::index
 * @see app/Http/Controllers/ProtestController.php:33
 * @route '/protests'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ProtestController::store
 * @see app/Http/Controllers/ProtestController.php:146
 * @route '/protests'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/protests',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ProtestController::store
 * @see app/Http/Controllers/ProtestController.php:146
 * @route '/protests'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProtestController::store
 * @see app/Http/Controllers/ProtestController.php:146
 * @route '/protests'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ProtestController::review
 * @see app/Http/Controllers/ProtestController.php:204
 * @route '/protests/{protest}/review'
 */
export const review = (args: { protest: number | { id: number } } | [protest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})

review.definition = {
    methods: ["patch"],
    url: '/protests/{protest}/review',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ProtestController::review
 * @see app/Http/Controllers/ProtestController.php:204
 * @route '/protests/{protest}/review'
 */
review.url = (args: { protest: number | { id: number } } | [protest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { protest: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { protest: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    protest: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        protest: typeof args.protest === 'object'
                ? args.protest.id
                : args.protest,
                }

    return review.definition.url
            .replace('{protest}', parsedArgs.protest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProtestController::review
 * @see app/Http/Controllers/ProtestController.php:204
 * @route '/protests/{protest}/review'
 */
review.patch = (args: { protest: number | { id: number } } | [protest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: review.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ProtestController::decide
 * @see app/Http/Controllers/ProtestController.php:228
 * @route '/protests/{protest}/decide'
 */
export const decide = (args: { protest: number | { id: number } } | [protest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: decide.url(args, options),
    method: 'patch',
})

decide.definition = {
    methods: ["patch"],
    url: '/protests/{protest}/decide',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ProtestController::decide
 * @see app/Http/Controllers/ProtestController.php:228
 * @route '/protests/{protest}/decide'
 */
decide.url = (args: { protest: number | { id: number } } | [protest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { protest: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { protest: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    protest: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        protest: typeof args.protest === 'object'
                ? args.protest.id
                : args.protest,
                }

    return decide.definition.url
            .replace('{protest}', parsedArgs.protest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ProtestController::decide
 * @see app/Http/Controllers/ProtestController.php:228
 * @route '/protests/{protest}/decide'
 */
decide.patch = (args: { protest: number | { id: number } } | [protest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: decide.url(args, options),
    method: 'patch',
})
const protests = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
review: Object.assign(review, review),
decide: Object.assign(decide, decide),
}

export default protests