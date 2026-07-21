import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import members79483b from './members'
/**
* @see \App\Http\Controllers\UserManagementController::index
 * @see app/Http/Controllers/UserManagementController.php:26
 * @route '/user-management'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/user-management',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UserManagementController::index
 * @see app/Http/Controllers/UserManagementController.php:26
 * @route '/user-management'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UserManagementController::index
 * @see app/Http/Controllers/UserManagementController.php:26
 * @route '/user-management'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\UserManagementController::index
 * @see app/Http/Controllers/UserManagementController.php:26
 * @route '/user-management'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\UserManagementController::index
 * @see app/Http/Controllers/UserManagementController.php:26
 * @route '/user-management'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\UserManagementController::index
 * @see app/Http/Controllers/UserManagementController.php:26
 * @route '/user-management'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\UserManagementController::index
 * @see app/Http/Controllers/UserManagementController.php:26
 * @route '/user-management'
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
* @see \App\Http\Controllers\UserManagementController::availableMembers
 * @see app/Http/Controllers/UserManagementController.php:78
 * @route '/user-management/available-members'
 */
export const availableMembers = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: availableMembers.url(options),
    method: 'get',
})

availableMembers.definition = {
    methods: ["get","head"],
    url: '/user-management/available-members',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UserManagementController::availableMembers
 * @see app/Http/Controllers/UserManagementController.php:78
 * @route '/user-management/available-members'
 */
availableMembers.url = (options?: RouteQueryOptions) => {
    return availableMembers.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UserManagementController::availableMembers
 * @see app/Http/Controllers/UserManagementController.php:78
 * @route '/user-management/available-members'
 */
availableMembers.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: availableMembers.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\UserManagementController::availableMembers
 * @see app/Http/Controllers/UserManagementController.php:78
 * @route '/user-management/available-members'
 */
availableMembers.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: availableMembers.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\UserManagementController::availableMembers
 * @see app/Http/Controllers/UserManagementController.php:78
 * @route '/user-management/available-members'
 */
    const availableMembersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: availableMembers.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\UserManagementController::availableMembers
 * @see app/Http/Controllers/UserManagementController.php:78
 * @route '/user-management/available-members'
 */
        availableMembersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: availableMembers.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\UserManagementController::availableMembers
 * @see app/Http/Controllers/UserManagementController.php:78
 * @route '/user-management/available-members'
 */
        availableMembersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: availableMembers.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    availableMembers.form = availableMembersForm
/**
* @see \App\Http\Controllers\UserManagementController::members
 * @see app/Http/Controllers/UserManagementController.php:95
 * @route '/user-management/{user}/members'
 */
export const members = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: members.url(args, options),
    method: 'get',
})

members.definition = {
    methods: ["get","head"],
    url: '/user-management/{user}/members',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UserManagementController::members
 * @see app/Http/Controllers/UserManagementController.php:95
 * @route '/user-management/{user}/members'
 */
members.url = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return members.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UserManagementController::members
 * @see app/Http/Controllers/UserManagementController.php:95
 * @route '/user-management/{user}/members'
 */
members.get = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: members.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\UserManagementController::members
 * @see app/Http/Controllers/UserManagementController.php:95
 * @route '/user-management/{user}/members'
 */
members.head = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: members.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\UserManagementController::members
 * @see app/Http/Controllers/UserManagementController.php:95
 * @route '/user-management/{user}/members'
 */
    const membersForm = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: members.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\UserManagementController::members
 * @see app/Http/Controllers/UserManagementController.php:95
 * @route '/user-management/{user}/members'
 */
        membersForm.get = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: members.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\UserManagementController::members
 * @see app/Http/Controllers/UserManagementController.php:95
 * @route '/user-management/{user}/members'
 */
        membersForm.head = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: members.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    members.form = membersForm
/**
* @see \App\Http\Controllers\UserManagementController::update
 * @see app/Http/Controllers/UserManagementController.php:131
 * @route '/user-management/{user}'
 */
export const update = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/user-management/{user}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\UserManagementController::update
 * @see app/Http/Controllers/UserManagementController.php:131
 * @route '/user-management/{user}'
 */
update.url = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{user}', parsedArgs.user.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UserManagementController::update
 * @see app/Http/Controllers/UserManagementController.php:131
 * @route '/user-management/{user}'
 */
update.patch = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\UserManagementController::update
 * @see app/Http/Controllers/UserManagementController.php:131
 * @route '/user-management/{user}'
 */
    const updateForm = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PATCH',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\UserManagementController::update
 * @see app/Http/Controllers/UserManagementController.php:131
 * @route '/user-management/{user}'
 */
        updateForm.patch = (args: { user: number | { id: number } } | [user: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
const users = {
    index: Object.assign(index, index),
availableMembers: Object.assign(availableMembers, availableMembers),
members: Object.assign(members, members79483b),
update: Object.assign(update, update),
}

export default users