import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\ClaimExportController::start
 * @see app/Http/Controllers/ClaimExportController.php:18
 * @route '/claims-export/start'
 */
export const start = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

start.definition = {
    methods: ["post"],
    url: '/claims-export/start',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ClaimExportController::start
 * @see app/Http/Controllers/ClaimExportController.php:18
 * @route '/claims-export/start'
 */
start.url = (options?: RouteQueryOptions) => {
    return start.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimExportController::start
 * @see app/Http/Controllers/ClaimExportController.php:18
 * @route '/claims-export/start'
 */
start.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: start.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\ClaimExportController::start
 * @see app/Http/Controllers/ClaimExportController.php:18
 * @route '/claims-export/start'
 */
    const startForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: start.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ClaimExportController::start
 * @see app/Http/Controllers/ClaimExportController.php:18
 * @route '/claims-export/start'
 */
        startForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: start.url(options),
            method: 'post',
        })
    
    start.form = startForm
/**
* @see \App\Http\Controllers\ClaimExportController::active
 * @see app/Http/Controllers/ClaimExportController.php:39
 * @route '/claims-export/active'
 */
export const active = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: active.url(options),
    method: 'get',
})

active.definition = {
    methods: ["get","head"],
    url: '/claims-export/active',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimExportController::active
 * @see app/Http/Controllers/ClaimExportController.php:39
 * @route '/claims-export/active'
 */
active.url = (options?: RouteQueryOptions) => {
    return active.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimExportController::active
 * @see app/Http/Controllers/ClaimExportController.php:39
 * @route '/claims-export/active'
 */
active.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: active.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimExportController::active
 * @see app/Http/Controllers/ClaimExportController.php:39
 * @route '/claims-export/active'
 */
active.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: active.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimExportController::active
 * @see app/Http/Controllers/ClaimExportController.php:39
 * @route '/claims-export/active'
 */
    const activeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: active.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimExportController::active
 * @see app/Http/Controllers/ClaimExportController.php:39
 * @route '/claims-export/active'
 */
        activeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: active.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimExportController::active
 * @see app/Http/Controllers/ClaimExportController.php:39
 * @route '/claims-export/active'
 */
        activeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: active.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    active.form = activeForm
/**
* @see \App\Http\Controllers\ClaimExportController::history
 * @see app/Http/Controllers/ClaimExportController.php:63
 * @route '/claims-export/history'
 */
export const history = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})

history.definition = {
    methods: ["get","head"],
    url: '/claims-export/history',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimExportController::history
 * @see app/Http/Controllers/ClaimExportController.php:63
 * @route '/claims-export/history'
 */
history.url = (options?: RouteQueryOptions) => {
    return history.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimExportController::history
 * @see app/Http/Controllers/ClaimExportController.php:63
 * @route '/claims-export/history'
 */
history.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: history.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimExportController::history
 * @see app/Http/Controllers/ClaimExportController.php:63
 * @route '/claims-export/history'
 */
history.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: history.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimExportController::history
 * @see app/Http/Controllers/ClaimExportController.php:63
 * @route '/claims-export/history'
 */
    const historyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: history.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimExportController::history
 * @see app/Http/Controllers/ClaimExportController.php:63
 * @route '/claims-export/history'
 */
        historyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: history.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimExportController::history
 * @see app/Http/Controllers/ClaimExportController.php:63
 * @route '/claims-export/history'
 */
        historyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: history.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    history.form = historyForm
/**
* @see \App\Http\Controllers\ClaimExportController::progress
 * @see app/Http/Controllers/ClaimExportController.php:54
 * @route '/claims-export/{claimExport}/progress'
 */
export const progress = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: progress.url(args, options),
    method: 'get',
})

progress.definition = {
    methods: ["get","head"],
    url: '/claims-export/{claimExport}/progress',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimExportController::progress
 * @see app/Http/Controllers/ClaimExportController.php:54
 * @route '/claims-export/{claimExport}/progress'
 */
progress.url = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { claimExport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { claimExport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    claimExport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        claimExport: typeof args.claimExport === 'object'
                ? args.claimExport.id
                : args.claimExport,
                }

    return progress.definition.url
            .replace('{claimExport}', parsedArgs.claimExport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimExportController::progress
 * @see app/Http/Controllers/ClaimExportController.php:54
 * @route '/claims-export/{claimExport}/progress'
 */
progress.get = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: progress.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimExportController::progress
 * @see app/Http/Controllers/ClaimExportController.php:54
 * @route '/claims-export/{claimExport}/progress'
 */
progress.head = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: progress.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimExportController::progress
 * @see app/Http/Controllers/ClaimExportController.php:54
 * @route '/claims-export/{claimExport}/progress'
 */
    const progressForm = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: progress.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimExportController::progress
 * @see app/Http/Controllers/ClaimExportController.php:54
 * @route '/claims-export/{claimExport}/progress'
 */
        progressForm.get = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: progress.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimExportController::progress
 * @see app/Http/Controllers/ClaimExportController.php:54
 * @route '/claims-export/{claimExport}/progress'
 */
        progressForm.head = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: progress.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    progress.form = progressForm
/**
* @see \App\Http\Controllers\ClaimExportController::download
 * @see app/Http/Controllers/ClaimExportController.php:78
 * @route '/claims-export/{claimExport}/download'
 */
export const download = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})

download.definition = {
    methods: ["get","head"],
    url: '/claims-export/{claimExport}/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimExportController::download
 * @see app/Http/Controllers/ClaimExportController.php:78
 * @route '/claims-export/{claimExport}/download'
 */
download.url = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { claimExport: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { claimExport: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    claimExport: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        claimExport: typeof args.claimExport === 'object'
                ? args.claimExport.id
                : args.claimExport,
                }

    return download.definition.url
            .replace('{claimExport}', parsedArgs.claimExport.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimExportController::download
 * @see app/Http/Controllers/ClaimExportController.php:78
 * @route '/claims-export/{claimExport}/download'
 */
download.get = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: download.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimExportController::download
 * @see app/Http/Controllers/ClaimExportController.php:78
 * @route '/claims-export/{claimExport}/download'
 */
download.head = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: download.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimExportController::download
 * @see app/Http/Controllers/ClaimExportController.php:78
 * @route '/claims-export/{claimExport}/download'
 */
    const downloadForm = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: download.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimExportController::download
 * @see app/Http/Controllers/ClaimExportController.php:78
 * @route '/claims-export/{claimExport}/download'
 */
        downloadForm.get = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: download.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimExportController::download
 * @see app/Http/Controllers/ClaimExportController.php:78
 * @route '/claims-export/{claimExport}/download'
 */
        downloadForm.head = (args: { claimExport: number | { id: number } } | [claimExport: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: download.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    download.form = downloadForm
const ClaimExportController = { start, active, history, progress, download }

export default ClaimExportController