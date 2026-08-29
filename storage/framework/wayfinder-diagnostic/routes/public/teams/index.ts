import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PortalTeamsController::show
 * @see app/Http/Controllers/PortalTeamsController.php:88
 * @route '/teams/{municipality}'
 */
export const show = (args: { municipality: string | number } | [municipality: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/teams/{municipality}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalTeamsController::show
 * @see app/Http/Controllers/PortalTeamsController.php:88
 * @route '/teams/{municipality}'
 */
show.url = (args: { municipality: string | number } | [municipality: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { municipality: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    municipality: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        municipality: args.municipality,
                }

    return show.definition.url
            .replace('{municipality}', parsedArgs.municipality.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalTeamsController::show
 * @see app/Http/Controllers/PortalTeamsController.php:88
 * @route '/teams/{municipality}'
 */
show.get = (args: { municipality: string | number } | [municipality: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalTeamsController::show
 * @see app/Http/Controllers/PortalTeamsController.php:88
 * @route '/teams/{municipality}'
 */
show.head = (args: { municipality: string | number } | [municipality: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PortalTeamsController::playersCoaches
 * @see app/Http/Controllers/PortalTeamsController.php:118
 * @route '/teams/{municipality}/players-coaches'
 */
export const playersCoaches = (args: { municipality: string | number } | [municipality: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: playersCoaches.url(args, options),
    method: 'get',
})

playersCoaches.definition = {
    methods: ["get","head"],
    url: '/teams/{municipality}/players-coaches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalTeamsController::playersCoaches
 * @see app/Http/Controllers/PortalTeamsController.php:118
 * @route '/teams/{municipality}/players-coaches'
 */
playersCoaches.url = (args: { municipality: string | number } | [municipality: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { municipality: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    municipality: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        municipality: args.municipality,
                }

    return playersCoaches.definition.url
            .replace('{municipality}', parsedArgs.municipality.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalTeamsController::playersCoaches
 * @see app/Http/Controllers/PortalTeamsController.php:118
 * @route '/teams/{municipality}/players-coaches'
 */
playersCoaches.get = (args: { municipality: string | number } | [municipality: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: playersCoaches.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalTeamsController::playersCoaches
 * @see app/Http/Controllers/PortalTeamsController.php:118
 * @route '/teams/{municipality}/players-coaches'
 */
playersCoaches.head = (args: { municipality: string | number } | [municipality: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: playersCoaches.url(args, options),
    method: 'head',
})
const teams = {
    show: Object.assign(show, show),
playersCoaches: Object.assign(playersCoaches, playersCoaches),
}

export default teams