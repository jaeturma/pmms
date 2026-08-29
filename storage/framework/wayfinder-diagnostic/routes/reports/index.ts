import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
import participationE98ebd from './participation'
import rosterD97775 from './roster'
import eventEntriesA54ac9 from './event-entries'
import resultSheet1f6378 from './result-sheet'
import tally4c8079 from './tally'
import medalConfigurationDe2d07 from './medal-configuration'
import schedule7539d9 from './schedule'
import managementD2a520 from './management'
/**
* @see \App\Http\Controllers\ReportController::participation
 * @see app/Http/Controllers/ReportController.php:190
 * @route '/reports/participation'
 */
export const participation = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: participation.url(options),
    method: 'get',
})

participation.definition = {
    methods: ["get","head"],
    url: '/reports/participation',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::participation
 * @see app/Http/Controllers/ReportController.php:190
 * @route '/reports/participation'
 */
participation.url = (options?: RouteQueryOptions) => {
    return participation.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::participation
 * @see app/Http/Controllers/ReportController.php:190
 * @route '/reports/participation'
 */
participation.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: participation.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::participation
 * @see app/Http/Controllers/ReportController.php:190
 * @route '/reports/participation'
 */
participation.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: participation.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::roster
 * @see app/Http/Controllers/ReportController.php:98
 * @route '/reports/delegations/{delegation}/roster'
 */
export const roster = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: roster.url(args, options),
    method: 'get',
})

