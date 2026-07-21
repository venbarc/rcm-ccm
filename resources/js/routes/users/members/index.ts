import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\UserManagementController::sync
 * @see app/Http/Controllers/UserManagementController.php:107
 * @route '/user-management/{user}/members'
 */
export const sync = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: sync.url(args, options),
    method: 'patch',
})

sync.definition = {
    methods: ["patch"],
    url: '/user-management/{user}/members',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\UserManagementController::sync
 * @see app/Http/Controllers/UserManagementController.php:107
 * @route '/user-management/{user}/members'
 */
sync.url = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return sync.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UserManagementController::sync
 * @see app/Http/Controllers/UserManagementController.php:107
 * @route '/user-management/{user}/members'
 */
sync.patch = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: sync.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\UserManagementController::sync
 * @see app/Http/Controllers/UserManagementController.php:107
 * @route '/user-management/{user}/members'
 */
    const syncForm = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: sync.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\UserManagementController::sync
 * @see app/Http/Controllers/UserManagementController.php:107
 * @route '/user-management/{user}/members'
 */
        syncForm.patch = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: sync.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    sync.form = syncForm
const members = {
    sync: Object.assign(sync, sync),
}

export default members