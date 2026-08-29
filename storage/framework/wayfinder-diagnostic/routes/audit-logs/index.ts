import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AuditLogController::index
 * @see app/Http/Controllers/AuditLogController.php:20
 * @route '/audit-logs'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/audit-logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AuditLogController::index
 * @see app/Http/Controllers/AuditLogController.php:20
 * @route '/audit-logs'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AuditLogController::index
 * @see app/Http/Controllers/AuditLogController.php:20
 * @route '/audit-logs'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AuditLogController::index
 * @see app/Http/Controllers/AuditLogController.php:20
 * @route '/audit-logs'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})
const auditLogs = {
    index: Object.assign(index, index),
}

export default auditLogs