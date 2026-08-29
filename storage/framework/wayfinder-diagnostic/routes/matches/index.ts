import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MatchController::index
 * @see app/Http/Controllers/MatchController.php:42
 * @route '/matches'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/matches',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MatchController::index
 * @see app/Http/Controllers/MatchController.php:42
 * @route '/matches'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchController::index
 * @see app/Http/Controllers/MatchController.php:42
 * @route '/matches'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MatchController::index
 * @see app/Http/Controllers/MatchController.php:42
 * @route '/matches'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MatchController::store
 * @see app/Http/Controllers/MatchController.php:186
 * @route '/matches'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/matches',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MatchController::store
 * @see app/Http/Controllers/MatchController.php:186
 * @route '/matches'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchController::store
 * @see app/Http/Controllers/MatchController.php:186
 * @route '/matches'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MatchController::update
 * @see app/Http/Controllers/MatchController.php:205
 * @route '/matches/{match}'
 */
export const update = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/matches/{match}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MatchController::update
 * @see app/Http/Controllers/MatchController.php:205
 * @route '/matches/{match}'
 */
update.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchController::update
 * @see app/Http/Controllers/MatchController.php:205
 * @route '/matches/{match}'
 */
update.put = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\MatchController::participants
 * @see app/Http/Controllers/MatchController.php:229
 * @route '/matches/{match}/participants'
 */
export const participants = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: participants.url(args, options),
    method: 'put',
})

participants.definition = {
    methods: ["put"],
    url: '/matches/{match}/participants',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MatchController::participants
 * @see app/Http/Controllers/MatchController.php:229
 * @route '/matches/{match}/participants'
 */
participants.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return participants.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchController::participants
 * @see app/Http/Controllers/MatchController.php:229
 * @route '/matches/{match}/participants'
 */
participants.put = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: participants.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\MatchController::status
 * @see app/Http/Controllers/MatchController.php:306
 * @route '/matches/{match}/status'
 */
export const status = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/matches/{match}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\MatchController::status
 * @see app/Http/Controllers/MatchController.php:306
 * @route '/matches/{match}/status'
 */
status.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return status.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchController::status
 * @see app/Http/Controllers/MatchController.php:306
 * @route '/matches/{match}/status'
 */
status.patch = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\MatchController::destroy
 * @see app/Http/Controllers/MatchController.php:343
 * @route '/matches/{match}'
 */
export const destroy = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/matches/{match}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MatchController::destroy
 * @see app/Http/Controllers/MatchController.php:343
 * @route '/matches/{match}'
 */
destroy.url = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return destroy.definition.url
            .replace('{match}', parsedArgs.match.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MatchController::destroy
 * @see app/Http/Controllers/MatchController.php:343
 * @route '/matches/{match}'
 */
destroy.delete = (args: { match: number | { id: number } } | [match: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const matches = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
participants: Object.assign(participants, participants),
status: Object.assign(status, status),
destroy: Object.assign(destroy, destroy),
}

export default matches