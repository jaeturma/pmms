import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::store
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:22
 * @route '/technical-official-accreditations'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/technical-official-accreditations',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::store
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:22
 * @route '/technical-official-accreditations'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::store
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:22
 * @route '/technical-official-accreditations'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::download
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:49
 * @route '/technical-official-accreditations/{accreditation}'
 */
export const download = (args: { accreditation: string | number | { id: string | number } } | [accreditation: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/technical-official-accreditations/{accreditation}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::download
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:49
 * @route '/technical-official-accreditations/{accreditation}'
 */
download.url = (args: { accreditation: string | number | { id: string | number } } | [accreditation: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { accreditation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { accreditation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    accreditation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        accreditation: typeof args.accreditation === 'object'
                ? args.accreditation.id
                : args.accreditation,
                }

    return download.definition.url
            .replace('{accreditation}', parsedArgs.accreditation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::download
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:49
 * @route '/technical-official-accreditations/{accreditation}'
 */
download.get = (args: { accreditation: string | number | { id: string | number } } | [accreditation: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::download
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:49
 * @route '/technical-official-accreditations/{accreditation}'
 */
download.head = (args: { accreditation: string | number | { id: string | number } } | [accreditation: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::status
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:39
 * @route '/technical-official-accreditations/{accreditation}/status'
 */
export const status = (args: { accreditation: string | number | { id: string | number } } | [accreditation: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/technical-official-accreditations/{accreditation}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::status
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:39
 * @route '/technical-official-accreditations/{accreditation}/status'
 */
status.url = (args: { accreditation: string | number | { id: string | number } } | [accreditation: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { accreditation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { accreditation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    accreditation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        accreditation: typeof args.accreditation === 'object'
                ? args.accreditation.id
                : args.accreditation,
                }

    return status.definition.url
            .replace('{accreditation}', parsedArgs.accreditation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TechnicalOfficialAccreditationController::status
 * @see app/Http/Controllers/TechnicalOfficialAccreditationController.php:39
 * @route '/technical-official-accreditations/{accreditation}/status'
 */
status.patch = (args: { accreditation: string | number | { id: string | number } } | [accreditation: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})
const technicalOfficialAccreditations = {
    store: Object.assign(store, store),
download: Object.assign(download, download),
status: Object.assign(status, status),
}

export default technicalOfficialAccreditations