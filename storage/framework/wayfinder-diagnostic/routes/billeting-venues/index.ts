import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\BilletingVenueController::store
 * @see app/Http/Controllers/BilletingVenueController.php:116
 * @route '/billeting-venues'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/billeting-venues',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BilletingVenueController::store
 * @see app/Http/Controllers/BilletingVenueController.php:116
 * @route '/billeting-venues'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BilletingVenueController::store
 * @see app/Http/Controllers/BilletingVenueController.php:116
 * @route '/billeting-venues'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BilletingVenueController::update
 * @see app/Http/Controllers/BilletingVenueController.php:152
 * @route '/billeting-venues/{billetingVenue}'
 */
export const update = (args: { billetingVenue: number | { id: number } } | [billetingVenue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/billeting-venues/{billetingVenue}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\BilletingVenueController::update
 * @see app/Http/Controllers/BilletingVenueController.php:152
 * @route '/billeting-venues/{billetingVenue}'
 */
update.url = (args: { billetingVenue: number | { id: number } } | [billetingVenue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { billetingVenue: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { billetingVenue: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    billetingVenue: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        billetingVenue: typeof args.billetingVenue === 'object'
                ? args.billetingVenue.id
                : args.billetingVenue,
                }

    return update.definition.url
            .replace('{billetingVenue}', parsedArgs.billetingVenue.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BilletingVenueController::update
 * @see app/Http/Controllers/BilletingVenueController.php:152
 * @route '/billeting-venues/{billetingVenue}'
 */
update.put = (args: { billetingVenue: number | { id: number } } | [billetingVenue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\BilletingVenueController::destroy
 * @see app/Http/Controllers/BilletingVenueController.php:177
 * @route '/billeting-venues/{billetingVenue}'
 */
export const destroy = (args: { billetingVenue: number | { id: number } } | [billetingVenue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/billeting-venues/{billetingVenue}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\BilletingVenueController::destroy
 * @see app/Http/Controllers/BilletingVenueController.php:177
 * @route '/billeting-venues/{billetingVenue}'
 */
destroy.url = (args: { billetingVenue: number | { id: number } } | [billetingVenue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { billetingVenue: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { billetingVenue: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    billetingVenue: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        billetingVenue: typeof args.billetingVenue === 'object'
                ? args.billetingVenue.id
                : args.billetingVenue,
                }

    return destroy.definition.url
            .replace('{billetingVenue}', parsedArgs.billetingVenue.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BilletingVenueController::destroy
 * @see app/Http/Controllers/BilletingVenueController.php:177
 * @route '/billeting-venues/{billetingVenue}'
 */
destroy.delete = (args: { billetingVenue: number | { id: number } } | [billetingVenue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const billetingVenues = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default billetingVenues