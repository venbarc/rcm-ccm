import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:28
 * @route '/activity-logs'
 */
const ActivityLogController = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ActivityLogController.url(options),
    method: 'get',
})

ActivityLogController.definition = {
    methods: ["get","head"],
    url: '/activity-logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:28
 * @route '/activity-logs'
 */
ActivityLogController.url = (options?: RouteQueryOptions) => {
    return ActivityLogController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:28
 * @route '/activity-logs'
 */
ActivityLogController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ActivityLogController.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:28
 * @route '/activity-logs'
 */
ActivityLogController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ActivityLogController.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:28
 * @route '/activity-logs'
 */
    const ActivityLogControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: ActivityLogController.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:28
 * @route '/activity-logs'
 */
        ActivityLogControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: ActivityLogController.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:28
 * @route '/activity-logs'
 */
        ActivityLogControllerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: ActivityLogController.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    ActivityLogController.form = ActivityLogControllerForm
/**
* @see \App\Http\Controllers\ActivityLogController::exportMethod
 * @see app/Http/Controllers/ActivityLogController.php:104
 * @route '/activity-logs/export'
 */
export const exportMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})

exportMethod.definition = {
    methods: ["get","head"],
    url: '/activity-logs/export',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ActivityLogController::exportMethod
 * @see app/Http/Controllers/ActivityLogController.php:104
 * @route '/activity-logs/export'
 */
exportMethod.url = (options?: RouteQueryOptions) => {
    return exportMethod.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ActivityLogController::exportMethod
 * @see app/Http/Controllers/ActivityLogController.php:104
 * @route '/activity-logs/export'
 */
exportMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ActivityLogController::exportMethod
 * @see app/Http/Controllers/ActivityLogController.php:104
 * @route '/activity-logs/export'
 */
exportMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ActivityLogController::exportMethod
 * @see app/Http/Controllers/ActivityLogController.php:104
 * @route '/activity-logs/export'
 */
    const exportMethodForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: exportMethod.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ActivityLogController::exportMethod
 * @see app/Http/Controllers/ActivityLogController.php:104
 * @route '/activity-logs/export'
 */
        exportMethodForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: exportMethod.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ActivityLogController::exportMethod
 * @see app/Http/Controllers/ActivityLogController.php:104
 * @route '/activity-logs/export'
 */
        exportMethodForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: exportMethod.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    exportMethod.form = exportMethodForm
/**
* @see \App\Http\Controllers\ActivityLogController::statusDetails
 * @see app/Http/Controllers/ActivityLogController.php:77
 * @route '/activity-logs/status-details'
 */
export const statusDetails = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statusDetails.url(options),
    method: 'get',
})

statusDetails.definition = {
    methods: ["get","head"],
    url: '/activity-logs/status-details',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ActivityLogController::statusDetails
 * @see app/Http/Controllers/ActivityLogController.php:77
 * @route '/activity-logs/status-details'
 */
statusDetails.url = (options?: RouteQueryOptions) => {
    return statusDetails.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ActivityLogController::statusDetails
 * @see app/Http/Controllers/ActivityLogController.php:77
 * @route '/activity-logs/status-details'
 */
statusDetails.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: statusDetails.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ActivityLogController::statusDetails
 * @see app/Http/Controllers/ActivityLogController.php:77
 * @route '/activity-logs/status-details'
 */
statusDetails.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: statusDetails.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ActivityLogController::statusDetails
 * @see app/Http/Controllers/ActivityLogController.php:77
 * @route '/activity-logs/status-details'
 */
    const statusDetailsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: statusDetails.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ActivityLogController::statusDetails
 * @see app/Http/Controllers/ActivityLogController.php:77
 * @route '/activity-logs/status-details'
 */
        statusDetailsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statusDetails.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ActivityLogController::statusDetails
 * @see app/Http/Controllers/ActivityLogController.php:77
 * @route '/activity-logs/status-details'
 */
        statusDetailsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: statusDetails.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    statusDetails.form = statusDetailsForm
/**
* @see \App\Http\Controllers\ActivityLogController::workedClaimLines
 * @see app/Http/Controllers/ActivityLogController.php:161
 * @route '/activity-logs/users/{user}/worked-claim-lines'
 */
export const workedClaimLines = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: workedClaimLines.url(args, options),
    method: 'get',
})

workedClaimLines.definition = {
    methods: ["get","head"],
    url: '/activity-logs/users/{user}/worked-claim-lines',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ActivityLogController::workedClaimLines
 * @see app/Http/Controllers/ActivityLogController.php:161
 * @route '/activity-logs/users/{user}/worked-claim-lines'
 */
workedClaimLines.url = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { user: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { user: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    user: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        user: typeof args.user === 'object'
                ? args.user.id
                : args.user,
                }

    return workedClaimLines.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ActivityLogController::workedClaimLines
 * @see app/Http/Controllers/ActivityLogController.php:161
 * @route '/activity-logs/users/{user}/worked-claim-lines'
 */
workedClaimLines.get = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: workedClaimLines.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ActivityLogController::workedClaimLines
 * @see app/Http/Controllers/ActivityLogController.php:161
 * @route '/activity-logs/users/{user}/worked-claim-lines'
 */
workedClaimLines.head = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: workedClaimLines.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ActivityLogController::workedClaimLines
 * @see app/Http/Controllers/ActivityLogController.php:161
 * @route '/activity-logs/users/{user}/worked-claim-lines'
 */
    const workedClaimLinesForm = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: workedClaimLines.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ActivityLogController::workedClaimLines
 * @see app/Http/Controllers/ActivityLogController.php:161
 * @route '/activity-logs/users/{user}/worked-claim-lines'
 */
        workedClaimLinesForm.get = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: workedClaimLines.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ActivityLogController::workedClaimLines
 * @see app/Http/Controllers/ActivityLogController.php:161
 * @route '/activity-logs/users/{user}/worked-claim-lines'
 */
        workedClaimLinesForm.head = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: workedClaimLines.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    workedClaimLines.form = workedClaimLinesForm
ActivityLogController.exportMethod = exportMethod
ActivityLogController.statusDetails = statusDetails
ActivityLogController.workedClaimLines = workedClaimLines

export default ActivityLogController