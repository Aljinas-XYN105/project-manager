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
            'admin@example.com' => ['Management',  null,                 'EMP-001', '2021-03-01', '1988-05-14'],
            'maria@example.com' => ['Engineering', 'admin@example.com',  'EMP-002', '2021-06-14', '1990-02-20'],
            'james@example.com' => ['Design',      'admin@example.com',  'EMP-003', '2022-01-10', null],
            'priya@example.com' => ['Quality',     'admin@example.com',  'EMP-004', '2022-04-04', '1991-11-30'],
            'sam@example.com'   => ['Engineering', 'maria@example.com',  'EMP-005', '2023-02-01', '1996-07-08'],
            'lily@example.com'  => ['Engineering', 'maria@example.com',  'EMP-006', '2023-05-15', '1995-01-22'],
            'carlos@example.com'=> ['Engineering', 'maria@example.com',  'EMP-007', '2022-10-24', '1993-10-22'],
            'aisha@example.com' => ['Design',      'james@example.com',  'EMP-008', '2023-08-21', '1997-12-12'],
            'tom@example.com'   => ['Engineering', 'maria@example.com',  'EMP-009', '2022-11-07', '1989-04-17'],
            'nadia@example.com' => ['Engineering', 'maria@example.com',  'EMP-010', '2024-01-08', '1998-06-25'],
            'omar@example.com'  => ['Engineering', 'maria@example.com',  'EMP-011', '2024-03-18', '1994-10-05'],
            'zoe@example.com'   => ['Design',      'james@example.com',  'EMP-012', '2024-06-03', '1996-03-29'],
        ];

        foreach ($profiles as $email => [$dept, $managerEmail, $code, $joined, $dob]) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }

            $fill = array_filter([
                'department'    => $user->department ? null : $dept,
                'manager_id'    => $user->manager_id || ! $managerEmail ? null : User::where('email', $managerEmail)->value('id'),
                'employee_code' => $user->employee_code ? null : $code,
                'joined_on'     => $user->joined_on ? null : $joined,
                'date_of_birth' => $user->date_of_birth || ! $dob ? null : $dob,
            ]);

            if ($fill) {
                $user->forceFill($fill)->saveQuietly();
            }
        }

        // Sample US public holidays; admins can edit these under Admin → Leave & Holidays.
        $holidays = [
            '2026-11-26' => 'Thanksgiving Day', '2026-11-27' => 'Day after Thanksgiving',
            '2026-12-25' => 'Christmas Day',    '2027-01-01' => "New Year's Day",
            '2027-01-18' => 'Martin Luther King Jr. Day', '2027-05-31' => 'Memorial Day',
            '2027-07-05' => 'Independence Day (observed)', '2027-09-06' => 'Labor Day',
        ];
        foreach ($holidays as $date => $name) {
            Holiday::firstOrCreate(['date' => $date], ['name' => $name]);
        }
    }
}
