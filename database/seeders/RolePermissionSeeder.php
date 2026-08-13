<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles y permisos del panel.
 * Matriz completa en docs/03_ADMIN_PANEL.md seccion 3.
 */
class RolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    public const PERMISSIONS = [
        'manage_settings',
        'manage_properties',
        'manage_property_images',
        'manage_news',
        'manage_leads',
        'manage_agents',
        'manage_pages',
        'manage_seo',
        'view_reports',
        'view_audit',
        'manage_users',
    ];

    /** @var array<string, list<string>> */
    public const ROLES = [
        'super_admin' => ['*'],

        'admin' => [
            'manage_settings',
            'manage_properties',
            'manage_property_images',
            'manage_news',
            'manage_leads',
            'manage_agents',
            'manage_pages',
            'manage_seo',
            'view_reports',
        ],

        'editor' => [
            'manage_properties',
            'manage_property_images',
            'manage_news',
            'manage_pages',
            'manage_seo',
        ],

        // El agente ve solo sus propias propiedades y sus leads asignados.
        // Ese filtro no lo hace el permiso, lo hacen las Policies.
        'agent' => [
            'manage_properties',
            'manage_property_images',
            'manage_leads',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (self::ROLES as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->syncPermissions(
                $permissions === ['*'] ? self::PERMISSIONS : $permissions
            );
        }

        $this->command->info('Roles y permisos sincronizados: '
            .count(self::ROLES).' roles, '.count(self::PERMISSIONS).' permisos.');
    }
}
