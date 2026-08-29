import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MeetSportAssignmentController::index
 * @see app/Http/Controllers/MeetSportAssignmentController.php:40
 * @route '/meet-sport-assignments'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/meet-sport-assignments',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::index
 * @see app/Http/Controllers/MeetSportAssignmentController.php:40
 * @route '/meet-sport-assignments'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::index
 * @see app/Http/Controllers/MeetSportAssignmentController.php:40
 * @route '/meet-sport-assignments'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\MeetSportAssignmentController::index
 * @see app/Http/Controllers/MeetSportAssignmentController.php:40
 * @route '/meet-sport-assignments'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::store
 * @see app/Http/Controllers/MeetSportAssignmentController.php:120
 * @route '/meet-sport-assignments'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/meet-sport-assignments',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::store
 * @see app/Http/Controllers/MeetSportAssignmentController.php:120
 * @route '/meet-sport-assignments'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::store
 * @see app/Http/Controllers/MeetSportAssignmentController.php:120
 * @route '/meet-sport-assignments'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::status
 * @see app/Http/Controllers/MeetSportAssignmentController.php:199
 * @route '/meet-sport-assignments/{meetSportAssignment}/status'
 */
export const status = (args: { meetSportAssignment: number | { id: number } } | [meetSportAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

status.definition = {
    methods: ["patch"],
    url: '/meet-sport-assignments/{meetSportAssignment}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::status
 * @see app/Http/Controllers/MeetSportAssignmentController.php:199
 * @route '/meet-sport-assignments/{meetSportAssignment}/status'
 */
status.url = (args: { meetSportAssignment: number | { id: number } } | [meetSportAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meetSportAssignment: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { meetSportAssignment: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    meetSportAssignment: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meetSportAssignment: typeof args.meetSportAssignment === 'object'
                ? args.meetSportAssignment.id
                : args.meetSportAssignment,
                }

    return status.definition.url
            .replace('{meetSportAssignment}', parsedArgs.meetSportAssignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::status
 * @see app/Http/Controllers/MeetSportAssignmentController.php:199
 * @route '/meet-sport-assignments/{meetSportAssignment}/status'
 */
status.patch = (args: { meetSportAssignment: number | { id: number } } | [meetSportAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: status.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::destroy
 * @see app/Http/Controllers/MeetSportAssignmentController.php:228
 * @route '/meet-sport-assignments/{meetSportAssignment}'
 */
export const destroy = (args: { meetSportAssignment: number | { id: number } } | [meetSportAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/meet-sport-assignments/{meetSportAssignment}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::destroy
 * @see app/Http/Controllers/MeetSportAssignmentController.php:228
 * @route '/meet-sport-assignments/{meetSportAssignment}'
 */
destroy.url = (args: { meetSportAssignment: number | { id: number } } | [meetSportAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { meetSportAssignment: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { meetSportAssignment: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    meetSportAssignment: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        meetSportAssignment: typeof args.meetSportAssignment === 'object'
                ? args.meetSportAssignment.id
                : args.meetSportAssignment,
                }

    return destroy.definition.url
            .replace('{meetSportAssignment}', parsedArgs.meetSportAssignment.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MeetSportAssignmentController::destroy
 * @see app/Http/Controllers/MeetSportAssignmentController.php:228
 * @route '/meet-sport-assignments/{meetSportAssignment}'
 */
destroy.delete = (args: { meetSportAssignment: number | { id: number } } | [meetSportAssignment: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const meetSportAssignments = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
status: Object.assign(status, status),
destroy: Object.assign(destroy, destroy),
}

export default meetSportAssignments