import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\VehicleController::store
 * @see app/Http/Controllers/VehicleController.php:130
 * @route '/vehicles'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/vehicles',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\VehicleController::store
 * @see app/Http/Controllers/VehicleController.php:130
 * @route '/vehicles'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VehicleController::store
 * @see app/Http/Controllers/VehicleController.php:130
 * @route '/vehicles'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\VehicleController::update
 * @see app/Http/Controllers/VehicleController.php:165
 * @route '/vehicles/{vehicle}'
 */
export const update = (args: { vehicle: number | { id: number } } | [vehicle: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/vehicles/{vehicle}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\VehicleController::update
 * @see app/Http/Controllers/VehicleController.php:165
 * @route '/vehicles/{vehicle}'
 */
update.url = (args: { vehicle: number | { id: number } } | [vehicle: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { vehicle: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { vehicle: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    vehicle: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        vehicle: typeof args.vehicle === 'object'
                ? args.vehicle.id
                : args.vehicle,
                }

    return update.definition.url
            .replace('{vehicle}', parsedArgs.vehicle.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VehicleController::update
 * @see app/Http/Controllers/VehicleController.php:165
 * @route '/vehicles/{vehicle}'
 */
update.put = (args: { vehicle: number | { id: number } } | [vehicle: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\VehicleController::destroy
 * @see app/Http/Controllers/VehicleController.php:189
 * @route '/vehicles/{vehicle}'
 */
export const destroy = (args: { vehicle: number | { id: number } } | [vehicle: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/vehicles/{vehicle}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\VehicleController::destroy
 * @see app/Http/Controllers/VehicleController.php:189
 * @route '/vehicles/{vehicle}'
 */
destroy.url = (args: { vehicle: number | { id: number } } | [vehicle: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { vehicle: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { vehicle: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    vehicle: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        vehicle: typeof args.vehicle === 'object'
                ? args.vehicle.id
                : args.vehicle,
                }

    return destroy.definition.url
            .replace('{vehicle}', parsedArgs.vehicle.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VehicleController::destroy
 * @see app/Http/Controllers/VehicleController.php:189
 * @route '/vehicles/{vehicle}'
 */
destroy.delete = (args: { vehicle: number | { id: number } } | [vehicle: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const vehicles = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default vehicles