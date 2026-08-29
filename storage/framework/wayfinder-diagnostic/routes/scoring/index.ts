import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import events from './events'
/**
* @see \App\Http\Controllers\ScoringSessionController::show
 * @see app/Http/Controllers/ScoringSessionController.php:63
 * @route '/matches/{match}/scoring-session'
 */
export const show = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/matches/{match}/scoring-session',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::show
 * @see app/Http/Controllers/ScoringSessionController.php:63
 * @route '/matches/{match}/scoring-session'
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
* @see \App\Http\Controllers\ScoringSessionController::show
 * @see app/Http/Controllers/ScoringSessionController.php:63
 * @route '/matches/{match}/scoring-session'
 */
show.get = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ScoringSessionController::show
 * @see app/Http/Controllers/ScoringSessionController.php:63
 * @route '/matches/{match}/scoring-session'
 */
show.head = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::board
 * @see app/Http/Controllers/ScoringSessionController.php:80
 * @route '/matches/{match}/scoreboard'
 */
export const board = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: board.url(args, options),
    method: 'get',
})

board.definition = {
    methods: ["get","head"],
    url: '/matches/{match}/scoreboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::board
 * @see app/Http/Controllers/ScoringSessionController.php:80
 * @route '/matches/{match}/scoreboard'
 */
board.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return board.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::board
 * @see app/Http/Controllers/ScoringSessionController.php:80
 * @route '/matches/{match}/scoreboard'
 */
board.get = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: board.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ScoringSessionController::board
 * @see app/Http/Controllers/ScoringSessionController.php:80
 * @route '/matches/{match}/scoreboard'
 */
board.head = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: board.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::start
 * @see app/Http/Controllers/ScoringSessionController.php:127
 * @route '/matches/{match}/scoring-sessions'
 */
export const start = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/matches/{match}/scoring-sessions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::start
 * @see app/Http/Controllers/ScoringSessionController.php:127
 * @route '/matches/{match}/scoring-sessions'
 */
start.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return start.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::start
 * @see app/Http/Controllers/ScoringSessionController.php:127
 * @route '/matches/{match}/scoring-sessions'
 */
start.post = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::score
 * @see app/Http/Controllers/ScoringSessionController.php:280
 * @route '/scoring-sessions/{session}/score'
 */
export const score = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: score.url(args, options),
    method: 'patch',
})

score.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/score',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::score
 * @see app/Http/Controllers/ScoringSessionController.php:280
 * @route '/scoring-sessions/{session}/score'
 */
score.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return score.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::score
 * @see app/Http/Controllers/ScoringSessionController.php:280
 * @route '/scoring-sessions/{session}/score'
 */
score.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: score.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::period
 * @see app/Http/Controllers/ScoringSessionController.php:347
 * @route '/scoring-sessions/{session}/period'
 */
export const period = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: period.url(args, options),
    method: 'patch',
})

period.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/period',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::period
 * @see app/Http/Controllers/ScoringSessionController.php:347
 * @route '/scoring-sessions/{session}/period'
 */
period.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return period.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::period
 * @see app/Http/Controllers/ScoringSessionController.php:347
 * @route '/scoring-sessions/{session}/period'
 */
period.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: period.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::pause
 * @see app/Http/Controllers/ScoringSessionController.php:379
 * @route '/scoring-sessions/{session}/pause'
 */
export const pause = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: pause.url(args, options),
    method: 'patch',
})

pause.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/pause',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::pause
 * @see app/Http/Controllers/ScoringSessionController.php:379
 * @route '/scoring-sessions/{session}/pause'
 */
pause.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return pause.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::pause
 * @see app/Http/Controllers/ScoringSessionController.php:379
 * @route '/scoring-sessions/{session}/pause'
 */
pause.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: pause.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::resume
 * @see app/Http/Controllers/ScoringSessionController.php:394
 * @route '/scoring-sessions/{session}/resume'
 */
export const resume = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: resume.url(args, options),
    method: 'patch',
})

resume.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/resume',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::resume
 * @see app/Http/Controllers/ScoringSessionController.php:394
 * @route '/scoring-sessions/{session}/resume'
 */
resume.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return resume.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::resume
 * @see app/Http/Controllers/ScoringSessionController.php:394
 * @route '/scoring-sessions/{session}/resume'
 */
resume.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: resume.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::end
 * @see app/Http/Controllers/ScoringSessionController.php:419
 * @route '/scoring-sessions/{session}/end'
 */
export const end = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: end.url(args, options),
    method: 'patch',
})

end.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/end',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::end
 * @see app/Http/Controllers/ScoringSessionController.php:419
 * @route '/scoring-sessions/{session}/end'
 */
