import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EquipmentItemController::store
 * @see app/Http/Controllers/EquipmentItemController.php:31
 * @route '/equipment-items'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/equipment-items',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EquipmentItemController::store
 * @see app/Http/Controllers/EquipmentItemController.php:31
 * @route '/equipment-items'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EquipmentItemController::store
 * @see app/Http/Controllers/EquipmentItemController.php:31
 * @route '/equipment-items'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EquipmentItemController::update
 * @see app/Http/Controllers/EquipmentItemController.php:56
 * @route '/equipment-items/{equipmentItem}'
 */
export const update = (args: { equipmentItem: number | { id: number } } | [equipmentItem: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/equipment-items/{equipmentItem}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\EquipmentItemController::update
 * @see app/Http/Controllers/EquipmentItemController.php:56
 * @route '/equipment-items/{equipmentItem}'
 */
update.url = (args: { equipmentItem: number | { id: number } } | [equipmentItem: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { equipmentItem: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { equipmentItem: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    equipmentItem: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        equipmentItem: typeof args.equipmentItem === 'object'
                ? args.equipmentItem.id
                : args.equipmentItem,
                }

    return update.definition.url
            .replace('{equipmentItem}', parsedArgs.equipmentItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EquipmentItemController::update
 * @see app/Http/Controllers/EquipmentItemController.php:56
 * @route '/equipment-items/{equipmentItem}'
 */
update.put = (args: { equipmentItem: number | { id: number } } | [equipmentItem: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\EquipmentItemController::destroy
 * @see app/Http/Controllers/EquipmentItemController.php:76
 * @route '/equipment-items/{equipmentItem}'
 */
export const destroy = (args: { equipmentItem: number | { id: number } } | [equipmentItem: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/equipment-items/{equipmentItem}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\EquipmentItemController::destroy
 * @see app/Http/Controllers/EquipmentItemController.php:76
 * @route '/equipment-items/{equipmentItem}'
 */
destroy.url = (args: { equipmentItem: number | { id: number } } | [equipmentItem: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { equipmentItem: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { equipmentItem: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    equipmentItem: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        equipmentItem: typeof args.equipmentItem === 'object'
                ? args.equipmentItem.id
                : args.equipmentItem,
                }

    return destroy.definition.url
            .replace('{equipmentItem}', parsedArgs.equipmentItem.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EquipmentItemController::destroy
 * @see app/Http/Controllers/EquipmentItemController.php:76
 * @route '/equipment-items/{equipmentItem}'
 */
destroy.delete = (args: { equipmentItem: number | { id: number } } | [equipmentItem: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const equipmentItems = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default equipmentItems