import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EquipmentIssueController::store
 * @see app/Http/Controllers/EquipmentIssueController.php:28
 * @route '/equipment-issues'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/equipment-issues',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EquipmentIssueController::store
 * @see app/Http/Controllers/EquipmentIssueController.php:28
 * @route '/equipment-issues'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EquipmentIssueController::store
 * @see app/Http/Controllers/EquipmentIssueController.php:28
 * @route '/equipment-issues'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})
const equipmentIssues = {
    store: Object.assign(store, store),
}

export default equipmentIssues