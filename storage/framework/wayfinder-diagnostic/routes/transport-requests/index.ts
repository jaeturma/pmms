import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TransportRequestController::store
 * @see app/Http/Controllers/TransportRequestController.php:30
 * @route '/transport-requests'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/transport-requests',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransportRequestController::store
 * @see app/Http/Controllers/TransportRequestController.php:30
 * @route '/transport-requests'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransportRequestController::store
 * @see app/Http/Controllers/TransportRequestController.php:30
 * @route '/transport-requests'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransportRequestController::status
 * @see app/Http/Controllers/TransportRequestController.php:60
 * @route '/transport-requests/{transportRequest}/status'
 */
export const status = (args: { transportRequest: number | { id: number } } | [transportRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/transport-requests/{transportRequest}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TransportRequestController::status
 * @see app/Http/Controllers/TransportRequestController.php:60
 * @route '/transport-requests/{transportRequest}/status'
 */
status.url = (args: { transportRequest: number | { id: number } } | [transportRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transportRequest: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { transportRequest: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    transportRequest: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        transportRequest: typeof args.transportRequest === 'object'
                ? args.transportRequest.id
                : args.transportRequest,
                }

    return status.definition.url
            .replace('{transportRequest}', parsedArgs.transportRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransportRequestController::status
 * @see app/Http/Controllers/TransportRequestController.php:60
 * @route '/transport-requests/{transportRequest}/status'
 */
status.patch = (args: { transportRequest: number | { id: number } } | [transportRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\TransportRequestController::destroy
 * @see app/Http/Controllers/TransportRequestController.php:80
 * @route '/transport-requests/{transportRequest}'
 */
export const destroy = (args: { transportRequest: number | { id: number } } | [transportRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/transport-requests/{transportRequest}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TransportRequestController::destroy
 * @see app/Http/Controllers/TransportRequestController.php:80
 * @route '/transport-requests/{transportRequest}'
 */
destroy.url = (args: { transportRequest: number | { id: number } } | [transportRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transportRequest: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { transportRequest: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    transportRequest: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        transportRequest: typeof args.transportRequest === 'object'
                ? args.transportRequest.id
                : args.transportRequest,
                }

    return destroy.definition.url
            .replace('{transportRequest}', parsedArgs.transportRequest.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransportRequestController::destroy
 * @see app/Http/Controllers/TransportRequestController.php:80
 * @route '/transport-requests/{transportRequest}'
 */
destroy.delete = (args: { transportRequest: number | { id: number } } | [transportRequest: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const transportRequests = {
    store: Object.assign(store, store),
status: Object.assign(status, status),
destroy: Object.assign(destroy, destroy),
}

export default transportRequests