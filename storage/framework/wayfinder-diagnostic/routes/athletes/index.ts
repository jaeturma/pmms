import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AthleteController::index
 * @see app/Http/Controllers/AthleteController.php:57
 * @route '/athletes'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/athletes',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AthleteController::index
 * @see app/Http/Controllers/AthleteController.php:57
 * @route '/athletes'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AthleteController::index
 * @see app/Http/Controllers/AthleteController.php:57
 * @route '/athletes'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AthleteController::index
 * @see app/Http/Controllers/AthleteController.php:57
 * @route '/athletes'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EligibilityController::eligibility
 * @see app/Http/Controllers/EligibilityController.php:51
 * @route '/athletes/eligibility'
 */
export const eligibility = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eligibility.url(options),
    method: 'get',
})

eligibility.definition = {
    methods: ["get","head"],
    url: '/athletes/eligibility',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EligibilityController::eligibility
 * @see app/Http/Controllers/EligibilityController.php:51
 * @route '/athletes/eligibility'
 */
eligibility.url = (options?: RouteQueryOptions) => {
    return eligibility.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EligibilityController::eligibility
 * @see app/Http/Controllers/EligibilityController.php:51
 * @route '/athletes/eligibility'
 */
eligibility.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eligibility.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\EligibilityController::eligibility
 * @see app/Http/Controllers/EligibilityController.php:51
 * @route '/athletes/eligibility'
 */
eligibility.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: eligibility.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AthleteController::edit
 * @see app/Http/Controllers/AthleteController.php:248
 * @route '/athletes/{athlete}/edit'
 */
export const edit = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/athletes/{athlete}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AthleteController::edit
 * @see app/Http/Controllers/AthleteController.php:248
 * @route '/athletes/{athlete}/edit'
 */
edit.url = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { athlete: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { athlete: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    athlete: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        athlete: typeof args.athlete === 'object'
                ? args.athlete.id
                : args.athlete,
                }

    return edit.definition.url
            .replace('{athlete}', parsedArgs.athlete.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AthleteController::edit
 * @see app/Http/Controllers/AthleteController.php:248
 * @route '/athletes/{athlete}/edit'
 */
edit.get = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AthleteController::edit
 * @see app/Http/Controllers/AthleteController.php:248
 * @route '/athletes/{athlete}/edit'
 */
edit.head = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AthleteController::show
 * @see app/Http/Controllers/AthleteController.php:280
 * @route '/athletes/{athlete}'
 */
export const show = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/athletes/{athlete}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AthleteController::show
 * @see app/Http/Controllers/AthleteController.php:280
 * @route '/athletes/{athlete}'
 */
show.url = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { athlete: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { athlete: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    athlete: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        athlete: typeof args.athlete === 'object'
                ? args.athlete.id
                : args.athlete,
                }

    return show.definition.url
            .replace('{athlete}', parsedArgs.athlete.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AthleteController::show
 * @see app/Http/Controllers/AthleteController.php:280
 * @route '/athletes/{athlete}'
 */
show.get = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AthleteController::show
 * @see app/Http/Controllers/AthleteController.php:280
 * @route '/athletes/{athlete}'
 */
show.head = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AthleteController::photo
 * @see app/Http/Controllers/AthleteController.php:378
 * @route '/athletes/{athlete}/photo'
 */
export const photo = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})

photo.definition = {
    methods: ["get","head"],
    url: '/athletes/{athlete}/photo',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AthleteController::photo
 * @see app/Http/Controllers/AthleteController.php:378
 * @route '/athletes/{athlete}/photo'
 */
photo.url = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { athlete: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { athlete: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    athlete: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        athlete: typeof args.athlete === 'object'
                ? args.athlete.id
                : args.athlete,
                }

    return photo.definition.url
            .replace('{athlete}', parsedArgs.athlete.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AthleteController::photo
 * @see app/Http/Controllers/AthleteController.php:378
 * @route '/athletes/{athlete}/photo'
 */
photo.get = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AthleteController::photo
 * @see app/Http/Controllers/AthleteController.php:378
 * @route '/athletes/{athlete}/photo'
 */
photo.head = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: photo.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AthleteController::sportsPhoto
 * @see app/Http/Controllers/AthleteController.php:393
 * @route '/athletes/{athlete}/sports-photo'
 */
export const sportsPhoto = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sportsPhoto.url(args, options),
    method: 'get',
})

sportsPhoto.definition = {
    methods: ["get","head"],
    url: '/athletes/{athlete}/sports-photo',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AthleteController::sportsPhoto
 * @see app/Http/Controllers/AthleteController.php:393
 * @route '/athletes/{athlete}/sports-photo'
 */
sportsPhoto.url = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { athlete: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { athlete: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    athlete: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        athlete: typeof args.athlete === 'object'
                ? args.athlete.id
                : args.athlete,
                }

    return sportsPhoto.definition.url
            .replace('{athlete}', parsedArgs.athlete.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AthleteController::sportsPhoto
 * @see app/Http/Controllers/AthleteController.php:393
 * @route '/athletes/{athlete}/sports-photo'
 */
sportsPhoto.get = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: sportsPhoto.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AthleteController::sportsPhoto
 * @see app/Http/Controllers/AthleteController.php:393
 * @route '/athletes/{athlete}/sports-photo'
 */
sportsPhoto.head = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: sportsPhoto.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AthleteController::store
 * @see app/Http/Controllers/AthleteController.php:407
 * @route '/athletes'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/athletes',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AthleteController::store
 * @see app/Http/Controllers/AthleteController.php:407
 * @route '/athletes'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AthleteController::store
 * @see app/Http/Controllers/AthleteController.php:407
 * @route '/athletes'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AthleteController::update
 * @see app/Http/Controllers/AthleteController.php:502
 * @route '/athletes/{athlete}'
 */
export const update = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/athletes/{athlete}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\AthleteController::update
 * @see app/Http/Controllers/AthleteController.php:502
 * @route '/athletes/{athlete}'
 */
update.url = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { athlete: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { athlete: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    athlete: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        athlete: typeof args.athlete === 'object'
                ? args.athlete.id
                : args.athlete,
                }

    return update.definition.url
            .replace('{athlete}', parsedArgs.athlete.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AthleteController::update
 * @see app/Http/Controllers/AthleteController.php:502
 * @route '/athletes/{athlete}'
 */
update.put = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\AthleteController::destroy
 * @see app/Http/Controllers/AthleteController.php:649
 * @route '/athletes/{athlete}'
 */
export const destroy = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/athletes/{athlete}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\AthleteController::destroy
 * @see app/Http/Controllers/AthleteController.php:649
 * @route '/athletes/{athlete}'
 */
destroy.url = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { athlete: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { athlete: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    athlete: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        athlete: typeof args.athlete === 'object'
                ? args.athlete.id
                : args.athlete,
                }

    return destroy.definition.url
            .replace('{athlete}', parsedArgs.athlete.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AthleteController::destroy
 * @see app/Http/Controllers/AthleteController.php:649
 * @route '/athletes/{athlete}'
 */
destroy.delete = (args: { athlete: number | { id: number } } | [athlete: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const athletes = {
    index: Object.assign(index, index),
eligibility: Object.assign(eligibility, eligibility),
edit: Object.assign(edit, edit),
show: Object.assign(show, show),
photo: Object.assign(photo, photo),
sportsPhoto: Object.assign(sportsPhoto, sportsPhoto),
store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default athletes