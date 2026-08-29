import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MealAnnouncementController::store
 * @see app/Http/Controllers/MealAnnouncementController.php:25
 * @route '/meal-announcements'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/meal-announcements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MealAnnouncementController::store
 * @see app/Http/Controllers/MealAnnouncementController.php:25
 * @route '/meal-announcements'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealAnnouncementController::store
 * @see app/Http/Controllers/MealAnnouncementController.php:25
 * @route '/meal-announcements'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MealAnnouncementController::update
 * @see app/Http/Controllers/MealAnnouncementController.php:51
 * @route '/meal-announcements/{mealAnnouncement}'
 */
export const update = (args: { mealAnnouncement: number | { id: number } } | [mealAnnouncement: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/meal-announcements/{mealAnnouncement}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MealAnnouncementController::update
 * @see app/Http/Controllers/MealAnnouncementController.php:51
 * @route '/meal-announcements/{mealAnnouncement}'
 */
update.url = (args: { mealAnnouncement: number | { id: number } } | [mealAnnouncement: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { mealAnnouncement: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { mealAnnouncement: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    mealAnnouncement: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        mealAnnouncement: typeof args.mealAnnouncement === 'object'
                ? args.mealAnnouncement.id
                : args.mealAnnouncement,
                }

    return update.definition.url
            .replace('{mealAnnouncement}', parsedArgs.mealAnnouncement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealAnnouncementController::update
 * @see app/Http/Controllers/MealAnnouncementController.php:51
 * @route '/meal-announcements/{mealAnnouncement}'
 */
update.put = (args: { mealAnnouncement: number | { id: number } } | [mealAnnouncement: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\MealAnnouncementController::destroy
 * @see app/Http/Controllers/MealAnnouncementController.php:71
 * @route '/meal-announcements/{mealAnnouncement}'
 */
export const destroy = (args: { mealAnnouncement: number | { id: number } } | [mealAnnouncement: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/meal-announcements/{mealAnnouncement}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MealAnnouncementController::destroy
 * @see app/Http/Controllers/MealAnnouncementController.php:71
 * @route '/meal-announcements/{mealAnnouncement}'
 */
destroy.url = (args: { mealAnnouncement: number | { id: number } } | [mealAnnouncement: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { mealAnnouncement: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { mealAnnouncement: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    mealAnnouncement: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        mealAnnouncement: typeof args.mealAnnouncement === 'object'
                ? args.mealAnnouncement.id
                : args.mealAnnouncement,
                }

    return destroy.definition.url
            .replace('{mealAnnouncement}', parsedArgs.mealAnnouncement.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealAnnouncementController::destroy
 * @see app/Http/Controllers/MealAnnouncementController.php:71
 * @route '/meal-announcements/{mealAnnouncement}'
 */
destroy.delete = (args: { mealAnnouncement: number | { id: number } } | [mealAnnouncement: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const mealAnnouncements = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default mealAnnouncements