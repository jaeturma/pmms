import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\EmergencyCommunicationLogController::store
 * @see app/Http/Controllers/EmergencyCommunicationLogController.php:26
 * @route '/emergency-communication-logs'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/emergency-communication-logs',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EmergencyCommunicationLogController::store
 * @see app/Http/Controllers/EmergencyCommunicationLogController.php:26
 * @route '/emergency-communication-logs'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EmergencyCommunicationLogController::store
 * @see app/Http/Controllers/EmergencyCommunicationLogController.php:26
 * @route '/emergency-communication-logs'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})
const emergencyCommunicationLogs = {
    store: Object.assign(store, store),
}

export default emergencyCommunicationLogs