<?php

namespace Database\Seeders;

use App\Models\Standard;
use Illuminate\Database\Seeder;

class StandardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $standards = [
            ['field_id' => 1, 'code' => '351230530000111', 'name_fa' => 'برنامه نویسی Python', 'name_en' => 'Programming Python', 'nazari_h' => 20, 'amali_h' => 90,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 110, 'required_h' => 80,
            ],

            ['field_id' => 1, 'code' => '351230530020001', 'name_fa' => 'اسمبل و ارتقا دهنده کامپیوترهای شخصی', 'name_en' => 'Desktop PC Assembling', 'nazari_h' => 30, 'amali_h' => 70,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 100, 'required_h' => 50,
            ],

            ['field_id' => 1, 'code' => '216640531080001', 'name_fa' => 'کارور Auto CAD', 'name_en' => 'AUTO CAD User', 'nazari_h' => 40, 'amali_h' => 88,
                'karvarzi_h' => 0, 'project_h' => 14, 'sum_h' => 142, 'required_h' => 50,
            ],

            [
                'field_id' => 1, 'code' => '732120530140001', 'name_fa' => 'کاربر ICDL', 'name_en' => 'ICDL USER', 'nazari_h' => 30, 'amali_h' => 100,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 130, 'required_h' => 80,
            ],

            [
                'field_id' => 1, 'code' => '251340530320011', 'name_fa' => 'Web Design Fundamental (HTML5, CSS3)', 'name_en' => 'Web Design Fundamental (HTML5, CSS3)', 'nazari_h' => 20, 'amali_h' => 34,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 54, 'required_h' => 40,
            ],

            [
                'field_id' => 2, 'code' => '1-10/18/1/5', 'name_fa' => 'رایانه کار حسابدار مالی', 'name_en' => 'Financial Accounting', 'nazari_h' => 56, 'amali_h' => 40,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 96, 'required_h' => 45,
            ],

            [
                'field_id' => 2, 'code' => '431320450010002', 'name_fa' => 'حسابدار حقوق و دستمزد', 'name_en' => 'Payroll Accountant', 'nazari_h' => 38, 'amali_h' => 82,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 120, 'required_h' => 45,
            ],

            [
                'field_id' => 1, 'code' => '265420531200001', 'name_fa' => 'کارور PREMIERE', 'name_en' => 'PREMIERE User', 'nazari_h' => 16, 'amali_h' => 32,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 48, 'required_h' => 30,
            ],

            [
                'field_id' => 1, 'code' => '732120530160001', 'name_fa' => 'کارور Photoshop', 'name_en' => 'Photoshop User', 'nazari_h' => 15, 'amali_h' => 75,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 90, 'required_h' => 40,
            ],

            [
                'field_id' => 1, 'code' => '732120530170001', 'name_fa' => 'کارور Illustrator', 'name_en' => 'Illustrator User', 'nazari_h' => 16, 'amali_h' => 74,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 90, 'required_h' => 40,
            ],

            [
                'field_id' => 3, 'code' => '343130830020001', 'name_fa' => 'عکاس دیجیتال', 'name_en' => 'Digital Photographer', 'nazari_h' => 108, 'amali_h' => 178,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 286, 'required_h' => 120,
            ],
            [
                'field_id' => 1, 'code' => '216630530850061', 'name_fa' => 'کارور After Effects', 'name_en' => 'After Effects User', 'nazari_h' => 48, 'amali_h' => 142,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 190, 'required_h' => 80,
            ],

            [
                'field_id' => 2, 'code' => '331130450020061', 'name_fa' => 'معامله گری ارز های دیجیتال', 'name_en' => 'CryptoCurrency Trading', 'nazari_h' => 10, 'amali_h' => 20,
                'karvarzi_h' => 0, 'project_h' => 0, 'sum_h' => 30, 'required_h' => 30,
            ],


            //            [
            //                'field_id' => 1,
            //                'code' => '251340530000031',
            //                'name_fa' => 'ساخت برنامه های وب با استفاده از Laravel Framework',
            //                'name_en' => 'Build Web Apps Using Laravel Framework',
            //                'nazari_h' => 30,
            //                'amali_h' => 60,
            //                'karvarzi_h' => 0,
            //                'project_h' => 0,
            //                'sum_h' => 90,
            //                'required_h' => 120,
            //            ],
            //
        ];

        foreach ($standards as $data) {
            Standard::updateOrCreate(
                ['code' => $data['code']],
                [
                    'field_id' => $data['field_id'],
                    'name_fa' => $data['name_fa'],
                    'name_en' => $data['name_en'],
                    'nazari_h' => $data['nazari_h'],
                    'amali_h' => $data['amali_h'],
                    'karvarzi_h' => $data['karvarzi_h'],
                    'project_h' => $data['project_h'],
                    'sum_h' => $data['sum_h'],
                    'required_h' => $data['required_h'],
                ]
            );
        }
    }
}
