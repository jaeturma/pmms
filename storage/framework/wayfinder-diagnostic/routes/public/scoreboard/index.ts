import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\PortalController::poll
 * @see app/Http/Controllers/PortalController.php:1563
 * @route '/meets/{meet}/matches/{match}/scoreboard/poll'
 */
export const poll = (args: { meet: string | number, match: string | number } | [meet: string | number, match: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: poll.url(args, options),
    method: 'get',
})

poll.definition = {
    methods: ["get","head"],
    url: '/meets/{meet}/matches/{match}/scoreboard/poll',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PortalController::poll
 * @see app/Http/Controllers/PortalController.php:1563
 * @route '/meets/{meet}/matches/{match}/scoreboard/poll'
 */
poll.url = (args: { meet: string | number, match: string | number } | [meet: string | number, match: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                    match: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: args.meet,
                                match: args.match,
                }

    return poll.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PortalController::poll
 * @see app/Http/Controllers/PortalController.php:1563
 * @route '/meets/{meet}/matches/{match}/scoreboard/poll'
 */
poll.get = (args: { meet: string | number, match: string | number } | [meet: string | number, match: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: poll.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PortalController::poll
 * @see app/Http/Controllers/PortalController.php:1563
 * @route '/meets/{meet}/matches/{match}/scoreboard/poll'
 */
poll.head = (args: { meet: string | number, match: string | number } | [meet: string | number, match: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: poll.url(args, options),
    method: 'head',
})
const scoreboard = {
    poll: Object.assign(poll, poll),
}

export default scoreboard