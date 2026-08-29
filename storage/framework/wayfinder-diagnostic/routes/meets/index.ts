import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MeetController::index
 * @see app/Http/Controllers/MeetController.php:27
 * @route '/meets'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/meets',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MeetController::index
 * @see app/Http/Controllers/MeetController.php:27
 * @route '/meets'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetController::index
 * @see app/Http/Controllers/MeetController.php:27
 * @route '/meets'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MeetController::index
 * @see app/Http/Controllers/MeetController.php:27
 * @route '/meets'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MeetController::update
 * @see app/Http/Controllers/MeetController.php:76
 * @route '/meets/{meet}'
 */
export const update = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/meets/{meet}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MeetController::update
 * @see app/Http/Controllers/MeetController.php:76
 * @route '/meets/{meet}'
 */
update.url = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { meet: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: typeof args.meet === 'object'
                ? args.meet.id
                : args.meet,
                }

    return update.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetController::update
 * @see app/Http/Controllers/MeetController.php:76
 * @route '/meets/{meet}'
 */
update.put = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\MeetController::status
 * @see app/Http/Controllers/MeetController.php:90
 * @route '/meets/{meet}/status'
 */
export const status = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/meets/{meet}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\MeetController::status
 * @see app/Http/Controllers/MeetController.php:90
 * @route '/meets/{meet}/status'
 */
status.url = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { meet: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: typeof args.meet === 'object'
                ? args.meet.id
                : args.meet,
                }

    return status.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetController::status
 * @see app/Http/Controllers/MeetController.php:90
 * @route '/meets/{meet}/status'
 */
status.patch = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\MeetController::publish
 * @see app/Http/Controllers/MeetController.php:165
 * @route '/meets/{meet}/publish'
 */
export const publish = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: publish.url(args, options),
    method: 'patch',
})

publish.definition = {
    methods: ["patch"],
    url: '/meets/{meet}/publish',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\MeetController::publish
 * @see app/Http/Controllers/MeetController.php:165
 * @route '/meets/{meet}/publish'
 */
publish.url = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { meet: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: typeof args.meet === 'object'
                ? args.meet.id
                : args.meet,
                }

    return publish.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetController::publish
 * @see app/Http/Controllers/MeetController.php:165
 * @route '/meets/{meet}/publish'
 */
publish.patch = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: publish.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\MeetController::unpublish
 * @see app/Http/Controllers/MeetController.php:197
 * @route '/meets/{meet}/unpublish'
 */
export const unpublish = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: unpublish.url(args, options),
    method: 'patch',
})

unpublish.definition = {
    methods: ["patch"],
    url: '/meets/{meet}/unpublish',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\MeetController::unpublish
 * @see app/Http/Controllers/MeetController.php:197
 * @route '/meets/{meet}/unpublish'
 */
unpublish.url = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { meet: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: typeof args.meet === 'object'
                ? args.meet.id
                : args.meet,
                }

    return unpublish.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetController::unpublish
 * @see app/Http/Controllers/MeetController.php:197
 * @route '/meets/{meet}/unpublish'
 */
unpublish.patch = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: unpublish.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\MeetController::activate
 * @see app/Http/Controllers/MeetController.php:222
 * @route '/meets/{meet}/activate'
 */
export const activate = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: activate.url(args, options),
    method: 'patch',
})

activate.definition = {
    methods: ["patch"],
    url: '/meets/{meet}/activate',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\MeetController::activate
 * @see app/Http/Controllers/MeetController.php:222
 * @route '/meets/{meet}/activate'
 */
activate.url = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { meet: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: typeof args.meet === 'object'
                ? args.meet.id
                : args.meet,
                }

    return activate.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetController::activate
 * @see app/Http/Controllers/MeetController.php:222
 * @route '/meets/{meet}/activate'
 */
activate.patch = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: activate.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\MeetController::deactivate
 * @see app/Http/Controllers/MeetController.php:258
 * @route '/meets/{meet}/deactivate'
 */
export const deactivate = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: deactivate.url(args, options),
    method: 'patch',
})

deactivate.definition = {
    methods: ["patch"],
    url: '/meets/{meet}/deactivate',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\MeetController::deactivate
 * @see app/Http/Controllers/MeetController.php:258
 * @route '/meets/{meet}/deactivate'
 */
deactivate.url = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { meet: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: typeof args.meet === 'object'
                ? args.meet.id
                : args.meet,
                }

    return deactivate.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetController::deactivate
 * @see app/Http/Controllers/MeetController.php:258
 * @route '/meets/{meet}/deactivate'
 */
deactivate.patch = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: deactivate.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\MeetController::events
 * @see app/Http/Controllers/MeetController.php:124
 * @route '/meets/{meet}/events'
 */
export const events = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: events.url(args, options),
    method: 'put',
})

events.definition = {
    methods: ["put"],
    url: '/meets/{meet}/events',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MeetController::events
 * @see app/Http/Controllers/MeetController.php:124
 * @route '/meets/{meet}/events'
 */
events.url = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meet: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { meet: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    meet: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meet: typeof args.meet === 'object'
                ? args.meet.id
                : args.meet,
                }

    return events.definition.url
            .replace('{meet}', parsedArgs.meet.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetController::events
 * @see app/Http/Controllers/MeetController.php:124
 * @route '/meets/{meet}/events'
 */
events.put = (args: { meet: number | { id: number } } | [meet: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: events.url(args, options),
    method: 'put',
})
const meets = {
    index: Object.assign(index, index),
update: Object.assign(update, update),
status: Object.assign(status, status),
publish: Object.assign(publish, publish),
unpublish: Object.assign(unpublish, unpublish),
activate: Object.assign(activate, activate),
deactivate: Object.assign(deactivate, deactivate),
events: Object.assign(events, events),
}

export default meets