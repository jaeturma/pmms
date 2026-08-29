import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PortalController::poll
 * @see app/Http/Controllers/PortalController.php:1232
 * @route '/{sportSlug}/poll'
 */
export const poll = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: poll.url(args, options),
    method: 'get',
})

poll.definition = {
    methods: ["get","head"],
    url: '/{sportSlug}/poll',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::poll
 * @see app/Http/Controllers/PortalController.php:1232
 * @route '/{sportSlug}/poll'
 */
poll.url = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sportSlug: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    sportSlug: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sportSlug: args.sportSlug,
                }

    return poll.definition.url
            .replace('{sportSlug}', parsedArgs.sportSlug.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::poll
 * @see app/Http/Controllers/PortalController.php:1232
 * @route '/{sportSlug}/poll'
 */
poll.get = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: poll.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::poll
 * @see app/Http/Controllers/PortalController.php:1232
 * @route '/{sportSlug}/poll'
 */
poll.head = (args: { sportSlug: string | number } | [sportSlug: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: poll.url(args, options),
    method: 'head',
})