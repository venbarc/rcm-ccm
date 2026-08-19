import Auth from './Auth'
import DashboardController from './DashboardController'
import CurrentAccountController from './CurrentAccountController'
import ClaimController from './ClaimController'
import ClaimExportController from './ClaimExportController'
import ClaimImportController from './ClaimImportController'
import AssignmentController from './AssignmentController'
import ActivityLogController from './ActivityLogController'
import DashboardSummaryExportController from './DashboardSummaryExportController'
import SystemConfigurationController from './SystemConfigurationController'
import UserManagementController from './UserManagementController'
import Settings from './Settings'
const Controllers = {
    Auth: Object.assign(Auth, Auth),
DashboardController: Object.assign(DashboardController, DashboardController),
CurrentAccountController: Object.assign(CurrentAccountController, CurrentAccountController),
ClaimController: Object.assign(ClaimController, ClaimController),
ClaimExportController: Object.assign(ClaimExportController, ClaimExportController),
ClaimImportController: Object.assign(ClaimImportController, ClaimImportController),
AssignmentController: Object.assign(AssignmentController, AssignmentController),
ActivityLogController: Object.assign(ActivityLogController, ActivityLogController),
DashboardSummaryExportController: Object.assign(DashboardSummaryExportController, DashboardSummaryExportController),
SystemConfigurationController: Object.assign(SystemConfigurationController, SystemConfigurationController),
UserManagementController: Object.assign(UserManagementController, UserManagementController),
Settings: Object.assign(Settings, Settings),
}

export default Controllers