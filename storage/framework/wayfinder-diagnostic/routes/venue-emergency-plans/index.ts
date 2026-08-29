import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\VenueEmergencyPlanController::store
 * @see app/Http/Controllers/VenueEmergencyPlanController.php:24
 * @route '/venue-emergency-plans'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/venue-emergency-plans',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\VenueEmergencyPlanController::store
 * @see app/Http/Controllers/VenueEmergencyPlanController.php:24
 * @route '/venue-emergency-plans'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VenueEmergencyPlanController::store
 * @see app/Http/Controllers/VenueEmergencyPlanController.php:24
 * @route '/venue-emergency-plans'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\VenueEmergencyPlanController::update
 * @see app/Http/Controllers/VenueEmergencyPlanController.php:43
 * @route '/venue-emergency-plans/{venueEmergencyPlan}'
 */
export const update = (args: { venueEmergencyPlan: number | { id: number } } | [venueEmergencyPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/venue-emergency-plans/{venueEmergencyPlan}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\VenueEmergencyPlanController::update
 * @see app/Http/Controllers/VenueEmergencyPlanController.php:43
 * @route '/venue-emergency-plans/{venueEmergencyPlan}'
 */
update.url = (args: { venueEmergencyPlan: number | { id: number } } | [venueEmergencyPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { venueEmergencyPlan: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { venueEmergencyPlan: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    venueEmergencyPlan: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        venueEmergencyPlan: typeof args.venueEmergencyPlan === 'object'
                ? args.venueEmergencyPlan.id
                : args.venueEmergencyPlan,
                }

    return update.definition.url
            .replace('{venueEmergencyPlan}', parsedArgs.venueEmergencyPlan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VenueEmergencyPlanController::update
 * @see app/Http/Controllers/VenueEmergencyPlanController.php:43
 * @route '/venue-emergency-plans/{venueEmergencyPlan}'
 */
update.put = (args: { venueEmergencyPlan: number | { id: number } } | [venueEmergencyPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\VenueEmergencyPlanController::destroy
 * @see app/Http/Controllers/VenueEmergencyPlanController.php:60
 * @route '/venue-emergency-plans/{venueEmergencyPlan}'
 */
export const destroy = (args: { venueEmergencyPlan: number | { id: number } } | [venueEmergencyPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/venue-emergency-plans/{venueEmergencyPlan}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\VenueEmergencyPlanController::destroy
 * @see app/Http/Controllers/VenueEmergencyPlanController.php:60
 * @route '/venue-emergency-plans/{venueEmergencyPlan}'
 */
destroy.url = (args: { venueEmergencyPlan: number | { id: number } } | [venueEmergencyPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { venueEmergencyPlan: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { venueEmergencyPlan: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    venueEmergencyPlan: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        venueEmergencyPlan: typeof args.venueEmergencyPlan === 'object'
                ? args.venueEmergencyPlan.id
                : args.venueEmergencyPlan,
                }

    return destroy.definition.url
            .replace('{venueEmergencyPlan}', parsedArgs.venueEmergencyPlan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VenueEmergencyPlanController::destroy
 * @see app/Http/Controllers/VenueEmergencyPlanController.php:60
 * @route '/venue-emergency-plans/{venueEmergencyPlan}'
 */
destroy.delete = (args: { venueEmergencyPlan: number | { id: number } } | [venueEmergencyPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const venueEmergencyPlans = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default venueEmergencyPlans