end.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return end.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::end
 * @see app/Http/Controllers/ScoringSessionController.php:419
 * @route '/scoring-sessions/{session}/end'
 */
end.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: end.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::foul
 * @see app/Http/Controllers/ScoringSessionController.php:460
 * @route '/scoring-sessions/{session}/foul'
 */
export const foul = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: foul.url(args, options),
    method: 'patch',
})

foul.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/foul',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::foul
 * @see app/Http/Controllers/ScoringSessionController.php:460
 * @route '/scoring-sessions/{session}/foul'
 */
foul.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return foul.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::foul
 * @see app/Http/Controllers/ScoringSessionController.php:460
 * @route '/scoring-sessions/{session}/foul'
 */
foul.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: foul.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::round
 * @see app/Http/Controllers/ScoringSessionController.php:603
 * @route '/scoring-sessions/{session}/round'
 */
export const round = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: round.url(args, options),
    method: 'patch',
})

round.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/round',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::round
 * @see app/Http/Controllers/ScoringSessionController.php:603
 * @route '/scoring-sessions/{session}/round'
 */
round.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return round.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::round
 * @see app/Http/Controllers/ScoringSessionController.php:603
 * @route '/scoring-sessions/{session}/round'
 */
round.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: round.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::count
 * @see app/Http/Controllers/ScoringSessionController.php:1643
 * @route '/scoring-sessions/{session}/count'
 */
export const count = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: count.url(args, options),
    method: 'patch',
})

count.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/count',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::count
 * @see app/Http/Controllers/ScoringSessionController.php:1643
 * @route '/scoring-sessions/{session}/count'
 */
count.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return count.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::count
 * @see app/Http/Controllers/ScoringSessionController.php:1643
 * @route '/scoring-sessions/{session}/count'
 */
count.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: count.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::inningRun
 * @see app/Http/Controllers/ScoringSessionController.php:1688
 * @route '/scoring-sessions/{session}/inning-run'
 */
export const inningRun = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: inningRun.url(args, options),
    method: 'patch',
})

inningRun.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/inning-run',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::inningRun
 * @see app/Http/Controllers/ScoringSessionController.php:1688
 * @route '/scoring-sessions/{session}/inning-run'
 */
inningRun.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return inningRun.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::inningRun
 * @see app/Http/Controllers/ScoringSessionController.php:1688
 * @route '/scoring-sessions/{session}/inning-run'
 */
inningRun.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: inningRun.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::settings
 * @see app/Http/Controllers/ScoringSessionController.php:1767
 * @route '/scoring-sessions/{session}/settings'
 */
export const settings = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: settings.url(args, options),
    method: 'patch',
})

settings.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/settings',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::settings
 * @see app/Http/Controllers/ScoringSessionController.php:1767
 * @route '/scoring-sessions/{session}/settings'
 */
settings.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return settings.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::settings
 * @see app/Http/Controllers/ScoringSessionController.php:1767
 * @route '/scoring-sessions/{session}/settings'
 */
settings.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: settings.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::possession
 * @see app/Http/Controllers/ScoringSessionController.php:1843
 * @route '/scoring-sessions/{session}/possession'
 */
export const possession = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: possession.url(args, options),
    method: 'patch',
})

possession.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/possession',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::possession
 * @see app/Http/Controllers/ScoringSessionController.php:1843
 * @route '/scoring-sessions/{session}/possession'
 */
possession.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return possession.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::possession
 * @see app/Http/Controllers/ScoringSessionController.php:1843
 * @route '/scoring-sessions/{session}/possession'
 */
possession.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: possession.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::gameClock
 * @see app/Http/Controllers/ScoringSessionController.php:1893
 * @route '/scoring-sessions/{session}/game-clock'
 */
export const gameClock = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: gameClock.url(args, options),
    method: 'patch',
})

gameClock.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/game-clock',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::gameClock
 * @see app/Http/Controllers/ScoringSessionController.php:1893
 * @route '/scoring-sessions/{session}/game-clock'
 */
gameClock.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return gameClock.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::gameClock
 * @see app/Http/Controllers/ScoringSessionController.php:1893
 * @route '/scoring-sessions/{session}/game-clock'
 */
gameClock.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: gameClock.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::shotClock
 * @see app/Http/Controllers/ScoringSessionController.php:1921
 * @route '/scoring-sessions/{session}/shot-clock'
 */
export const shotClock = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: shotClock.url(args, options),
    method: 'patch',
})

