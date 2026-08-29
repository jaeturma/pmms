import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DrrmEquipmentController::store
 * @see app/Http/Controllers/DrrmEquipmentController.php:26
 * @route '/drrm-equipment'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/drrm-equipment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\DrrmEquipmentController::store
 * @see app/Http/Controllers/DrrmEquipmentController.php:26
 * @route '/drrm-equipment'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DrrmEquipmentController::store
 * @see app/Http/Controllers/DrrmEquipmentController.php:26
 * @route '/drrm-equipment'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\DrrmEquipmentController::update
 * @see app/Http/Controllers/DrrmEquipmentController.php:47
 * @route '/drrm-equipment/{drrmEquipment}'
 */
export const update = (args: { drrmEquipment: number | { id: number } } | [drrmEquipment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/drrm-equipment/{drrmEquipment}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\DrrmEquipmentController::update
 * @see app/Http/Controllers/DrrmEquipmentController.php:47
 * @route '/drrm-equipment/{drrmEquipment}'
 */
update.url = (args: { drrmEquipment: number | { id: number } } | [drrmEquipment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { drrmEquipment: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { drrmEquipment: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    drrmEquipment: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        drrmEquipment: typeof args.drrmEquipment === 'object'
                ? args.drrmEquipment.id
                : args.drrmEquipment,
                }

    return update.definition.url
            .replace('{drrmEquipment}', parsedArgs.drrmEquipment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DrrmEquipmentController::update
 * @see app/Http/Controllers/DrrmEquipmentController.php:47
 * @route '/drrm-equipment/{drrmEquipment}'
 */
update.put = (args: { drrmEquipment: number | { id: number } } | [drrmEquipment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\DrrmEquipmentController::destroy
 * @see app/Http/Controllers/DrrmEquipmentController.php:66
 * @route '/drrm-equipment/{drrmEquipment}'
 */
export const destroy = (args: { drrmEquipment: number | { id: number } } | [drrmEquipment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/drrm-equipment/{drrmEquipment}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\DrrmEquipmentController::destroy
 * @see app/Http/Controllers/DrrmEquipmentController.php:66
 * @route '/drrm-equipment/{drrmEquipment}'
 */
destroy.url = (args: { drrmEquipment: number | { id: number } } | [drrmEquipment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { drrmEquipment: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { drrmEquipment: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    drrmEquipment: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        drrmEquipment: typeof args.drrmEquipment === 'object'
                ? args.drrmEquipment.id
                : args.drrmEquipment,
                }

    return destroy.definition.url
            .replace('{drrmEquipment}', parsedArgs.drrmEquipment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DrrmEquipmentController::destroy
 * @see app/Http/Controllers/DrrmEquipmentController.php:66
 * @route '/drrm-equipment/{drrmEquipment}'
 */
destroy.delete = (args: { drrmEquipment: number | { id: number } } | [drrmEquipment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const drrmEquipment = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default drrmEquipment