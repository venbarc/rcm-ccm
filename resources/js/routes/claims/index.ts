import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import importMethod from './import'
/**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:19
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
 * @see app/Http/Controllers/ClaimController.php:19
 * @route '/claims'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:19
 * @route '/claims'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:19
 * @route '/claims'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:19
 * @route '/claims'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:19
 * @route '/claims'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\ClaimController::index
 * @see app/Http/Controllers/ClaimController.php:19
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
* @see \App\Http\Controllers\ClaimController::update
 * @see app/Http/Controllers/ClaimController.php:44
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
 * @see app/Http/Controllers/ClaimController.php:44
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
 * @see app/Http/Controllers/ClaimController.php:44
 * @route '/claims/{claim}'
 */
update.patch = (args: { claim: number | { id: number } } | [claim: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\ClaimController::update
 * @see app/Http/Controllers/ClaimController.php:44
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
 * @see app/Http/Controllers/ClaimController.php:44
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
update: Object.assign(update, update),
import: Object.assign(importMethod, importMethod),
}

export default claims