shotClock.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/shot-clock',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::shotClock
 * @see app/Http/Controllers/ScoringSessionController.php:1921
 * @route '/scoring-sessions/{session}/shot-clock'
 */
shotClock.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return shotClock.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::shotClock
 * @see app/Http/Controllers/ScoringSessionController.php:1921
 * @route '/scoring-sessions/{session}/shot-clock'
 */
shotClock.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: shotClock.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::horn
 * @see app/Http/Controllers/ScoringSessionController.php:2025
 * @route '/scoring-sessions/{session}/horn'
 */
export const horn = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: horn.url(args, options),
    method: 'patch',
})

horn.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/horn',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::horn
 * @see app/Http/Controllers/ScoringSessionController.php:2025
 * @route '/scoring-sessions/{session}/horn'
 */
horn.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return horn.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::horn
 * @see app/Http/Controllers/ScoringSessionController.php:2025
 * @route '/scoring-sessions/{session}/horn'
 */
horn.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: horn.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::lineup
 * @see app/Http/Controllers/ScoringSessionController.php:2064
 * @route '/scoring-sessions/{session}/lineup'
 */
export const lineup = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: lineup.url(args, options),
    method: 'patch',
})

lineup.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/lineup',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::lineup
 * @see app/Http/Controllers/ScoringSessionController.php:2064
 * @route '/scoring-sessions/{session}/lineup'
 */
lineup.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return lineup.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::lineup
 * @see app/Http/Controllers/ScoringSessionController.php:2064
 * @route '/scoring-sessions/{session}/lineup'
 */
lineup.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: lineup.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::roundClock
 * @see app/Http/Controllers/ScoringSessionController.php:1955
 * @route '/scoring-sessions/{session}/round-clock'
 */
export const roundClock = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: roundClock.url(args, options),
    method: 'patch',
})

roundClock.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/round-clock',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::roundClock
 * @see app/Http/Controllers/ScoringSessionController.php:1955
 * @route '/scoring-sessions/{session}/round-clock'
 */
roundClock.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return roundClock.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::roundClock
 * @see app/Http/Controllers/ScoringSessionController.php:1955
 * @route '/scoring-sessions/{session}/round-clock'
 */
roundClock.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: roundClock.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::bell
 * @see app/Http/Controllers/ScoringSessionController.php:1992
 * @route '/scoring-sessions/{session}/bell'
 */
export const bell = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bell.url(args, options),
    method: 'patch',
})

bell.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/bell',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::bell
 * @see app/Http/Controllers/ScoringSessionController.php:1992
 * @route '/scoring-sessions/{session}/bell'
 */
bell.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return bell.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::bell
 * @see app/Http/Controllers/ScoringSessionController.php:1992
 * @route '/scoring-sessions/{session}/bell'
 */
bell.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bell.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::rallyPoint
 * @see app/Http/Controllers/ScoringSessionController.php:669
 * @route '/scoring-sessions/{session}/rally-point'
 */
export const rallyPoint = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: rallyPoint.url(args, options),
    method: 'patch',
})

rallyPoint.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/rally-point',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::rallyPoint
 * @see app/Http/Controllers/ScoringSessionController.php:669
 * @route '/scoring-sessions/{session}/rally-point'
 */
rallyPoint.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return rallyPoint.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::rallyPoint
 * @see app/Http/Controllers/ScoringSessionController.php:669
 * @route '/scoring-sessions/{session}/rally-point'
 */
rallyPoint.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: rallyPoint.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::card
 * @see app/Http/Controllers/ScoringSessionController.php:805
 * @route '/scoring-sessions/{session}/card'
 */
export const card = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: card.url(args, options),
    method: 'patch',
})

card.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/card',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::card
 * @see app/Http/Controllers/ScoringSessionController.php:805
 * @route '/scoring-sessions/{session}/card'
 */
card.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return card.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::card
 * @see app/Http/Controllers/ScoringSessionController.php:805
 * @route '/scoring-sessions/{session}/card'
 */
card.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: card.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::gamePoint
 * @see app/Http/Controllers/ScoringSessionController.php:1142
 * @route '/scoring-sessions/{session}/game-point'
 */
export const gamePoint = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: gamePoint.url(args, options),
    method: 'patch',
})

gamePoint.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/game-point',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::gamePoint
 * @see app/Http/Controllers/ScoringSessionController.php:1142
 * @route '/scoring-sessions/{session}/game-point'
 */
gamePoint.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return gamePoint.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::gamePoint
 * @see app/Http/Controllers/ScoringSessionController.php:1142
 * @route '/scoring-sessions/{session}/game-point'
 */
