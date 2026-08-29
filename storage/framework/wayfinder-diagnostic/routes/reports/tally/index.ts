import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:288
 * @route '/reports/tally/download'
 */
export const download = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/reports/tally/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:288
 * @route '/reports/tally/download'
 */
download.url = (options?: RouteQueryOptions) => {
    return download.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:288
 * @route '/reports/tally/download'
 */
download.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:288
 * @route '/reports/tally/download'
 */
download.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(options),
    method: 'head',
})
const tally = {
    download: Object.assign(download, download),
}

export default tally