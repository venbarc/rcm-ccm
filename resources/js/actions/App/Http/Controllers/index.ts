import Auth from './Auth'
import DashboardController from './DashboardController'
import ClaimController from './ClaimController'
import ClaimImportController from './ClaimImportController'
import AssignmentController from './AssignmentController'
import ActivityLogController from './ActivityLogController'
import UserManagementController from './UserManagementController'
import Settings from './Settings'
const Controllers = {
    Auth: Object.assign(Auth, Auth),
DashboardController: Object.assign(DashboardController, DashboardController),
ClaimController: Object.assign(ClaimController, ClaimController),
ClaimImportController: Object.assign(ClaimImportController, ClaimImportController),
AssignmentController: Object.assign(AssignmentController, AssignmentController),
ActivityLogController: Object.assign(ActivityLogController, ActivityLogController),
UserManagementController: Object.assign(UserManagementController, UserManagementController),
Settings: Object.assign(Settings, Settings),
}

export default Controllers