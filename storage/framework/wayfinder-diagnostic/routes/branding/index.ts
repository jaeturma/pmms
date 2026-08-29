import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SystemSettingsController::logo
 * @see app/Http/Controllers/SystemSettingsController.php:175
 * @route '/branding/logo'
 */
export const logo = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logo.url(options),
    method: 'get',
})

logo.definition = {
    methods: ["get","head"],
    url: '/branding/logo',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemSettingsController::logo
 * @see app/Http/Controllers/SystemSettingsController.php:175
 * @route '/branding/logo'
 */
logo.url = (options?: RouteQueryOptions) => {
    return logo.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemSettingsController::logo
 * @see app/Http/Controllers/SystemSettingsController.php:175
 * @route '/branding/logo'
 */
logo.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logo.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SystemSettingsController::logo
 * @see app/Http/Controllers/SystemSettingsController.php:175
 * @route '/branding/logo'
 */
logo.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: logo.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SystemSettingsController::favicon
 * @see app/Http/Controllers/SystemSettingsController.php:191
 * @route '/branding/favicon'
 */
export const favicon = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: favicon.url(options),
    method: 'get',
})

favicon.definition = {
    methods: ["get","head"],
    url: '/branding/favicon',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemSettingsController::favicon
 * @see app/Http/Controllers/SystemSettingsController.php:191
 * @route '/branding/favicon'
 */
favicon.url = (options?: RouteQueryOptions) => {
    return favicon.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemSettingsController::favicon
 * @see app/Http/Controllers/SystemSettingsController.php:191
 * @route '/branding/favicon'
 */
favicon.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: favicon.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SystemSettingsController::favicon
 * @see app/Http/Controllers/SystemSettingsController.php:191
 * @route '/branding/favicon'
 */
favicon.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: favicon.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SystemSettingsController::loginBackground
 * @see app/Http/Controllers/SystemSettingsController.php:183
 * @route '/branding/login-background'
 */
export const loginBackground = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loginBackground.url(options),
    method: 'get',
})

loginBackground.definition = {
    methods: ["get","head"],
    url: '/branding/login-background',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemSettingsController::loginBackground
 * @see app/Http/Controllers/SystemSettingsController.php:183
 * @route '/branding/login-background'
 */
loginBackground.url = (options?: RouteQueryOptions) => {
    return loginBackground.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemSettingsController::loginBackground
 * @see app/Http/Controllers/SystemSettingsController.php:183
 * @route '/branding/login-background'
 */
loginBackground.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loginBackground.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SystemSettingsController::loginBackground
 * @see app/Http/Controllers/SystemSettingsController.php:183
 * @route '/branding/login-background'
 */
loginBackground.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loginBackground.url(options),
    method: 'head',
})
const branding = {
    logo: Object.assign(logo, logo),
favicon: Object.assign(favicon, favicon),
loginBackground: Object.assign(loginBackground, loginBackground),
}

export default branding