<?php

namespace Database\Seeders;

use App\Models\General\ApprovalHeaders;
use App\Models\General\ApprovalUsers;
use App\Models\General\Departements;
use App\Models\General\ReceiptNoteTypes;
use App\Models\General\Roles;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UsersSeeder::class
        ]);

        Departements::create([
            'dep_code' => 'DEP-001',
            'dep_name' => 'Teknik',
            'dep_description' => '-',
        ]);

        Departements::create([
            'dep_code' => 'DEP-002',
            'dep_name' => 'Akuntan',
            'dep_description' => '-',
        ]);

        Departements::create([
            'dep_code' => 'DEP-003',
            'dep_name' => 'Keuangan',
            'dep_description' => '-',
        ]);

        Roles::create([
            'role_name' => 'Super Admin',
            'role_description' => '-',
        ]);

        Roles::create([
            'role_name' => 'Admin',
            'role_description' => '-',
        ]);

        Roles::create([
            'role_name' => 'Super User',
            'role_description' => '-',
        ]);

        Roles::create([
            'role_name' => 'User',
            'role_description' => '-',
        ]);

        Roles::create([
            'role_name' => 'Approver',
            'role_description' => '-',
        ]);

        ReceiptNoteTypes::create([
            'rnt_name' => 'Raw Material',
            'rnt_description' => '-',
        ]);

        ReceiptNoteTypes::create([
            'rnt_name' => 'Packaging Material',
            'rnt_description' => '-',
        ]);

        ReceiptNoteTypes::create([
            'rnt_name' => 'Finished Goods',
            'rnt_description' => '-',
        ]);

        ReceiptNoteTypes::create([
            'rnt_name' => 'Lain-Lain',
            'rnt_description' => '-',
        ]);

        for ($i = 1; $i <= 3; $i++) {
            ApprovalHeaders::create([
                'apvh_code' => 'APVH-0000000' . $i,
                'apvh_name' => 'Test ' . $i,
                'apvh_description' => '-',
            ]);
        }

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000001',
            'apvu_level' => 1,
            'user_id' => 1,
            'apvu_description' => '-',
        ]);

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000001',
            'apvu_level' => 2,
            'user_id' => 2,
            'apvu_description' => '-',
        ]);

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000001',
            'apvu_level' => 3,
            'user_id' => 3,
            'apvu_description' => '-',
        ]);

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000002',
            'apvu_level' => 1,
            'user_id' => 4,
            'apvu_description' => '-',
        ]);

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000002',
            'apvu_level' => 2,
            'user_id' => 5,
            'apvu_description' => '-',
        ]);

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000002',
            'apvu_level' => 3,
            'user_id' => 6,
            'apvu_description' => '-',
        ]);

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000002',
            'apvu_level' => 4,
            'user_id' => 7,
            'apvu_description' => '-',
        ]);

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000003',
            'apvu_level' => 1,
            'user_id' => 8,
            'apvu_description' => '-',
        ]);

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000003',
            'apvu_level' => 2,
            'user_id' => 9,
            'apvu_description' => '-',
        ]);

        ApprovalUsers::create([
            'apvh_code' => 'APVH-00000003',
            'apvu_level' => 3,
            'user_id' => 10,
            'apvu_description' => '-',
        ]);
    }
}