gamePoint.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: gamePoint.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::wrestlingPoint
 * @see app/Http/Controllers/ScoringSessionController.php:1287
 * @route '/scoring-sessions/{session}/wrestling-point'
 */
export const wrestlingPoint = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: wrestlingPoint.url(args, options),
    method: 'patch',
})

wrestlingPoint.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/wrestling-point',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::wrestlingPoint
 * @see app/Http/Controllers/ScoringSessionController.php:1287
 * @route '/scoring-sessions/{session}/wrestling-point'
 */
wrestlingPoint.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return wrestlingPoint.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::wrestlingPoint
 * @see app/Http/Controllers/ScoringSessionController.php:1287
 * @route '/scoring-sessions/{session}/wrestling-point'
 */
wrestlingPoint.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: wrestlingPoint.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::periodClock
 * @see app/Http/Controllers/ScoringSessionController.php:1333
 * @route '/scoring-sessions/{session}/period-clock'
 */
export const periodClock = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: periodClock.url(args, options),
    method: 'patch',
})

periodClock.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/period-clock',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::periodClock
 * @see app/Http/Controllers/ScoringSessionController.php:1333
 * @route '/scoring-sessions/{session}/period-clock'
 */
periodClock.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return periodClock.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::periodClock
 * @see app/Http/Controllers/ScoringSessionController.php:1333
 * @route '/scoring-sessions/{session}/period-clock'
 */
periodClock.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: periodClock.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::fall
 * @see app/Http/Controllers/ScoringSessionController.php:1374
 * @route '/scoring-sessions/{session}/fall'
 */
export const fall = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: fall.url(args, options),
    method: 'patch',
})

fall.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/fall',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::fall
 * @see app/Http/Controllers/ScoringSessionController.php:1374
 * @route '/scoring-sessions/{session}/fall'
 */
fall.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return fall.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::fall
 * @see app/Http/Controllers/ScoringSessionController.php:1374
 * @route '/scoring-sessions/{session}/fall'
 */
fall.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: fall.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::tennisPoint
 * @see app/Http/Controllers/ScoringSessionController.php:1438
 * @route '/scoring-sessions/{session}/tennis-point'
 */
export const tennisPoint = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: tennisPoint.url(args, options),
    method: 'patch',
})

tennisPoint.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/tennis-point',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::tennisPoint
 * @see app/Http/Controllers/ScoringSessionController.php:1438
 * @route '/scoring-sessions/{session}/tennis-point'
 */
tennisPoint.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return tennisPoint.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::tennisPoint
 * @see app/Http/Controllers/ScoringSessionController.php:1438
 * @route '/scoring-sessions/{session}/tennis-point'
 */
tennisPoint.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: tennisPoint.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::tennisUndo
 * @see app/Http/Controllers/ScoringSessionController.php:1593
 * @route '/scoring-sessions/{session}/tennis-undo'
 */
export const tennisUndo = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: tennisUndo.url(args, options),
    method: 'patch',
})

tennisUndo.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/tennis-undo',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::tennisUndo
 * @see app/Http/Controllers/ScoringSessionController.php:1593
 * @route '/scoring-sessions/{session}/tennis-undo'
 */
tennisUndo.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return tennisUndo.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::tennisUndo
 * @see app/Http/Controllers/ScoringSessionController.php:1593
 * @route '/scoring-sessions/{session}/tennis-undo'
 */
tennisUndo.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: tennisUndo.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::penaltyThrow
 * @see app/Http/Controllers/ScoringSessionController.php:871
 * @route '/scoring-sessions/{session}/penalty-throw'
 */
export const penaltyThrow = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: penaltyThrow.url(args, options),
    method: 'patch',
})

penaltyThrow.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/penalty-throw',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::penaltyThrow
 * @see app/Http/Controllers/ScoringSessionController.php:871
 * @route '/scoring-sessions/{session}/penalty-throw'
 */
penaltyThrow.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return penaltyThrow.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::penaltyThrow
 * @see app/Http/Controllers/ScoringSessionController.php:871
 * @route '/scoring-sessions/{session}/penalty-throw'
 */
penaltyThrow.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: penaltyThrow.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::billiardRack
 * @see app/Http/Controllers/ScoringSessionController.php:926
 * @route '/scoring-sessions/{session}/billiard-rack'
 */
export const billiardRack = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: billiardRack.url(args, options),
    method: 'patch',
})

billiardRack.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/billiard-rack',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::billiardRack
 * @see app/Http/Controllers/ScoringSessionController.php:926
 * @route '/scoring-sessions/{session}/billiard-rack'
 */
