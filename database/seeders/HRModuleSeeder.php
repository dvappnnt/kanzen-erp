<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HRModuleSeeder extends Seeder
{
    public function run(): void
    {
        // Offense Types
        $offenseTypes = [
            'Tardiness',
            'Absenteeism',
            'Insubordination',
            'Misconduct',
            'Negligence',
            'Violation of Company Policy',
            'Dishonesty',
            'Harassment',
            'Theft',
            'Sleeping During Work Hours',
            'Failure to Meet Deadlines',
            'Damage to Company Property',
            'Unauthorized Absence',
            'Breach of Confidentiality',
            'Use of Company Resources for Personal Use',
        ];

        foreach ($offenseTypes as $offense) {
            DB::table('offense_types')->insert([
                'name' => $offense,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Document Types
        $documentTypes = [
            'Resume',
            'Birth Certificate',
            'Marriage Certificate',
            'Diploma',
            'Transcript of Records',
            'NBI Clearance',
            'Government IDs',
            'Certificate of Employment',
            'Training Certificates',
            'Medical Certificate',
            'Signed Contract',
        ];

        foreach ($documentTypes as $doc) {
            DB::table('document_types')->insert([
                'name' => $doc,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Positions
        $positions = [
            'HR Assistant',
            'Recruitment Specialist',
            'Payroll Officer',
            'HR Manager',
            'Software Developer',
            'QA Engineer',
            'IT Support',
            'DevOps Engineer',
            'Project Manager',
            'CTO',
        ];

        foreach ($positions as $position) {
            DB::table('positions')->insert([
                'name' => $position,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }

        // Departments
        $departments = [
            'Human Resources',
            'Finance',
            'IT Department',
            'Marketing',
            'Sales',
            'Operations',
            'Customer Support',
            'Legal',
            'R&D',
        ];

        // Fetch all companies
        $companies = DB::table('companies')->pluck('id');

        foreach ($companies as $companyId) {
            foreach ($departments as $dept) {
                DB::table('departments')->insert([
                    'company_id' => $companyId,
                    'name' => $dept,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
