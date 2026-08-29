import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:237
 * @route '/reports/results/{result}/download'
 */
export const download = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/reports/results/{result}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:237
 * @route '/reports/results/{result}/download'
 */
download.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return download.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:237
 * @route '/reports/results/{result}/download'
 */
download.get = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::download
 * @see app/Http/Controllers/ReportController.php:237
 * @route '/reports/results/{result}/download'
 */
download.head = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})
const resultSheet = {
    download: Object.assign(download, download),
}

export default resultSheet