import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
 * @see routes/web.php:25
 * @route '/oneaccess'
 */
export const returnMethod = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: returnMethod.url(options),
    method: 'get',
})

returnMethod.definition = {
    methods: ["get","head"],
    url: '/oneaccess',
} satisfies RouteDefinition<["get","head"]>

/**
 * @see routes/web.php:25
 * @route '/oneaccess'
 */
returnMethod.url = (options?: RouteQueryOptions) => {
    return returnMethod.definition.url + queryParams(options)
}

/**
 * @see routes/web.php:25
 * @route '/oneaccess'
 */
returnMethod.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: returnMethod.url(options),
    method: 'get',
})
/**
 * @see routes/web.php:25
 * @route '/oneaccess'
 */
returnMethod.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: returnMethod.url(options),
    method: 'head',
})

    /**
 * @see routes/web.php:25
 * @route '/oneaccess'
 */
    const returnMethodForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: returnMethod.url(options),
        method: 'get',
    })

            /**
 * @see routes/web.php:25
 * @route '/oneaccess'
 */
        returnMethodForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: returnMethod.url(options),
            method: 'get',
        })
            /**
 * @see routes/web.php:25
 * @route '/oneaccess'
 */
        returnMethodForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: returnMethod.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    returnMethod.form = returnMethodForm
const oneaccess = {
    return: Object.assign(returnMethod, returnMethod),
}

export default oneaccess