import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\MealScheduleController::store
 * @see app/Http/Controllers/MealScheduleController.php:101
 * @route '/meal-schedules'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/meal-schedules',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MealScheduleController::store
 * @see app/Http/Controllers/MealScheduleController.php:101
 * @route '/meal-schedules'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealScheduleController::store
 * @see app/Http/Controllers/MealScheduleController.php:101
 * @route '/meal-schedules'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MealScheduleController::update
 * @see app/Http/Controllers/MealScheduleController.php:140
 * @route '/meal-schedules/{mealSchedule}'
 */
export const update = (args: { mealSchedule: number | { id: number } } | [mealSchedule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/meal-schedules/{mealSchedule}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\MealScheduleController::update
 * @see app/Http/Controllers/MealScheduleController.php:140
 * @route '/meal-schedules/{mealSchedule}'
 */
update.url = (args: { mealSchedule: number | { id: number } } | [mealSchedule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { mealSchedule: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { mealSchedule: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    mealSchedule: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        mealSchedule: typeof args.mealSchedule === 'object'
                ? args.mealSchedule.id
                : args.mealSchedule,
                }

    return update.definition.url
            .replace('{mealSchedule}', parsedArgs.mealSchedule.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealScheduleController::update
 * @see app/Http/Controllers/MealScheduleController.php:140
 * @route '/meal-schedules/{mealSchedule}'
 */
update.put = (args: { mealSchedule: number | { id: number } } | [mealSchedule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\MealScheduleController::destroy
 * @see app/Http/Controllers/MealScheduleController.php:163
 * @route '/meal-schedules/{mealSchedule}'
 */
export const destroy = (args: { mealSchedule: number | { id: number } } | [mealSchedule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/meal-schedules/{mealSchedule}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\MealScheduleController::destroy
 * @see app/Http/Controllers/MealScheduleController.php:163
 * @route '/meal-schedules/{mealSchedule}'
 */
destroy.url = (args: { mealSchedule: number | { id: number } } | [mealSchedule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { mealSchedule: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { mealSchedule: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    mealSchedule: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        mealSchedule: typeof args.mealSchedule === 'object'
                ? args.mealSchedule.id
                : args.mealSchedule,
                }

    return destroy.definition.url
            .replace('{mealSchedule}', parsedArgs.mealSchedule.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MealScheduleController::destroy
 * @see app/Http/Controllers/MealScheduleController.php:163
 * @route '/meal-schedules/{mealSchedule}'
 */
destroy.delete = (args: { mealSchedule: number | { id: number } } | [mealSchedule: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})
const mealSchedules = {
    store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default mealSchedules