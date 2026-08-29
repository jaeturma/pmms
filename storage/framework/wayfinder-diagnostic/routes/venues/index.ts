import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\VenueController::index
 * @see app/Http/Controllers/VenueController.php:31
 * @route '/venues'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/venues',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\VenueController::index
 * @see app/Http/Controllers/VenueController.php:31
 * @route '/venues'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VenueController::index
 * @see app/Http/Controllers/VenueController.php:31
 * @route '/venues'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\VenueController::index
 * @see app/Http/Controllers/VenueController.php:31
 * @route '/venues'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\VenueController::archive
 * @see app/Http/Controllers/VenueController.php:134
 * @route '/venues/{venue}/archive'
 */
export const archive = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

archive.definition = {
    methods: ["patch"],
    url: '/venues/{venue}/archive',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\VenueController::archive
 * @see app/Http/Controllers/VenueController.php:134
 * @route '/venues/{venue}/archive'
 */
archive.url = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { venue: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { venue: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    venue: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        venue: typeof args.venue === 'object'
                ? args.venue.id
                : args.venue,
                }

    return archive.definition.url
            .replace('{venue}', parsedArgs.venue.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VenueController::archive
 * @see app/Http/Controllers/VenueController.php:134
 * @route '/venues/{venue}/archive'
 */
archive.patch = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\VenueController::restore
 * @see app/Http/Controllers/VenueController.php:148
 * @route '/venues/{venue}/restore'
 */
export const restore = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

restore.definition = {
    methods: ["patch"],
    url: '/venues/{venue}/restore',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\VenueController::restore
 * @see app/Http/Controllers/VenueController.php:148
 * @route '/venues/{venue}/restore'
 */
restore.url = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { venue: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { venue: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    venue: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        venue: typeof args.venue === 'object'
                ? args.venue.id
                : args.venue,
                }

    return restore.definition.url
            .replace('{venue}', parsedArgs.venue.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VenueController::restore
 * @see app/Http/Controllers/VenueController.php:148
 * @route '/venues/{venue}/restore'
 */
restore.patch = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\VenueController::destroy
 * @see app/Http/Controllers/VenueController.php:162
 * @route '/venues/{venue}'
 */
export const destroy = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/venues/{venue}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\VenueController::destroy
 * @see app/Http/Controllers/VenueController.php:162
 * @route '/venues/{venue}'
 */
destroy.url = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { venue: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { venue: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    venue: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        venue: typeof args.venue === 'object'
                ? args.venue.id
                : args.venue,
                }

    return destroy.definition.url
            .replace('{venue}', parsedArgs.venue.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VenueController::destroy
 * @see app/Http/Controllers/VenueController.php:162
 * @route '/venues/{venue}'
 */
destroy.delete = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\VenueController::store
 * @see app/Http/Controllers/VenueController.php:95
 * @route '/venues'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/venues',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\VenueController::store
 * @see app/Http/Controllers/VenueController.php:95
 * @route '/venues'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\VenueController::store
 * @see app/Http/Controllers/VenueController.php:95
 * @route '/venues'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\VenueController::update
 * @see app/Http/Controllers/VenueController.php:119
 * @route '/venues/{venue}'
 */
export const update = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/venues/{venue}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\VenueController::update
 * @see app/Http/Controllers/VenueController.php:119
 * @route '/venues/{venue}'
 */
update.url = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { venue: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { venue: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    venue: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        venue: typeof args.venue === 'object'
                ? args.venue.id
                : args.venue,
                }

    return update.definition.url
            .replace('{venue}', parsedArgs.venue.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\VenueController::update
 * @see app/Http/Controllers/VenueController.php:119
 * @route '/venues/{venue}'
 */
update.put = (args: { venue: number | { id: number } } | [venue: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})
const venues = {
    index: Object.assign(index, index),
archive: Object.assign(archive, archive),
restore: Object.assign(restore, restore),
destroy: Object.assign(destroy, destroy),
store: Object.assign(store, store),
update: Object.assign(update, update),
}

export default venues