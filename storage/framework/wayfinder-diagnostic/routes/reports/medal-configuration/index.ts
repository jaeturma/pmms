import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:45
 * @route '/reports/medal-configuration/download'
 */
export const download = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/reports/medal-configuration/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:45
 * @route '/reports/medal-configuration/download'
 */
download.url = (options?: RouteQueryOptions) => {
    return download.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:45
 * @route '/reports/medal-configuration/download'
 */
download.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:45
 * @route '/reports/medal-configuration/download'
 */
download.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(options),
    method: 'head',
})
const medalConfiguration = {
    download: Object.assign(download, download),
}

export default medalConfiguration