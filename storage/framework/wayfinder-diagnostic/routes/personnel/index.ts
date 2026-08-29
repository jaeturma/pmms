import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\PersonnelController::index
 * @see app/Http/Controllers/PersonnelController.php:37
 * @route '/personnel'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/personnel',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PersonnelController::index
 * @see app/Http/Controllers/PersonnelController.php:37
 * @route '/personnel'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PersonnelController::index
 * @see app/Http/Controllers/PersonnelController.php:37
 * @route '/personnel'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PersonnelController::index
 * @see app/Http/Controllers/PersonnelController.php:37
 * @route '/personnel'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PersonnelController::photo
 * @see app/Http/Controllers/PersonnelController.php:119
 * @route '/personnel/{personnel}/photo'
 */
export const photo = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})

photo.definition = {
    methods: ["get","head"],
    url: '/personnel/{personnel}/photo',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\PersonnelController::photo
 * @see app/Http/Controllers/PersonnelController.php:119
 * @route '/personnel/{personnel}/photo'
 */
photo.url = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { personnel: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { personnel: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    personnel: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        personnel: typeof args.personnel === 'object'
                ? args.personnel.id
                : args.personnel,
                }

    return photo.definition.url
            .replace('{personnel}', parsedArgs.personnel.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PersonnelController::photo
 * @see app/Http/Controllers/PersonnelController.php:119
 * @route '/personnel/{personnel}/photo'
 */
photo.get = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: photo.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\PersonnelController::photo
 * @see app/Http/Controllers/PersonnelController.php:119
 * @route '/personnel/{personnel}/photo'
 */
photo.head = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: photo.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\PersonnelController::store
 * @see app/Http/Controllers/PersonnelController.php:133
 * @route '/personnel'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/personnel',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\PersonnelController::store
 * @see app/Http/Controllers/PersonnelController.php:133
 * @route '/personnel'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\PersonnelController::store
 * @see app/Http/Controllers/PersonnelController.php:133
 * @route '/personnel'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\PersonnelController::update
 * @see app/Http/Controllers/PersonnelController.php:166
 * @route '/personnel/{personnel}'
 */
export const update = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/personnel/{personnel}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\PersonnelController::update
 * @see app/Http/Controllers/PersonnelController.php:166
 * @route '/personnel/{personnel}'
 */
update.url = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { personnel: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { personnel: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    personnel: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        personnel: typeof args.personnel === 'object'
                ? args.personnel.id
                : args.personnel,
                }

    return update.definition.url
            .replace('{personnel}', parsedArgs.personnel.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PersonnelController::update
 * @see app/Http/Controllers/PersonnelController.php:166
 * @route '/personnel/{personnel}'
 */
update.put = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\PersonnelController::sports
 * @see app/Http/Controllers/PersonnelController.php:202
 * @route '/personnel/{personnel}/sports'
 */
export const sports = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: sports.url(args, options),
    method: 'put',
})

sports.definition = {
    methods: ["put"],
    url: '/personnel/{personnel}/sports',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\PersonnelController::sports
 * @see app/Http/Controllers/PersonnelController.php:202
 * @route '/personnel/{personnel}/sports'
 */
sports.url = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { personnel: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { personnel: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    personnel: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        personnel: typeof args.personnel === 'object'
                ? args.personnel.id
                : args.personnel,
                }

    return sports.definition.url
            .replace('{personnel}', parsedArgs.personnel.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PersonnelController::sports
 * @see app/Http/Controllers/PersonnelController.php:202
 * @route '/personnel/{personnel}/sports'
 */
sports.put = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: sports.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\PersonnelController::destroy
 * @see app/Http/Controllers/PersonnelController.php:238
 * @route '/personnel/{personnel}'
 */
export const destroy = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/personnel/{personnel}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\PersonnelController::destroy
 * @see app/Http/Controllers/PersonnelController.php:238
 * @route '/personnel/{personnel}'
 */
destroy.url = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { personnel: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { personnel: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    personnel: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        personnel: typeof args.personnel === 'object'
                ? args.personnel.id
                : args.personnel,
                }

    return destroy.definition.url
            .replace('{personnel}', parsedArgs.personnel.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\PersonnelController::destroy
 * @see app/Http/Controllers/PersonnelController.php:238
 * @route '/personnel/{personnel}'
 */
destroy.delete = (args: { personnel: number | { id: number } } | [personnel: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const personnel = {
    index: Object.assign(index, index),
photo: Object.assign(photo, photo),
store: Object.assign(store, store),
update: Object.assign(update, update),
sports: Object.assign(sports, sports),
destroy: Object.assign(destroy, destroy),
}

export default personnel