import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AccountProvisionController::index
 * @see app/Http/Controllers/AccountProvisionController.php:22
 * @route '/account-provisions'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/account-provisions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AccountProvisionController::index
 * @see app/Http/Controllers/AccountProvisionController.php:22
 * @route '/account-provisions'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccountProvisionController::index
 * @see app/Http/Controllers/AccountProvisionController.php:22
 * @route '/account-provisions'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AccountProvisionController::index
 * @see app/Http/Controllers/AccountProvisionController.php:22
 * @route '/account-provisions'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\AccountProvisionController::invite
 * @see app/Http/Controllers/AccountProvisionController.php:65
 * @route '/account-provisions/{accountProvision}/invite'
 */
export const invite = (args: { accountProvision: string | number | { id: string | number } } | [accountProvision: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: invite.url(args, options),
    method: 'post',
})

invite.definition = {
    methods: ["post"],
    url: '/account-provisions/{accountProvision}/invite',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AccountProvisionController::invite
 * @see app/Http/Controllers/AccountProvisionController.php:65
 * @route '/account-provisions/{accountProvision}/invite'
 */
invite.url = (args: { accountProvision: string | number | { id: string | number } } | [accountProvision: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { accountProvision: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { accountProvision: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    accountProvision: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        accountProvision: typeof args.accountProvision === 'object'
                ? args.accountProvision.id
                : args.accountProvision,
                }

    return invite.definition.url
            .replace('{accountProvision}', parsedArgs.accountProvision.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccountProvisionController::invite
 * @see app/Http/Controllers/AccountProvisionController.php:65
 * @route '/account-provisions/{accountProvision}/invite'
 */
invite.post = (args: { accountProvision: string | number | { id: string | number } } | [accountProvision: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: invite.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\AccountProvisionController::resetPassword
 * @see app/Http/Controllers/AccountProvisionController.php:110
 * @route '/account-provisions/{accountProvision}/reset-password'
 */
export const resetPassword = (args: { accountProvision: string | number | { id: string | number } } | [accountProvision: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})

resetPassword.definition = {
    methods: ["post"],
    url: '/account-provisions/{accountProvision}/reset-password',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\AccountProvisionController::resetPassword
 * @see app/Http/Controllers/AccountProvisionController.php:110
 * @route '/account-provisions/{accountProvision}/reset-password'
 */
resetPassword.url = (args: { accountProvision: string | number | { id: string | number } } | [accountProvision: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { accountProvision: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { accountProvision: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    accountProvision: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        accountProvision: typeof args.accountProvision === 'object'
                ? args.accountProvision.id
                : args.accountProvision,
                }

    return resetPassword.definition.url
            .replace('{accountProvision}', parsedArgs.accountProvision.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\AccountProvisionController::resetPassword
 * @see app/Http/Controllers/AccountProvisionController.php:110
 * @route '/account-provisions/{accountProvision}/reset-password'
 */
resetPassword.post = (args: { accountProvision: string | number | { id: string | number } } | [accountProvision: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetPassword.url(args, options),
    method: 'post',
})
const accountProvisions = {
    index: Object.assign(index, index),
invite: Object.assign(invite, invite),
resetPassword: Object.assign(resetPassword, resetPassword),
}

export default accountProvisions