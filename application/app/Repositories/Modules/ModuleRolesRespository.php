<?php

/** --------------------------------------------------------------------------------
 * This repository class manages all the data absctration for templates
 *
 * @package    Grow CRM
 * @author     NextLoop
 *----------------------------------------------------------------------------------*/

namespace App\Repositories\Modules;

use App\Models\Module;
use App\Models\Role;
use Illuminate\Support\Facades\Log;

class ModuleRolesRespository {

    /**
     * Syncs permissions for active modules across all roles.
     */
    public function syncModulePermissions() {
        try {
            // Retrieve active modules and all roles
            $active_modules = $this->getActiveModules();
            $roles = $this->getAllRoles();

            // Update permissions for each role based on active modules
            foreach ($roles as $role) {
                $this->updateRoleModulePermissions($role, $active_modules);
            }

            Log::info("Completed syncing module permissions", ['process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__]);

        } catch (\Exception$e) {
            Log::error("Failed to sync module permissions: {$e->getMessage()}", [
                'process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__,
            ]);
        }
    }

    /**
     * Retrieves all active modules that are enabled.
     *
     * @return \Illuminate\Support\Collection A collection of active modules.
     */
    public function getActiveModules() {
        try {
            // Query for modules with status set to 'enabled'
            $modules = Module::where('module_status', 'enabled')
                ->get();

            Log::info("retrive active modules", ['process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__]);

            return $modules;

        } catch (\Exception$e) {
            Log::error("Failed to retrieve active modules: {$e->getMessage()}", [
                'process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__,
            ]);
            return collect([]); // Return empty collection if query fails
        }
    }

    /**
     * Retrieves all roles from the database.
     *
     * @return \Illuminate\Support\Collection A collection of roles.
     */
    public function getAllRoles() {
        try {
            $roles = Role::all();

            Log::info("Retrieved all roles", [
                'process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__,
                'role_count' => $roles->count(),
            ]);

            return $roles;

        } catch (\Exception$e) {
            Log::error("Failed to retrieve roles: {$e->getMessage()}", [
                'process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__,
            ]);
            return collect([]); // Return empty collection if query fails
        }
    }

    /**
     * Updates module permissions for a specific role.
     *
     * @param Role $role The role for which permissions are being updated.
     * @param \Illuminate\Support\Collection $active_modules List of active modules to assign permissions.
     */
    public function updateRoleModulePermissions($role, $active_modules) {
        try {
            // Parse current permissions for the role and build updated permissions list
            $current_permissions = $this->parseModulePermissions($role->modules);
            $updated_permissions = $this->buildUpdatedPermissions($current_permissions, $active_modules, $role);

            // Save updated permissions back to the role
            $role->modules = $updated_permissions;
            $role->save();

            Log::info("Updated role module permissions", [
                'process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__,
                'role_id' => $role->role_id, 'module_count' => count($updated_permissions),
            ]);

        } catch (\Exception$e) {
            Log::error("Failed to update role module permissions: {$e->getMessage()}", [
                'process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__,
                'role_id' => $role->role_id ?? 'unknown',
            ]);
        }
    }

    /**
     * Parses module permissions from JSON or array data format.
     *
     * @param string|array $modules_data Module permissions data, either JSON or array.
     * @return array Parsed module permissions array.
     */
    public function parseModulePermissions($modules_data = '') {
        // If no data exists, log and return an empty array
        if (empty($modules_data)) {
            Log::info("No existing module permissions found", [
                'process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__,
            ]);
            return [];
        }

        // Return directly if permissions are already an array
        if (is_array($modules_data)) {
            return $modules_data;
        }

        // Attempt to parse JSON data
        try {
            return json_decode($modules_data, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException$e) {
            Log::error("Failed to parse module permissions JSON: {$e->getMessage()}", [
                'process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__,
            ]);
            return [];
        }
    }

    /**
     * Builds a new array of permissions based on current and active module data.
     *
     * @param array $current_permissions Array of current permissions for the role.
     * @param \Illuminate\Support\Collection $active_modules List of active modules.
     * @return array Updated permissions array.
     */
    public function buildUpdatedPermissions($current_permissions = [], $active_modules = [], $role = []) {
        $updated_permissions = [];

        // Iterate over each active module to update permissions
        foreach ($active_modules as $module) {
            $module_name = $module->module_name;

            // Check if permission already exists for this module, default to 'no'
            $existing_permission = null;
            foreach ($current_permissions as $permission) {
                if ($permission['module_name'] === $module_name) {
                    $existing_permission = $permission['module_permission'];
                    break;
                }
            }

            // set the deaulf permission to 'yes' for admin role and client role
            $default_permission = ($role->role_id == 1 || $role->role_id == 2) ? 'yes' : 'no';
            switch ($role->role_id) {
            //admin
            case 1:
                $default_permission = 'manage';
                break;
            //client
            case 1:
                $default_permission = 'view';
                break;
            //everyone else
            default:
                $default_permission = 'none';
            }

            //update permission
            $updated_permissions[] = [
                'module_name' => $module_name,
                'module_alias' => $module->module_alias,
                'module_permission' => $existing_permission ?? $default_permission,
            ];
        }

        Log::info("Built updated permissions array", [
            'process' => 'cron.sync-module-permissions', 'function' => __FUNCTION__,
            'permission_count' => count($updated_permissions),
        ]);

        return $updated_permissions;
    }

}