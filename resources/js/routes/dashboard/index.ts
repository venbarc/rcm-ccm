import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DashboardSummaryExportController::__invoke
 * @see app/Http/Controllers/DashboardSummaryExportController.php:23
 * @route '/dashboard-export/{panel}'
 */
export const exportMethod = (args: { panel: string | number } | [panel: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(args, options),
    method: 'get',
})

exportMethod.definition = {
    methods: ["get","head"],
    url: '/dashboard-export/{panel}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DashboardSummaryExportController::__invoke
 * @see app/Http/Controllers/DashboardSummaryExportController.php:23
 * @route '/dashboard-export/{panel}'
 */
exportMethod.url = (args: { panel: string | number } | [panel: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { panel: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    panel: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        panel: args.panel,
                }

    return exportMethod.definition.url
            .replace('{panel}', parsedArgs.panel.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardSummaryExportController::__invoke
 * @see app/Http/Controllers/DashboardSummaryExportController.php:23
 * @route '/dashboard-export/{panel}'
 */
exportMethod.get = (args: { panel: string | number } | [panel: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: exportMethod.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DashboardSummaryExportController::__invoke
 * @see app/Http/Controllers/DashboardSummaryExportController.php:23
 * @route '/dashboard-export/{panel}'
 */
exportMethod.head = (args: { panel: string | number } | [panel: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: exportMethod.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\DashboardSummaryExportController::__invoke
 * @see app/Http/Controllers/DashboardSummaryExportController.php:23
 * @route '/dashboard-export/{panel}'
 */
    const exportMethodForm = (args: { panel: string | number } | [panel: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: exportMethod.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\DashboardSummaryExportController::__invoke
 * @see app/Http/Controllers/DashboardSummaryExportController.php:23
 * @route '/dashboard-export/{panel}'
 */
        exportMethodForm.get = (args: { panel: string | number } | [panel: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: exportMethod.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\DashboardSummaryExportController::__invoke
 * @see app/Http/Controllers/DashboardSummaryExportController.php:23
 * @route '/dashboard-export/{panel}'
 */
        exportMethodForm.head = (args: { panel: string | number } | [panel: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: exportMethod.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    exportMethod.form = exportMethodForm
const dashboard = {
    export: Object.assign(exportMethod, exportMethod),
}

export default dashboard