import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:14
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
 * @see app/Http/Controllers/ActivityLogController.php:14
 * @route '/activity-logs'
 */
ActivityLogController.url = (options?: RouteQueryOptions) => {
    return ActivityLogController.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:14
 * @route '/activity-logs'
 */
ActivityLogController.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ActivityLogController.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:14
 * @route '/activity-logs'
 */
ActivityLogController.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ActivityLogController.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:14
 * @route '/activity-logs'
 */
    const ActivityLogControllerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: ActivityLogController.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:14
 * @route '/activity-logs'
 */
        ActivityLogControllerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: ActivityLogController.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ActivityLogController::__invoke
 * @see app/Http/Controllers/ActivityLogController.php:14
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
export default ActivityLogController