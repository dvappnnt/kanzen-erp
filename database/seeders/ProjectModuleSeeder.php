<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\ProjectColumn;

class ProjectModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed columns with predefined Kanban stages
        $columns = collect([
            ['name' => 'To Do', 'position' => 1],
            ['name' => 'In Progress', 'position' => 2],
            ['name' => 'Done', 'position' => 3],
        ]);

        // Create and store the inserted columns with keys
        $insertedColumns = [];

        $columns->each(function ($column) use (&$insertedColumns) {
            $insertedColumns[$column['name']] = ProjectColumn::create($column);
        });

        // Create sample projects under each column
        Project::create([
            'project_column_id' => $insertedColumns['To Do']->id,
            'name' => 'Project 1',
            'description' => 'Initial planning and research',
            'status' => 'draft',
            'start_date' => '2021-01-01',
            'end_date' => '2021-02-01',
            'created_by_user_id' => 1,
            'customer_id' => null,
        ]);

        Project::create([
            'project_column_id' => $insertedColumns['In Progress']->id,
            'name' => 'Project 2',
            'description' => 'Development in progress',
            'status' => 'active',
            'start_date' => '2021-02-01',
            'end_date' => '2021-03-01',
            'created_by_user_id' => 1,
            'customer_id' => null,
        ]);

        Project::create([
            'project_column_id' => $insertedColumns['Done']->id,
            'name' => 'Project 3',
            'description' => 'Project completed successfully',
            'status' => 'completed',
            'start_date' => '2020-12-01',
            'end_date' => '2021-01-15',
            'created_by_user_id' => 1,
            'customer_id' => null,
        ]);
    }
}
