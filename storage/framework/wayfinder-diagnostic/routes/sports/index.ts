import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SportController::photo
 * @see app/Http/Controllers/SportController.php:260
 * @route '/sports/{sport}/photo'
 */
export const photo = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})

photo.definition = {
    methods: ["get","head"],
    url: '/sports/{sport}/photo',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SportController::photo
 * @see app/Http/Controllers/SportController.php:260
 * @route '/sports/{sport}/photo'
 */
photo.url = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { sport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    sport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sport: typeof args.sport === 'object'
                ? args.sport.id
                : args.sport,
                }

    return photo.definition.url
            .replace('{sport}', parsedArgs.sport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportController::photo
 * @see app/Http/Controllers/SportController.php:260
 * @route '/sports/{sport}/photo'
 */
photo.get = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SportController::photo
 * @see app/Http/Controllers/SportController.php:260
 * @route '/sports/{sport}/photo'
 */
photo.head = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: photo.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SportController::index
 * @see app/Http/Controllers/SportController.php:37
 * @route '/sports'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/sports',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SportController::index
 * @see app/Http/Controllers/SportController.php:37
 * @route '/sports'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportController::index
 * @see app/Http/Controllers/SportController.php:37
 * @route '/sports'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SportController::index
 * @see app/Http/Controllers/SportController.php:37
 * @route '/sports'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SportController::store
 * @see app/Http/Controllers/SportController.php:116
 * @route '/sports'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/sports',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SportController::store
 * @see app/Http/Controllers/SportController.php:116
 * @route '/sports'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportController::store
 * @see app/Http/Controllers/SportController.php:116
 * @route '/sports'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SportController::update
 * @see app/Http/Controllers/SportController.php:136
 * @route '/sports/{sport}'
 */
export const update = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/sports/{sport}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SportController::update
 * @see app/Http/Controllers/SportController.php:136
 * @route '/sports/{sport}'
 */
update.url = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { sport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    sport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sport: typeof args.sport === 'object'
                ? args.sport.id
                : args.sport,
                }

    return update.definition.url
            .replace('{sport}', parsedArgs.sport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportController::update
 * @see app/Http/Controllers/SportController.php:136
 * @route '/sports/{sport}'
 */
update.put = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SportController::archive
 * @see app/Http/Controllers/SportController.php:165
 * @route '/sports/{sport}/archive'
 */
export const archive = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

archive.definition = {
    methods: ["patch"],
    url: '/sports/{sport}/archive',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\SportController::archive
 * @see app/Http/Controllers/SportController.php:165
 * @route '/sports/{sport}/archive'
 */
archive.url = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { sport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    sport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sport: typeof args.sport === 'object'
                ? args.sport.id
                : args.sport,
                }

    return archive.definition.url
            .replace('{sport}', parsedArgs.sport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportController::archive
 * @see app/Http/Controllers/SportController.php:165
 * @route '/sports/{sport}/archive'
 */
archive.patch = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\SportController::restore
 * @see app/Http/Controllers/SportController.php:179
 * @route '/sports/{sport}/restore'
 */
export const restore = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

restore.definition = {
    methods: ["patch"],
    url: '/sports/{sport}/restore',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\SportController::restore
 * @see app/Http/Controllers/SportController.php:179
 * @route '/sports/{sport}/restore'
 */
restore.url = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { sport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    sport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sport: typeof args.sport === 'object'
                ? args.sport.id
                : args.sport,
                }

    return restore.definition.url
            .replace('{sport}', parsedArgs.sport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportController::restore
 * @see app/Http/Controllers/SportController.php:179
 * @route '/sports/{sport}/restore'
 */
restore.patch = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\SportController::technicalOfficials
 * @see app/Http/Controllers/SportController.php:195
 * @route '/sports/{sport}/technical-officials'
 */
export const technicalOfficials = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: technicalOfficials.url(args, options),
    method: 'put',
})

technicalOfficials.definition = {
    methods: ["put"],
    url: '/sports/{sport}/technical-officials',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SportController::technicalOfficials
 * @see app/Http/Controllers/SportController.php:195
 * @route '/sports/{sport}/technical-officials'
 */
technicalOfficials.url = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { sport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    sport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sport: typeof args.sport === 'object'
                ? args.sport.id
                : args.sport,
                }

    return technicalOfficials.definition.url
            .replace('{sport}', parsedArgs.sport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportController::technicalOfficials
 * @see app/Http/Controllers/SportController.php:195
 * @route '/sports/{sport}/technical-officials'
 */
technicalOfficials.put = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: technicalOfficials.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SportController::tournamentManager
 * @see app/Http/Controllers/SportController.php:228
 * @route '/sports/{sport}/tournament-manager'
 */
export const tournamentManager = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: tournamentManager.url(args, options),
    method: 'put',
})

tournamentManager.definition = {
    methods: ["put"],
    url: '/sports/{sport}/tournament-manager',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SportController::tournamentManager
 * @see app/Http/Controllers/SportController.php:228
 * @route '/sports/{sport}/tournament-manager'
 */
tournamentManager.url = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { sport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    sport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sport: typeof args.sport === 'object'
                ? args.sport.id
                : args.sport,
                }

    return tournamentManager.definition.url
            .replace('{sport}', parsedArgs.sport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportController::tournamentManager
 * @see app/Http/Controllers/SportController.php:228
 * @route '/sports/{sport}/tournament-manager'
 */
tournamentManager.put = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: tournamentManager.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SportController::destroy
 * @see app/Http/Controllers/SportController.php:272
 * @route '/sports/{sport}'
 */
export const destroy = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/sports/{sport}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SportController::destroy
 * @see app/Http/Controllers/SportController.php:272
 * @route '/sports/{sport}'
 */
destroy.url = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { sport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { sport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    sport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        sport: typeof args.sport === 'object'
                ? args.sport.id
                : args.sport,
                }

    return destroy.definition.url
            .replace('{sport}', parsedArgs.sport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SportController::destroy
 * @see app/Http/Controllers/SportController.php:272
 * @route '/sports/{sport}'
 */
destroy.delete = (args: { sport: number | { id: number } } | [sport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const sports = {
    photo: Object.assign(photo, photo),
index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
archive: Object.assign(archive, archive),
restore: Object.assign(restore, restore),
technicalOfficials: Object.assign(technicalOfficials, technicalOfficials),
tournamentManager: Object.assign(tournamentManager, tournamentManager),
destroy: Object.assign(destroy, destroy),
}

export default sports