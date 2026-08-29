import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EvacuationRouteController::store
 * @see app/Http/Controllers/EvacuationRouteController.php:28
 * @route '/evacuation-routes'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/evacuation-routes',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EvacuationRouteController::store
 * @see app/Http/Controllers/EvacuationRouteController.php:28
 * @route '/evacuation-routes'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EvacuationRouteController::store
 * @see app/Http/Controllers/EvacuationRouteController.php:28
 * @route '/evacuation-routes'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EvacuationRouteController::update
 * @see app/Http/Controllers/EvacuationRouteController.php:52
 * @route '/evacuation-routes/{evacuationRoute}'
 */
export const update = (args: { evacuationRoute: number | { id: number } } | [evacuationRoute: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/evacuation-routes/{evacuationRoute}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\EvacuationRouteController::update
 * @see app/Http/Controllers/EvacuationRouteController.php:52
 * @route '/evacuation-routes/{evacuationRoute}'
 */
update.url = (args: { evacuationRoute: number | { id: number } } | [evacuationRoute: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { evacuationRoute: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { evacuationRoute: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    evacuationRoute: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        evacuationRoute: typeof args.evacuationRoute === 'object'
                ? args.evacuationRoute.id
                : args.evacuationRoute,
                }

    return update.definition.url
            .replace('{evacuationRoute}', parsedArgs.evacuationRoute.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EvacuationRouteController::update
 * @see app/Http/Controllers/EvacuationRouteController.php:52
 * @route '/evacuation-routes/{evacuationRoute}'
 */
update.put = (args: { evacuationRoute: number | { id: number } } | [evacuationRoute: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\EvacuationRouteController::destroy
 * @see app/Http/Controllers/EvacuationRouteController.php:74
 * @route '/evacuation-routes/{evacuationRoute}'
 */
export const destroy = (args: { evacuationRoute: number | { id: number } } | [evacuationRoute: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/evacuation-routes/{evacuationRoute}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\EvacuationRouteController::destroy
 * @see app/Http/Controllers/EvacuationRouteController.php:74
 * @route '/evacuation-routes/{evacuationRoute}'
 */
destroy.url = (args: { evacuationRoute: number | { id: number } } | [evacuationRoute: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { evacuationRoute: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { evacuationRoute: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    evacuationRoute: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        evacuationRoute: typeof args.evacuationRoute === 'object'
                ? args.evacuationRoute.id
                : args.evacuationRoute,
                }

    return destroy.definition.url
            .replace('{evacuationRoute}', parsedArgs.evacuationRoute.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EvacuationRouteController::destroy
 * @see app/Http/Controllers/EvacuationRouteController.php:74
 * @route '/evacuation-routes/{evacuationRoute}'
 */
destroy.delete = (args: { evacuationRoute: number | { id: number } } | [evacuationRoute: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const evacuationRoutes = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default evacuationRoutes