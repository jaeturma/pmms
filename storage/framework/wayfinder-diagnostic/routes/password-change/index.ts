import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\RequiredPasswordChangeController::edit
 * @see app/Http/Controllers/RequiredPasswordChangeController.php:17
 * @route '/change-password'
 */
export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/change-password',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\RequiredPasswordChangeController::edit
 * @see app/Http/Controllers/RequiredPasswordChangeController.php:17
 * @route '/change-password'
 */
edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RequiredPasswordChangeController::edit
 * @see app/Http/Controllers/RequiredPasswordChangeController.php:17
 * @route '/change-password'
 */
edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\RequiredPasswordChangeController::edit
 * @see app/Http/Controllers/RequiredPasswordChangeController.php:17
 * @route '/change-password'
 */
edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\RequiredPasswordChangeController::update
 * @see app/Http/Controllers/RequiredPasswordChangeController.php:24
 * @route '/change-password'
 */
export const update = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/change-password',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\RequiredPasswordChangeController::update
 * @see app/Http/Controllers/RequiredPasswordChangeController.php:24
 * @route '/change-password'
 */
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\RequiredPasswordChangeController::update
 * @see app/Http/Controllers/RequiredPasswordChangeController.php:24
 * @route '/change-password'
 */
update.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})
const passwordChange = {
    edit: Object.assign(edit, edit),
update: Object.assign(update, update),
}

export default passwordChange