roster.definition = {
    methods: ["get","head"],
    url: '/reports/delegations/{delegation}/roster',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::roster
 * @see app/Http/Controllers/ReportController.php:98
 * @route '/reports/delegations/{delegation}/roster'
 */
roster.url = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { delegation: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { delegation: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    delegation: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        delegation: typeof args.delegation === 'object'
                ? args.delegation.id
                : args.delegation,
                }

    return roster.definition.url
            .replace('{delegation}', parsedArgs.delegation.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::roster
 * @see app/Http/Controllers/ReportController.php:98
 * @route '/reports/delegations/{delegation}/roster'
 */
roster.get = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: roster.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::roster
 * @see app/Http/Controllers/ReportController.php:98
 * @route '/reports/delegations/{delegation}/roster'
 */
roster.head = (args: { delegation: number | { id: number } } | [delegation: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: roster.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::eventEntries
 * @see app/Http/Controllers/ReportController.php:147
 * @route '/reports/events/{event}/entries'
 */
export const eventEntries = (args: { event: number | { id: number } } | [event: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eventEntries.url(args, options),
    method: 'get',
})

eventEntries.definition = {
    methods: ["get","head"],
    url: '/reports/events/{event}/entries',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::eventEntries
 * @see app/Http/Controllers/ReportController.php:147
 * @route '/reports/events/{event}/entries'
 */
eventEntries.url = (args: { event: number | { id: number } } | [event: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { event: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { event: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    event: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        event: typeof args.event === 'object'
                ? args.event.id
                : args.event,
                }

    return eventEntries.definition.url
            .replace('{event}', parsedArgs.event.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::eventEntries
 * @see app/Http/Controllers/ReportController.php:147
 * @route '/reports/events/{event}/entries'
 */
eventEntries.get = (args: { event: number | { id: number } } | [event: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eventEntries.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::eventEntries
 * @see app/Http/Controllers/ReportController.php:147
 * @route '/reports/events/{event}/entries'
 */
eventEntries.head = (args: { event: number | { id: number } } | [event: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: eventEntries.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::resultSheet
 * @see app/Http/Controllers/ReportController.php:223
 * @route '/reports/results/{result}'
 */
export const resultSheet = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: resultSheet.url(args, options),
    method: 'get',
})

resultSheet.definition = {
    methods: ["get","head"],
    url: '/reports/results/{result}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::resultSheet
 * @see app/Http/Controllers/ReportController.php:223
 * @route '/reports/results/{result}'
 */
resultSheet.url = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { result: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { result: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    result: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        result: typeof args.result === 'object'
                ? args.result.id
                : args.result,
                }

    return resultSheet.definition.url
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::resultSheet
 * @see app/Http/Controllers/ReportController.php:223
 * @route '/reports/results/{result}'
 */
resultSheet.get = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: resultSheet.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::resultSheet
 * @see app/Http/Controllers/ReportController.php:223
 * @route '/reports/results/{result}'
 */
resultSheet.head = (args: { result: number | { id: number } } | [result: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: resultSheet.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::tally
 * @see app/Http/Controllers/ReportController.php:266
 * @route '/reports/tally'
 */
export const tally = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tally.url(options),
    method: 'get',
})

tally.definition = {
    methods: ["get","head"],
    url: '/reports/tally',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::tally
 * @see app/Http/Controllers/ReportController.php:266
 * @route '/reports/tally'
 */
tally.url = (options?: RouteQueryOptions) => {
    return tally.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::tally
 * @see app/Http/Controllers/ReportController.php:266
 * @route '/reports/tally'
 */
tally.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: tally.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::tally
 * @see app/Http/Controllers/ReportController.php:266
 * @route '/reports/tally'
 */
tally.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: tally.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::medalConfiguration
 * @see app/Http/Controllers/ReportController.php:34
 * @route '/reports/medal-configuration'
 */
export const medalConfiguration = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: medalConfiguration.url(options),
    method: 'get',
})

medalConfiguration.definition = {
    methods: ["get","head"],
    url: '/reports/medal-configuration',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::medalConfiguration
 * @see app/Http/Controllers/ReportController.php:34
 * @route '/reports/medal-configuration'
 */
medalConfiguration.url = (options?: RouteQueryOptions) => {
    return medalConfiguration.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::medalConfiguration
 * @see app/Http/Controllers/ReportController.php:34
 * @route '/reports/medal-configuration'
 */
medalConfiguration.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: medalConfiguration.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::medalConfiguration
 * @see app/Http/Controllers/ReportController.php:34
 * @route '/reports/medal-configuration'
 */
medalConfiguration.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: medalConfiguration.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ReportController::schedule
 * @see app/Http/Controllers/ReportController.php:324
 * @route '/reports/schedule'
 */
export const schedule = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: schedule.url(options),
    method: 'get',
})

schedule.definition = {
    methods: ["get","head"],
    url: '/reports/schedule',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ReportController::schedule
 * @see app/Http/Controllers/ReportController.php:324
 * @route '/reports/schedule'
 */
schedule.url = (options?: RouteQueryOptions) => {
    return schedule.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ReportController::schedule
 * @see app/Http/Controllers/ReportController.php:324
 * @route '/reports/schedule'
 */
schedule.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: schedule.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ReportController::schedule
 * @see app/Http/Controllers/ReportController.php:324
 * @route '/reports/schedule'
 */
schedule.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: schedule.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\ManagementDashboardController::management
 * @see app/Http/Controllers/ManagementDashboardController.php:76
 * @route '/reports/management'
 */
export const management = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: management.url(options),
    method: 'get',
})

management.definition = {
    methods: ["get","head"],
    url: '/reports/management',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ManagementDashboardController::management
 * @see app/Http/Controllers/ManagementDashboardController.php:76
 * @route '/reports/management'
 */
management.url = (options?: RouteQueryOptions) => {
    return management.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ManagementDashboardController::management
 * @see app/Http/Controllers/ManagementDashboardController.php:76
 * @route '/reports/management'
 */
management.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: management.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ManagementDashboardController::management
 * @see app/Http/Controllers/ManagementDashboardController.php:76
 * @route '/reports/management'
 */
management.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: management.url(options),
    method: 'head',
})
const reports = {
    participation: Object.assign(participation, participationE98ebd),
roster: Object.assign(roster, rosterD97775),
eventEntries: Object.assign(eventEntries, eventEntriesA54ac9),
resultSheet: Object.assign(resultSheet, resultSheet1f6378),
tally: Object.assign(tally, tally4c8079),
medalConfiguration: Object.assign(medalConfiguration, medalConfigurationDe2d07),
schedule: Object.assign(schedule, schedule7539d9),
management: Object.assign(management, managementD2a520),
}

export default reports