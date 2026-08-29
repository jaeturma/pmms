import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DrrmPlanController::index
 * @see app/Http/Controllers/DrrmPlanController.php:44
 * @route '/drrm/plans'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/drrm/plans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DrrmPlanController::index
 * @see app/Http/Controllers/DrrmPlanController.php:44
 * @route '/drrm/plans'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DrrmPlanController::index
 * @see app/Http/Controllers/DrrmPlanController.php:44
 * @route '/drrm/plans'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DrrmPlanController::index
 * @see app/Http/Controllers/DrrmPlanController.php:44
 * @route '/drrm/plans'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DrrmPlanController::store
 * @see app/Http/Controllers/DrrmPlanController.php:129
 * @route '/drrm-plans'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/drrm-plans',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DrrmPlanController::store
 * @see app/Http/Controllers/DrrmPlanController.php:129
 * @route '/drrm-plans'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DrrmPlanController::store
 * @see app/Http/Controllers/DrrmPlanController.php:129
 * @route '/drrm-plans'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DrrmPlanController::update
 * @see app/Http/Controllers/DrrmPlanController.php:152
 * @route '/drrm-plans/{drrmPlan}'
 */
export const update = (args: { drrmPlan: number | { id: number } } | [drrmPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/drrm-plans/{drrmPlan}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DrrmPlanController::update
 * @see app/Http/Controllers/DrrmPlanController.php:152
 * @route '/drrm-plans/{drrmPlan}'
 */
update.url = (args: { drrmPlan: number | { id: number } } | [drrmPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { drrmPlan: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { drrmPlan: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    drrmPlan: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        drrmPlan: typeof args.drrmPlan === 'object'
                ? args.drrmPlan.id
                : args.drrmPlan,
                }

    return update.definition.url
            .replace('{drrmPlan}', parsedArgs.drrmPlan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DrrmPlanController::update
 * @see app/Http/Controllers/DrrmPlanController.php:152
 * @route '/drrm-plans/{drrmPlan}'
 */
update.put = (args: { drrmPlan: number | { id: number } } | [drrmPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DrrmPlanController::destroy
 * @see app/Http/Controllers/DrrmPlanController.php:170
 * @route '/drrm-plans/{drrmPlan}'
 */
export const destroy = (args: { drrmPlan: number | { id: number } } | [drrmPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/drrm-plans/{drrmPlan}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DrrmPlanController::destroy
 * @see app/Http/Controllers/DrrmPlanController.php:170
 * @route '/drrm-plans/{drrmPlan}'
 */
destroy.url = (args: { drrmPlan: number | { id: number } } | [drrmPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { drrmPlan: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { drrmPlan: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    drrmPlan: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        drrmPlan: typeof args.drrmPlan === 'object'
                ? args.drrmPlan.id
                : args.drrmPlan,
                }

    return destroy.definition.url
            .replace('{drrmPlan}', parsedArgs.drrmPlan.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DrrmPlanController::destroy
 * @see app/Http/Controllers/DrrmPlanController.php:170
 * @route '/drrm-plans/{drrmPlan}'
 */
destroy.delete = (args: { drrmPlan: number | { id: number } } | [drrmPlan: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const drrmPlans = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default drrmPlans