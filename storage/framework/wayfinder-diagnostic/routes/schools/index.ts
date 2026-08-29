import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SchoolController::index
 * @see app/Http/Controllers/SchoolController.php:25
 * @route '/schools'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/schools',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SchoolController::index
 * @see app/Http/Controllers/SchoolController.php:25
 * @route '/schools'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolController::index
 * @see app/Http/Controllers/SchoolController.php:25
 * @route '/schools'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SchoolController::index
 * @see app/Http/Controllers/SchoolController.php:25
 * @route '/schools'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SchoolController::store
 * @see app/Http/Controllers/SchoolController.php:74
 * @route '/schools'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/schools',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SchoolController::store
 * @see app/Http/Controllers/SchoolController.php:74
 * @route '/schools'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolController::store
 * @see app/Http/Controllers/SchoolController.php:74
 * @route '/schools'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SchoolController::update
 * @see app/Http/Controllers/SchoolController.php:88
 * @route '/schools/{school}'
 */
export const update = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/schools/{school}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SchoolController::update
 * @see app/Http/Controllers/SchoolController.php:88
 * @route '/schools/{school}'
 */
update.url = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { school: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { school: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    school: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        school: typeof args.school === 'object'
                ? args.school.id
                : args.school,
                }

    return update.definition.url
            .replace('{school}', parsedArgs.school.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolController::update
 * @see app/Http/Controllers/SchoolController.php:88
 * @route '/schools/{school}'
 */
update.put = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SchoolController::archive
 * @see app/Http/Controllers/SchoolController.php:102
 * @route '/schools/{school}/archive'
 */
export const archive = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

archive.definition = {
    methods: ["patch"],
    url: '/schools/{school}/archive',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\SchoolController::archive
 * @see app/Http/Controllers/SchoolController.php:102
 * @route '/schools/{school}/archive'
 */
archive.url = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { school: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { school: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    school: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        school: typeof args.school === 'object'
                ? args.school.id
                : args.school,
                }

    return archive.definition.url
            .replace('{school}', parsedArgs.school.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolController::archive
 * @see app/Http/Controllers/SchoolController.php:102
 * @route '/schools/{school}/archive'
 */
archive.patch = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\SchoolController::restore
 * @see app/Http/Controllers/SchoolController.php:117
 * @route '/schools/{school}/restore'
 */
export const restore = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

restore.definition = {
    methods: ["patch"],
    url: '/schools/{school}/restore',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\SchoolController::restore
 * @see app/Http/Controllers/SchoolController.php:117
 * @route '/schools/{school}/restore'
 */
restore.url = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { school: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { school: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    school: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        school: typeof args.school === 'object'
                ? args.school.id
                : args.school,
                }

    return restore.definition.url
            .replace('{school}', parsedArgs.school.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolController::restore
 * @see app/Http/Controllers/SchoolController.php:117
 * @route '/schools/{school}/restore'
 */
restore.patch = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\SchoolController::destroy
 * @see app/Http/Controllers/SchoolController.php:132
 * @route '/schools/{school}'
 */
export const destroy = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/schools/{school}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SchoolController::destroy
 * @see app/Http/Controllers/SchoolController.php:132
 * @route '/schools/{school}'
 */
destroy.url = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { school: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { school: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    school: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        school: typeof args.school === 'object'
                ? args.school.id
                : args.school,
                }

    return destroy.definition.url
            .replace('{school}', parsedArgs.school.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolController::destroy
 * @see app/Http/Controllers/SchoolController.php:132
 * @route '/schools/{school}'
 */
destroy.delete = (args: { school: number | { id: number } } | [school: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const schools = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
archive: Object.assign(archive, archive),
restore: Object.assign(restore, restore),
destroy: Object.assign(destroy, destroy),
}

export default schools