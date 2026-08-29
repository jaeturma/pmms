import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SchoolDistrictController::index
 * @see app/Http/Controllers/SchoolDistrictController.php:30
 * @route '/school-districts'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/school-districts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SchoolDistrictController::index
 * @see app/Http/Controllers/SchoolDistrictController.php:30
 * @route '/school-districts'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolDistrictController::index
 * @see app/Http/Controllers/SchoolDistrictController.php:30
 * @route '/school-districts'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SchoolDistrictController::index
 * @see app/Http/Controllers/SchoolDistrictController.php:30
 * @route '/school-districts'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SchoolDistrictController::store
 * @see app/Http/Controllers/SchoolDistrictController.php:67
 * @route '/school-districts'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/school-districts',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SchoolDistrictController::store
 * @see app/Http/Controllers/SchoolDistrictController.php:67
 * @route '/school-districts'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolDistrictController::store
 * @see app/Http/Controllers/SchoolDistrictController.php:67
 * @route '/school-districts'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SchoolDistrictController::update
 * @see app/Http/Controllers/SchoolDistrictController.php:81
 * @route '/school-districts/{schoolDistrict}'
 */
export const update = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/school-districts/{schoolDistrict}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SchoolDistrictController::update
 * @see app/Http/Controllers/SchoolDistrictController.php:81
 * @route '/school-districts/{schoolDistrict}'
 */
update.url = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { schoolDistrict: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { schoolDistrict: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    schoolDistrict: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        schoolDistrict: typeof args.schoolDistrict === 'object'
                ? args.schoolDistrict.id
                : args.schoolDistrict,
                }

    return update.definition.url
            .replace('{schoolDistrict}', parsedArgs.schoolDistrict.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolDistrictController::update
 * @see app/Http/Controllers/SchoolDistrictController.php:81
 * @route '/school-districts/{schoolDistrict}'
 */
update.put = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\SchoolDistrictController::archive
 * @see app/Http/Controllers/SchoolDistrictController.php:95
 * @route '/school-districts/{schoolDistrict}/archive'
 */
export const archive = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

archive.definition = {
    methods: ["patch"],
    url: '/school-districts/{schoolDistrict}/archive',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\SchoolDistrictController::archive
 * @see app/Http/Controllers/SchoolDistrictController.php:95
 * @route '/school-districts/{schoolDistrict}/archive'
 */
archive.url = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { schoolDistrict: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { schoolDistrict: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    schoolDistrict: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        schoolDistrict: typeof args.schoolDistrict === 'object'
                ? args.schoolDistrict.id
                : args.schoolDistrict,
                }

    return archive.definition.url
            .replace('{schoolDistrict}', parsedArgs.schoolDistrict.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolDistrictController::archive
 * @see app/Http/Controllers/SchoolDistrictController.php:95
 * @route '/school-districts/{schoolDistrict}/archive'
 */
archive.patch = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: archive.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\SchoolDistrictController::restore
 * @see app/Http/Controllers/SchoolDistrictController.php:109
 * @route '/school-districts/{schoolDistrict}/restore'
 */
export const restore = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

restore.definition = {
    methods: ["patch"],
    url: '/school-districts/{schoolDistrict}/restore',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\SchoolDistrictController::restore
 * @see app/Http/Controllers/SchoolDistrictController.php:109
 * @route '/school-districts/{schoolDistrict}/restore'
 */
restore.url = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { schoolDistrict: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { schoolDistrict: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    schoolDistrict: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        schoolDistrict: typeof args.schoolDistrict === 'object'
                ? args.schoolDistrict.id
                : args.schoolDistrict,
                }

    return restore.definition.url
            .replace('{schoolDistrict}', parsedArgs.schoolDistrict.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolDistrictController::restore
 * @see app/Http/Controllers/SchoolDistrictController.php:109
 * @route '/school-districts/{schoolDistrict}/restore'
 */
restore.patch = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: restore.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\SchoolDistrictController::destroy
 * @see app/Http/Controllers/SchoolDistrictController.php:123
 * @route '/school-districts/{schoolDistrict}'
 */
export const destroy = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/school-districts/{schoolDistrict}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SchoolDistrictController::destroy
 * @see app/Http/Controllers/SchoolDistrictController.php:123
 * @route '/school-districts/{schoolDistrict}'
 */
destroy.url = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { schoolDistrict: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { schoolDistrict: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    schoolDistrict: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        schoolDistrict: typeof args.schoolDistrict === 'object'
                ? args.schoolDistrict.id
                : args.schoolDistrict,
                }

    return destroy.definition.url
            .replace('{schoolDistrict}', parsedArgs.schoolDistrict.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SchoolDistrictController::destroy
 * @see app/Http/Controllers/SchoolDistrictController.php:123
 * @route '/school-districts/{schoolDistrict}'
 */
destroy.delete = (args: { schoolDistrict: number | { id: number } } | [schoolDistrict: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const schoolDistricts = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
archive: Object.assign(archive, archive),
restore: Object.assign(restore, restore),
destroy: Object.assign(destroy, destroy),
}

export default schoolDistricts