import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TransportTripController::store
 * @see app/Http/Controllers/TransportTripController.php:35
 * @route '/transport-trips'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/transport-trips',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransportTripController::store
 * @see app/Http/Controllers/TransportTripController.php:35
 * @route '/transport-trips'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransportTripController::store
 * @see app/Http/Controllers/TransportTripController.php:35
 * @route '/transport-trips'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransportTripController::status
 * @see app/Http/Controllers/TransportTripController.php:90
 * @route '/transport-trips/{transportTrip}/status'
 */
export const status = (args: { transportTrip: number | { id: number } } | [transportTrip: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/transport-trips/{transportTrip}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TransportTripController::status
 * @see app/Http/Controllers/TransportTripController.php:90
 * @route '/transport-trips/{transportTrip}/status'
 */
status.url = (args: { transportTrip: number | { id: number } } | [transportTrip: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transportTrip: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { transportTrip: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    transportTrip: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        transportTrip: typeof args.transportTrip === 'object'
                ? args.transportTrip.id
                : args.transportTrip,
                }

    return status.definition.url
            .replace('{transportTrip}', parsedArgs.transportTrip.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransportTripController::status
 * @see app/Http/Controllers/TransportTripController.php:90
 * @route '/transport-trips/{transportTrip}/status'
 */
status.patch = (args: { transportTrip: number | { id: number } } | [transportTrip: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\TransportTripController::destroy
 * @see app/Http/Controllers/TransportTripController.php:110
 * @route '/transport-trips/{transportTrip}'
 */
export const destroy = (args: { transportTrip: number | { id: number } } | [transportTrip: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/transport-trips/{transportTrip}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TransportTripController::destroy
 * @see app/Http/Controllers/TransportTripController.php:110
 * @route '/transport-trips/{transportTrip}'
 */
destroy.url = (args: { transportTrip: number | { id: number } } | [transportTrip: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transportTrip: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { transportTrip: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    transportTrip: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        transportTrip: typeof args.transportTrip === 'object'
                ? args.transportTrip.id
                : args.transportTrip,
                }

    return destroy.definition.url
            .replace('{transportTrip}', parsedArgs.transportTrip.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransportTripController::destroy
 * @see app/Http/Controllers/TransportTripController.php:110
 * @route '/transport-trips/{transportTrip}'
 */
destroy.delete = (args: { transportTrip: number | { id: number } } | [transportTrip: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const transportTrips = {
    store: Object.assign(store, store),
status: Object.assign(status, status),
destroy: Object.assign(destroy, destroy),
}

export default transportTrips