import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import exportMethod from './export'
import importMethod from './import'
/**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:61
 * @route '/claims'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/claims',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:61
 * @route '/claims'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:61
 * @route '/claims'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:61
 * @route '/claims'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:61
 * @route '/claims'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:61
 * @route '/claims'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:61
 * @route '/claims'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \App\Http\Controllers\ClaimController::options
 * @see app/Http/Controllers/ClaimController.php:334
 * @route '/claims/options'
 */
export const options = (routeOptions?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: options.url(routeOptions),
    method: 'get',
})

options.definition = {
    methods: ["get","head"],
    url: '/claims/options',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimController::options
 * @see app/Http/Controllers/ClaimController.php:334
 * @route '/claims/options'
 */
options.url = (routeOptions?: RouteQueryOptions) => {
    return options.definition.url
 + queryParams(routeOptions)
}

/**
* @see \App\Http\Controllers\ClaimController::options
 * @see app/Http/Controllers/ClaimController.php:334
 * @route '/claims/options'
 */
options.get = (routeOptions?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: options.url(routeOptions),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimController::options
 * @see app/Http/Controllers/ClaimController.php:334
 * @route '/claims/options'
 */
options.head = (routeOptions?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: options.url(routeOptions),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimController::options
 * @see app/Http/Controllers/ClaimController.php:334
 * @route '/claims/options'
 */
    const optionsForm = (routeOptions?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: options.url(
            
                            routeOptions
                   ),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimController::options
 * @see app/Http/Controllers/ClaimController.php:334
 * @route '/claims/options'
 */
        optionsForm.get = (routeOptions?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: options.url(
                
                                routeOptions
                           ),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimController::options
 * @see app/Http/Controllers/ClaimController.php:334
 * @route '/claims/options'
 */
        optionsForm.head = (routeOptions?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: options.url({
                        [routeOptions?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(routeOptions?.query ?? routeOptions?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    options.form = optionsForm
/**
* @see \App\Http\Controllers\ClaimController::activities
 * @see app/Http/Controllers/ClaimController.php:536
 * @route '/claims/{claim}/activities'
 */
export const activities = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: activities.url(args, options),
    method: 'get',
})

activities.definition = {
    methods: ["get","head"],
    url: '/claims/{claim}/activities',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimController::activities
 * @see app/Http/Controllers/ClaimController.php:536
 * @route '/claims/{claim}/activities'
 */
activities.url = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { claim: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { claim: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    claim: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        claim: typeof args.claim === 'object'
                ? args.claim.id
                : args.claim,
                }

    return activities.definition.url
            .replace('{claim}', parsedArgs.claim.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimController::activities
 * @see app/Http/Controllers/ClaimController.php:536
 * @route '/claims/{claim}/activities'
 */
activities.get = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: activities.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimController::activities
 * @see app/Http/Controllers/ClaimController.php:536
 * @route '/claims/{claim}/activities'
 */
activities.head = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: activities.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimController::activities
 * @see app/Http/Controllers/ClaimController.php:536
 * @route '/claims/{claim}/activities'
 */
    const activitiesForm = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: activities.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimController::activities
 * @see app/Http/Controllers/ClaimController.php:536
 * @route '/claims/{claim}/activities'
 */
        activitiesForm.get = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: activities.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimController::activities
 * @see app/Http/Controllers/ClaimController.php:536
 * @route '/claims/{claim}/activities'
 */
        activitiesForm.head = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: activities.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    activities.form = activitiesForm
/**
* @see \App\Http\Controllers\ClaimController::show
 * @see app/Http/Controllers/ClaimController.php:437
 * @route '/claims/{claim}'
 */
export const show = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/claims/{claim}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\ClaimController::show
 * @see app/Http/Controllers/ClaimController.php:437
 * @route '/claims/{claim}'
 */
show.url = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { claim: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { claim: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    claim: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        claim: typeof args.claim === 'object'
                ? args.claim.id
                : args.claim,
                }

    return show.definition.url
            .replace('{claim}', parsedArgs.claim.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimController::show
 * @see app/Http/Controllers/ClaimController.php:437
 * @route '/claims/{claim}'
 */
show.get = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimController::show
 * @see app/Http/Controllers/ClaimController.php:437
 * @route '/claims/{claim}'
 */
show.head = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimController::show
 * @see app/Http/Controllers/ClaimController.php:437
 * @route '/claims/{claim}'
 */
    const showForm = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: show.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimController::show
 * @see app/Http/Controllers/ClaimController.php:437
 * @route '/claims/{claim}'
 */
        showForm.get = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimController::show
 * @see app/Http/Controllers/ClaimController.php:437
 * @route '/claims/{claim}'
 */
        showForm.head = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: show.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    show.form = showForm
/**
* @see \App\Http\Controllers\ClaimController::update
 * @see app/Http/Controllers/ClaimController.php:554
 * @route '/claims/{claim}'
 */
export const update = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/claims/{claim}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\ClaimController::update
 * @see app/Http/Controllers/ClaimController.php:554
 * @route '/claims/{claim}'
 */
update.url = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { claim: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { claim: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    claim: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        claim: typeof args.claim === 'object'
                ? args.claim.id
                : args.claim,
                }

    return update.definition.url
            .replace('{claim}', parsedArgs.claim.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimController::update
 * @see app/Http/Controllers/ClaimController.php:554
 * @route '/claims/{claim}'
 */
update.patch = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\ClaimController::update
 * @see app/Http/Controllers/ClaimController.php:554
 * @route '/claims/{claim}'
 */
    const updateForm = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\ClaimController::update
 * @see app/Http/Controllers/ClaimController.php:554
 * @route '/claims/{claim}'
 */
        updateForm.patch = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
const claims = {
    index: Object.assign(index, index),
options: Object.assign(options, options),
activities: Object.assign(activities, activities),
show: Object.assign(show, show),
update: Object.assign(update, update),
export: Object.assign(exportMethod, exportMethod),
import: Object.assign(importMethod, importMethod),
}

export default claims