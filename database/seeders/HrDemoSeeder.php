<?php

namespace Database\Seeders;

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Database\Seeder;

class HrDemoSeeder extends Seeder
{
    public function run(): void
    {
        // email => [department, manager email, employee code, joining date, date of birth]
        $profiles = [
            'admin@xynosoft.in'    => ['Management',  null,                    'EMP-001', '2021-03-01', '1988-05-14'],
            'aljinas@xynosoft.in'  => ['Engineering', 'admin@xynosoft.in',     'EMP-002', '2022-01-10', '1995-09-15'],
            'sunitha@xynosoft.in'  => ['Engineering', 'aljinas@xynosoft.in',   'EMP-003', '2022-06-01', '1997-03-22'],
            'pranathi@xynosoft.in' => ['Engineering', 'aljinas@xynosoft.in',   'EMP-004', '2023-01-16', '1998-07-08'],
            'sajin@xynosoft.in'    => ['Engineering', 'aljinas@xynosoft.in',   'EMP-005', '2023-04-03', '1996-11-30'],
            'lidiya@xynosoft.in'   => ['Design',      'aljinas@xynosoft.in',   'EMP-006', '2023-08-21', '1997-12-12'],
            'jinu@xynosoft.in'     => ['Engineering', 'aljinas@xynosoft.in',   'EMP-007', '2024-02-05', '1999-04-17'],
            'suhana@xynosoft.in'   => ['Quality',     'aljinas@xynosoft.in',   'EMP-008', '2024-05-13', '1998-06-25'],
            'annmaria@xynosoft.in' => ['Engineering', 'aljinas@xynosoft.in',   'EMP-009', '2024-09-01', '1997-10-05'],
        ];

        foreach ($profiles as $email => [$dept, $managerEmail, $code, $joined, $dob]) {
            $user = User::where('email', $email)->first();
            if (! $user) continue;

            $fill = array_filter([
                'department'    => $user->department  ? null : $dept,
                'manager_id'    => ($user->manager_id || ! $managerEmail) ? null : User::where('email', $managerEmail)->value('id'),
                'employee_code' => $user->employee_code ? null : $code,
                'joined_on'     => $user->joined_on   ? null : $joined,
                'date_of_birth' => ($user->date_of_birth || ! $dob) ? null : $dob,
            ]);

            if ($fill) {
                $user->forceFill($fill)->saveQuietly();
            }
        }

        // Indian & general public holidays
        $holidays = [
            '2026-11-04' => 'Diwali',
            '2026-12-25' => 'Christmas Day',
            '2027-01-01' => "New Year's Day",
            '2027-01-14' => 'Makar Sankranti / Pongal',
            '2027-01-26' => 'Republic Day',
            '2027-03-17' => 'Holi',
            '2027-04-02' => 'Good Friday',
            '2027-05-01' => 'Labour Day',
            '2027-08-15' => 'Independence Day',
            '2027-10-02' => 'Gandhi Jayanti',
            '2027-10-20' => 'Dussehra',
        ];

        foreach ($holidays as $date => $name) {
            Holiday::firstOrCreate(['date' => $date], ['name' => $name]);
        }
    }
}
