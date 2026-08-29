import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\FileUploadController::store
 * @see app/Http/Controllers/FileUploadController.php:27
 * @route '/uploads'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/uploads',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\FileUploadController::store
 * @see app/Http/Controllers/FileUploadController.php:27
 * @route '/uploads'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\FileUploadController::store
 * @see app/Http/Controllers/FileUploadController.php:27
 * @route '/uploads'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\FileUploadController::download
 * @see app/Http/Controllers/FileUploadController.php:45
 * @route '/uploads/{upload}'
 */
export const download = (args: { upload: number | { id: number } } | [upload: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/uploads/{upload}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\FileUploadController::download
 * @see app/Http/Controllers/FileUploadController.php:45
 * @route '/uploads/{upload}'
 */
download.url = (args: { upload: number | { id: number } } | [upload: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { upload: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { upload: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    upload: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        upload: typeof args.upload === 'object'
                ? args.upload.id
                : args.upload,
                }

    return download.definition.url
            .replace('{upload}', parsedArgs.upload.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\FileUploadController::download
 * @see app/Http/Controllers/FileUploadController.php:45
 * @route '/uploads/{upload}'
 */
download.get = (args: { upload: number | { id: number } } | [upload: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\FileUploadController::download
 * @see app/Http/Controllers/FileUploadController.php:45
 * @route '/uploads/{upload}'
 */
download.head = (args: { upload: number | { id: number } } | [upload: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\FileUploadController::destroy
 * @see app/Http/Controllers/FileUploadController.php:59
 * @route '/uploads/{upload}'
 */
export const destroy = (args: { upload: number | { id: number } } | [upload: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/uploads/{upload}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\FileUploadController::destroy
 * @see app/Http/Controllers/FileUploadController.php:59
 * @route '/uploads/{upload}'
 */
destroy.url = (args: { upload: number | { id: number } } | [upload: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { upload: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { upload: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    upload: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        upload: typeof args.upload === 'object'
                ? args.upload.id
                : args.upload,
                }

    return destroy.definition.url
            .replace('{upload}', parsedArgs.upload.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\FileUploadController::destroy
 * @see app/Http/Controllers/FileUploadController.php:59
 * @route '/uploads/{upload}'
 */
destroy.delete = (args: { upload: number | { id: number } } | [upload: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const uploads = {
    store: Object.assign(store, store),
download: Object.assign(download, download),
destroy: Object.assign(destroy, destroy),
}

export default uploads