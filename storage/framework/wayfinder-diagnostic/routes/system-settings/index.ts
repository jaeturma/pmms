import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SystemSettingsController::edit
 * @see app/Http/Controllers/SystemSettingsController.php:33
 * @route '/system-settings'
 */
export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/system-settings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemSettingsController::edit
 * @see app/Http/Controllers/SystemSettingsController.php:33
 * @route '/system-settings'
 */
edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemSettingsController::edit
 * @see app/Http/Controllers/SystemSettingsController.php:33
 * @route '/system-settings'
 */
edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SystemSettingsController::edit
 * @see app/Http/Controllers/SystemSettingsController.php:33
 * @route '/system-settings'
 */
edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SystemSettingsController::update
 * @see app/Http/Controllers/SystemSettingsController.php:72
 * @route '/system-settings'
 */
export const update = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/system-settings',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\SystemSettingsController::update
 * @see app/Http/Controllers/SystemSettingsController.php:72
 * @route '/system-settings'
 */
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemSettingsController::update
 * @see app/Http/Controllers/SystemSettingsController.php:72
 * @route '/system-settings'
 */
update.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(options),
    method: 'put',
})
const systemSettings = {
    edit: Object.assign(edit, edit),
update: Object.assign(update, update),
}

export default systemSettings