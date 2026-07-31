import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SystemConfigurationController::index
 * @see app/Http/Controllers/SystemConfigurationController.php:21
 * @route '/system-configuration'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/system-configuration',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemConfigurationController::index
 * @see app/Http/Controllers/SystemConfigurationController.php:21
 * @route '/system-configuration'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemConfigurationController::index
 * @see app/Http/Controllers/SystemConfigurationController.php:21
 * @route '/system-configuration'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\SystemConfigurationController::index
 * @see app/Http/Controllers/SystemConfigurationController.php:21
 * @route '/system-configuration'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\SystemConfigurationController::index
 * @see app/Http/Controllers/SystemConfigurationController.php:21
 * @route '/system-configuration'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\SystemConfigurationController::index
 * @see app/Http/Controllers/SystemConfigurationController.php:21
 * @route '/system-configuration'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\SystemConfigurationController::index
 * @see app/Http/Controllers/SystemConfigurationController.php:21
 * @route '/system-configuration'
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
* @see \App\Http\Controllers\SystemConfigurationController::store
 * @see app/Http/Controllers/SystemConfigurationController.php:46
 * @route '/system-configuration'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/system-configuration',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SystemConfigurationController::store
 * @see app/Http/Controllers/SystemConfigurationController.php:46
 * @route '/system-configuration'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemConfigurationController::store
 * @see app/Http/Controllers/SystemConfigurationController.php:46
 * @route '/system-configuration'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\SystemConfigurationController::store
 * @see app/Http/Controllers/SystemConfigurationController.php:46
 * @route '/system-configuration'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\SystemConfigurationController::store
 * @see app/Http/Controllers/SystemConfigurationController.php:46
 * @route '/system-configuration'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\SystemConfigurationController::update
 * @see app/Http/Controllers/SystemConfigurationController.php:74
 * @route '/system-configuration/{configurationOption}'
 */
export const update = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/system-configuration/{configurationOption}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\SystemConfigurationController::update
 * @see app/Http/Controllers/SystemConfigurationController.php:74
 * @route '/system-configuration/{configurationOption}'
 */
update.url = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { configurationOption: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { configurationOption: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    configurationOption: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        configurationOption: typeof args.configurationOption === 'object'
                ? args.configurationOption.id
                : args.configurationOption,
                }

    return update.definition.url
            .replace('{configurationOption}', parsedArgs.configurationOption.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemConfigurationController::update
 * @see app/Http/Controllers/SystemConfigurationController.php:74
 * @route '/system-configuration/{configurationOption}'
 */
update.patch = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\SystemConfigurationController::update
 * @see app/Http/Controllers/SystemConfigurationController.php:74
 * @route '/system-configuration/{configurationOption}'
 */
    const updateForm = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\SystemConfigurationController::update
 * @see app/Http/Controllers/SystemConfigurationController.php:74
 * @route '/system-configuration/{configurationOption}'
 */
        updateForm.patch = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
/**
* @see \App\Http\Controllers\SystemConfigurationController::destroy
 * @see app/Http/Controllers/SystemConfigurationController.php:100
 * @route '/system-configuration/{configurationOption}'
 */
export const destroy = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/system-configuration/{configurationOption}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\SystemConfigurationController::destroy
 * @see app/Http/Controllers/SystemConfigurationController.php:100
 * @route '/system-configuration/{configurationOption}'
 */
destroy.url = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { configurationOption: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { configurationOption: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    configurationOption: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        configurationOption: typeof args.configurationOption === 'object'
                ? args.configurationOption.id
                : args.configurationOption,
                }

    return destroy.definition.url
            .replace('{configurationOption}', parsedArgs.configurationOption.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemConfigurationController::destroy
 * @see app/Http/Controllers/SystemConfigurationController.php:100
 * @route '/system-configuration/{configurationOption}'
 */
destroy.delete = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\SystemConfigurationController::destroy
 * @see app/Http/Controllers/SystemConfigurationController.php:100
 * @route '/system-configuration/{configurationOption}'
 */
    const destroyForm = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\SystemConfigurationController::destroy
 * @see app/Http/Controllers/SystemConfigurationController.php:100
 * @route '/system-configuration/{configurationOption}'
 */
        destroyForm.delete = (args: { configurationOption: number | { id: number } } | [configurationOption: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const SystemConfigurationController = { index, store, update, destroy }

export default SystemConfigurationController