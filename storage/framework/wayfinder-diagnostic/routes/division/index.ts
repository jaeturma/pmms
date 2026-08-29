import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DivisionController::logo
 * @see app/Http/Controllers/DivisionController.php:54
 * @route '/division/logo'
 */
export const logo = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logo.url(options),
    method: 'get',
})

logo.definition = {
    methods: ["get","head"],
    url: '/division/logo',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DivisionController::logo
 * @see app/Http/Controllers/DivisionController.php:54
 * @route '/division/logo'
 */
logo.url = (options?: RouteQueryOptions) => {
    return logo.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DivisionController::logo
 * @see app/Http/Controllers/DivisionController.php:54
 * @route '/division/logo'
 */
logo.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logo.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DivisionController::logo
 * @see app/Http/Controllers/DivisionController.php:54
 * @route '/division/logo'
 */
logo.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: logo.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DivisionController::heroIcon
 * @see app/Http/Controllers/DivisionController.php:67
 * @route '/division/hero-icon'
 */
export const heroIcon = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: heroIcon.url(options),
    method: 'get',
})

heroIcon.definition = {
    methods: ["get","head"],
    url: '/division/hero-icon',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DivisionController::heroIcon
 * @see app/Http/Controllers/DivisionController.php:67
 * @route '/division/hero-icon'
 */
heroIcon.url = (options?: RouteQueryOptions) => {
    return heroIcon.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DivisionController::heroIcon
 * @see app/Http/Controllers/DivisionController.php:67
 * @route '/division/hero-icon'
 */
heroIcon.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: heroIcon.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DivisionController::heroIcon
 * @see app/Http/Controllers/DivisionController.php:67
 * @route '/division/hero-icon'
 */
heroIcon.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: heroIcon.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DivisionController::edit
 * @see app/Http/Controllers/DivisionController.php:34
 * @route '/division'
 */
export const edit = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/division',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DivisionController::edit
 * @see app/Http/Controllers/DivisionController.php:34
 * @route '/division'
 */
edit.url = (options?: RouteQueryOptions) => {
    return edit.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DivisionController::edit
 * @see app/Http/Controllers/DivisionController.php:34
 * @route '/division'
 */
edit.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DivisionController::edit
 * @see app/Http/Controllers/DivisionController.php:34
 * @route '/division'
 */
edit.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DivisionController::update
 * @see app/Http/Controllers/DivisionController.php:82
 * @route '/division'
 */
export const update = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/division',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\DivisionController::update
 * @see app/Http/Controllers/DivisionController.php:82
 * @route '/division'
 */
update.url = (options?: RouteQueryOptions) => {
    return update.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DivisionController::update
 * @see app/Http/Controllers/DivisionController.php:82
 * @route '/division'
 */
update.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(options),
    method: 'patch',
})
const division = {
    logo: Object.assign(logo, logo),
heroIcon: Object.assign(heroIcon, heroIcon),
edit: Object.assign(edit, edit),
update: Object.assign(update, update),
}

export default division