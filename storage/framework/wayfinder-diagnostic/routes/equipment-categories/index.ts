import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EquipmentCategoryController::store
 * @see app/Http/Controllers/EquipmentCategoryController.php:148
 * @route '/equipment-categories'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/equipment-categories',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EquipmentCategoryController::store
 * @see app/Http/Controllers/EquipmentCategoryController.php:148
 * @route '/equipment-categories'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EquipmentCategoryController::store
 * @see app/Http/Controllers/EquipmentCategoryController.php:148
 * @route '/equipment-categories'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EquipmentCategoryController::update
 * @see app/Http/Controllers/EquipmentCategoryController.php:188
 * @route '/equipment-categories/{equipmentCategory}'
 */
export const update = (args: { equipmentCategory: number | { id: number } } | [equipmentCategory: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/equipment-categories/{equipmentCategory}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\EquipmentCategoryController::update
 * @see app/Http/Controllers/EquipmentCategoryController.php:188
 * @route '/equipment-categories/{equipmentCategory}'
 */
update.url = (args: { equipmentCategory: number | { id: number } } | [equipmentCategory: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { equipmentCategory: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { equipmentCategory: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    equipmentCategory: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        equipmentCategory: typeof args.equipmentCategory === 'object'
                ? args.equipmentCategory.id
                : args.equipmentCategory,
                }

    return update.definition.url
            .replace('{equipmentCategory}', parsedArgs.equipmentCategory.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EquipmentCategoryController::update
 * @see app/Http/Controllers/EquipmentCategoryController.php:188
 * @route '/equipment-categories/{equipmentCategory}'
 */
update.put = (args: { equipmentCategory: number | { id: number } } | [equipmentCategory: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\EquipmentCategoryController::destroy
 * @see app/Http/Controllers/EquipmentCategoryController.php:218
 * @route '/equipment-categories/{equipmentCategory}'
 */
export const destroy = (args: { equipmentCategory: number | { id: number } } | [equipmentCategory: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/equipment-categories/{equipmentCategory}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\EquipmentCategoryController::destroy
 * @see app/Http/Controllers/EquipmentCategoryController.php:218
 * @route '/equipment-categories/{equipmentCategory}'
 */
destroy.url = (args: { equipmentCategory: number | { id: number } } | [equipmentCategory: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { equipmentCategory: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { equipmentCategory: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    equipmentCategory: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        equipmentCategory: typeof args.equipmentCategory === 'object'
                ? args.equipmentCategory.id
                : args.equipmentCategory,
                }

    return destroy.definition.url
            .replace('{equipmentCategory}', parsedArgs.equipmentCategory.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EquipmentCategoryController::destroy
 * @see app/Http/Controllers/EquipmentCategoryController.php:218
 * @route '/equipment-categories/{equipmentCategory}'
 */
destroy.delete = (args: { equipmentCategory: number | { id: number } } | [equipmentCategory: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const equipmentCategories = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default equipmentCategories