billiardRack.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return billiardRack.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::billiardRack
 * @see app/Http/Controllers/ScoringSessionController.php:926
 * @route '/scoring-sessions/{session}/billiard-rack'
 */
billiardRack.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: billiardRack.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::billiardUndoRack
 * @see app/Http/Controllers/ScoringSessionController.php:977
 * @route '/scoring-sessions/{session}/billiard-undo-rack'
 */
export const billiardUndoRack = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: billiardUndoRack.url(args, options),
    method: 'patch',
})

billiardUndoRack.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/billiard-undo-rack',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::billiardUndoRack
 * @see app/Http/Controllers/ScoringSessionController.php:977
 * @route '/scoring-sessions/{session}/billiard-undo-rack'
 */
billiardUndoRack.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return billiardUndoRack.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::billiardUndoRack
 * @see app/Http/Controllers/ScoringSessionController.php:977
 * @route '/scoring-sessions/{session}/billiard-undo-rack'
 */
billiardUndoRack.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: billiardUndoRack.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::bocceEnd
 * @see app/Http/Controllers/ScoringSessionController.php:1031
 * @route '/scoring-sessions/{session}/bocce-end'
 */
export const bocceEnd = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bocceEnd.url(args, options),
    method: 'patch',
})

bocceEnd.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/bocce-end',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::bocceEnd
 * @see app/Http/Controllers/ScoringSessionController.php:1031
 * @route '/scoring-sessions/{session}/bocce-end'
 */
bocceEnd.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return bocceEnd.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::bocceEnd
 * @see app/Http/Controllers/ScoringSessionController.php:1031
 * @route '/scoring-sessions/{session}/bocce-end'
 */
bocceEnd.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bocceEnd.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\ScoringSessionController::bocceUndoEnd
 * @see app/Http/Controllers/ScoringSessionController.php:1084
 * @route '/scoring-sessions/{session}/bocce-undo-end'
 */
export const bocceUndoEnd = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bocceUndoEnd.url(args, options),
    method: 'patch',
})

bocceUndoEnd.definition = {
    methods: ["patch"],
    url: '/scoring-sessions/{session}/bocce-undo-end',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ScoringSessionController::bocceUndoEnd
 * @see app/Http/Controllers/ScoringSessionController.php:1084
 * @route '/scoring-sessions/{session}/bocce-undo-end'
 */
bocceUndoEnd.url = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { session: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { session: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    session: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        session: typeof args.session === 'object'
                ? args.session.id
                : args.session,
                }

    return bocceUndoEnd.definition.url
            .replace('{session}', parsedArgs.session.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ScoringSessionController::bocceUndoEnd
 * @see app/Http/Controllers/ScoringSessionController.php:1084
 * @route '/scoring-sessions/{session}/bocce-undo-end'
 */
bocceUndoEnd.patch = (args: { session: number | { id: number } } | [session: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bocceUndoEnd.url(args, options),
    method: 'patch',
})
const scoring = {
    show: Object.assign(show, show),
board: Object.assign(board, board),
start: Object.assign(start, start),
score: Object.assign(score, score),
period: Object.assign(period, period),
pause: Object.assign(pause, pause),
resume: Object.assign(resume, resume),
end: Object.assign(end, end),
foul: Object.assign(foul, foul),
events: Object.assign(events, events),
round: Object.assign(round, round),
count: Object.assign(count, count),
inningRun: Object.assign(inningRun, inningRun),
settings: Object.assign(settings, settings),
possession: Object.assign(possession, possession),
gameClock: Object.assign(gameClock, gameClock),
shotClock: Object.assign(shotClock, shotClock),
horn: Object.assign(horn, horn),
lineup: Object.assign(lineup, lineup),
roundClock: Object.assign(roundClock, roundClock),
bell: Object.assign(bell, bell),
rallyPoint: Object.assign(rallyPoint, rallyPoint),
card: Object.assign(card, card),
gamePoint: Object.assign(gamePoint, gamePoint),
wrestlingPoint: Object.assign(wrestlingPoint, wrestlingPoint),
periodClock: Object.assign(periodClock, periodClock),
fall: Object.assign(fall, fall),
tennisPoint: Object.assign(tennisPoint, tennisPoint),
tennisUndo: Object.assign(tennisUndo, tennisUndo),
penaltyThrow: Object.assign(penaltyThrow, penaltyThrow),
billiardRack: Object.assign(billiardRack, billiardRack),
billiardUndoRack: Object.assign(billiardUndoRack, billiardUndoRack),
bocceEnd: Object.assign(bocceEnd, bocceEnd),
bocceUndoEnd: Object.assign(bocceUndoEnd, bocceUndoEnd),
}

export default scoring