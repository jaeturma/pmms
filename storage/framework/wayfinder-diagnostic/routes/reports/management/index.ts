import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ManagementDashboardController::download
 * @see app/Http/Controllers/ManagementDashboardController.php:93
 * @route '/reports/management/download'
 */
export const download = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/reports/management/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ManagementDashboardController::download
 * @see app/Http/Controllers/ManagementDashboardController.php:93
 * @route '/reports/management/download'
 */
download.url = (options?: RouteQueryOptions) => {
    return download.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementDashboardController::download
 * @see app/Http/Controllers/ManagementDashboardController.php:93
 * @route '/reports/management/download'
 */
download.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ManagementDashboardController::download
 * @see app/Http/Controllers/ManagementDashboardController.php:93
 * @route '/reports/management/download'
 */
download.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(options),
    method: 'head',
})
const management = {
    download: Object.assign(download, download),
}

export default management