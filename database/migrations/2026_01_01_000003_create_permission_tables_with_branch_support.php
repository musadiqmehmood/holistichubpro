<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = Config::get('permission.table_names');
        $columnNames = Config::get('permission.column_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        // Permissions table
        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 125);
            $table->string('guard_name', 125);
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        // Roles table
        Schema::create($tableNames['roles'], function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 125);
            $table->string('guard_name', 125);
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        // Model has permissions (no branch_id)
        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $permissionPivot = $columnNames['permission_pivot_key'] ?? 'permission_id';

            $table->unsignedBigInteger($permissionPivot);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($permissionPivot)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->primary([$permissionPivot, $columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary');
        });

        // Model has roles (with branch_id and composite primary key)
        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $rolePivot = $columnNames['role_pivot_key'] ?? 'role_id';

            $table->unsignedBigInteger($rolePivot);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            // Add branch_id as NOT NULL (foreign key to branches)
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->index(['branch_id'], 'model_has_roles_branch_id_index');

            $table->foreign($rolePivot)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            // Composite primary key including branch_id
            $table->primary(['branch_id', $rolePivot, $columnNames['model_morph_key'], 'model_type'],
                'model_has_roles_branch_role_model_primary');
        });

        // Role has permissions
        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $permissionPivot = $columnNames['permission_pivot_key'] ?? 'permission_id';
            $rolePivot = $columnNames['role_pivot_key'] ?? 'role_id';

            $table->unsignedBigInteger($permissionPivot);
            $table->unsignedBigInteger($rolePivot);

            $table->foreign($permissionPivot)
                ->references('id')
                ->on($tableNames['permissions'])
                ->cascadeOnDelete();

            $table->foreign($rolePivot)
                ->references('id')
                ->on($tableNames['roles'])
                ->cascadeOnDelete();

            $table->primary([$permissionPivot, $rolePivot], 'role_has_permissions_permission_id_role_id_primary');
        });

        // Clear permission cache
        app('cache')
            ->store(Config::get('permission.cache.store') != 'default' ? Config::get('permission.cache.store') : null)
            ->forget(Config::get('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = Config::get('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found. Please drop tables manually.');
        }

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
