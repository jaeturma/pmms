import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ScoringSessionController::destroy
 * @see app/Http/Controllers/ScoringSessionController.php:525
 * @route '/scoring-sessions/{session}/events/{event}'
 */
export const destroy = (args: { session: number | { id: number }, event: number | { id: number } } | [session: number | { id: number }, event: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/scoring-sessions/{session}/events/{event}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::destroy
 * @see app/Http/Controllers/ScoringSessionController.php:525
 * @route '/scoring-sessions/{session}/events/{event}'
 */
destroy.url = (args: { session: number | { id: number }, event: number | { id: number } } | [session: number | { id: number }, event: number | { id: number } ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                    event: args[1],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                                event: typeof args.event === 'object'
                ? args.event.id
                : args.event,
                }

    return destroy.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace('{event}', parsedArgs.event.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::destroy
 * @see app/Http/Controllers/ScoringSessionController.php:525
 * @route '/scoring-sessions/{session}/events/{event}'
 */
destroy.delete = (args: { session: number | { id: number }, event: number | { id: number } } | [session: number | { id: number }, event: number | { id: number } ], options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const events = {
    destroy: Object.assign(destroy, destroy),
}

export default events