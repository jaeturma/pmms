import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MatchRosterController::show
 * @see app/Http/Controllers/MatchRosterController.php:47
 * @route '/matches/{match}/roster'
 */
export const show = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/matches/{match}/roster',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MatchRosterController::show
 * @see app/Http/Controllers/MatchRosterController.php:47
 * @route '/matches/{match}/roster'
 */
show.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return show.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchRosterController::show
 * @see app/Http/Controllers/MatchRosterController.php:47
 * @route '/matches/{match}/roster'
 */
show.get = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MatchRosterController::show
 * @see app/Http/Controllers/MatchRosterController.php:47
 * @route '/matches/{match}/roster'
 */
show.head = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MatchRosterController::store
 * @see app/Http/Controllers/MatchRosterController.php:62
 * @route '/matches/{match}/roster'
 */
export const store = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/matches/{match}/roster',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MatchRosterController::store
 * @see app/Http/Controllers/MatchRosterController.php:62
 * @route '/matches/{match}/roster'
 */
store.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { match: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { match: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    match: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        match: typeof args.match === 'object'
                ? args.match.id
                : args.match,
                }

    return store.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchRosterController::store
 * @see app/Http/Controllers/MatchRosterController.php:62
 * @route '/matches/{match}/roster'
 */
store.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MatchRosterController::update
 * @see app/Http/Controllers/MatchRosterController.php:131
 * @route '/match-roster/{rosterPlayer}'
 */
export const update = (args: { rosterPlayer: number | { id: number } } | [rosterPlayer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/match-roster/{rosterPlayer}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\MatchRosterController::update
 * @see app/Http/Controllers/MatchRosterController.php:131
 * @route '/match-roster/{rosterPlayer}'
 */
update.url = (args: { rosterPlayer: number | { id: number } } | [rosterPlayer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { rosterPlayer: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { rosterPlayer: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    rosterPlayer: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        rosterPlayer: typeof args.rosterPlayer === 'object'
                ? args.rosterPlayer.id
                : args.rosterPlayer,
                }

    return update.definition.url
            .replace('{rosterPlayer}', parsedArgs.rosterPlayer.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchRosterController::update
 * @see app/Http/Controllers/MatchRosterController.php:131
 * @route '/match-roster/{rosterPlayer}'
 */
update.patch = (args: { rosterPlayer: number | { id: number } } | [rosterPlayer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\MatchRosterController::destroy
 * @see app/Http/Controllers/MatchRosterController.php:161
 * @route '/match-roster/{rosterPlayer}'
 */
export const destroy = (args: { rosterPlayer: number | { id: number } } | [rosterPlayer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/match-roster/{rosterPlayer}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MatchRosterController::destroy
 * @see app/Http/Controllers/MatchRosterController.php:161
 * @route '/match-roster/{rosterPlayer}'
 */
destroy.url = (args: { rosterPlayer: number | { id: number } } | [rosterPlayer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { rosterPlayer: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { rosterPlayer: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    rosterPlayer: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        rosterPlayer: typeof args.rosterPlayer === 'object'
                ? args.rosterPlayer.id
                : args.rosterPlayer,
                }

    return destroy.definition.url
            .replace('{rosterPlayer}', parsedArgs.rosterPlayer.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchRosterController::destroy
 * @see app/Http/Controllers/MatchRosterController.php:161
 * @route '/match-roster/{rosterPlayer}'
 */
destroy.delete = (args: { rosterPlayer: number | { id: number } } | [rosterPlayer: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const matchRoster = {
    show: Object.assign(show, show),
store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default matchRoster