<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\StandarBobot;
use App\Models\LivestockStrain;
use App\Models\LivestockStrainStandard;

class StrainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $supervisor = User::where('email', 'supervisor@demo.com')->first();
        if (!$supervisor) {
            $this->command->error("Supervisor user not found. Cannot create standard weight data.");
            return;
        }

        $this->command->info('Generating standard weight data...');

        $strains = [
            'Cobb' => [
                'name' => 'Cobb',
                'description' => 'Cobb 500 Broiler',
                'data' => [
                    0 => [
                        'bobot' => ['min' => 40, 'max' => 44, 'target' => 42],
                        'feed_intake' => ['min' => 0, 'max' => 0, 'target' => 0],
                        'fcr' => ['min' => 0, 'max' => 0, 'target' => 0]
                    ],
                    1 => [
                        'bobot' => ['min' => 43, 'max' => 47, 'target' => 45],
                        'feed_intake' => ['min' => 5, 'max' => 7, 'target' => 6],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    2 => [
                        'bobot' => ['min' => 46, 'max' => 50, 'target' => 48],
                        'feed_intake' => ['min' => 10, 'max' => 12, 'target' => 11],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    3 => [
                        'bobot' => ['min' => 50, 'max' => 54, 'target' => 52],
                        'feed_intake' => ['min' => 15, 'max' => 17, 'target' => 16],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    4 => [
                        'bobot' => ['min' => 55, 'max' => 59, 'target' => 57],
                        'feed_intake' => ['min' => 20, 'max' => 22, 'target' => 21],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    5 => [
                        'bobot' => ['min' => 61, 'max' => 65, 'target' => 63],
                        'feed_intake' => ['min' => 25, 'max' => 27, 'target' => 26],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    6 => [
                        'bobot' => ['min' => 68, 'max' => 72, 'target' => 70],
                        'feed_intake' => ['min' => 30, 'max' => 32, 'target' => 31],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    7 => [
                        'bobot' => ['min' => 76, 'max' => 80, 'target' => 78],
                        'feed_intake' => ['min' => 35, 'max' => 37, 'target' => 36],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    8 => [
                        'bobot' => ['min' => 85, 'max' => 89, 'target' => 87],
                        'feed_intake' => ['min' => 40, 'max' => 42, 'target' => 41],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    9 => [
                        'bobot' => ['min' => 95, 'max' => 99, 'target' => 97],
                        'feed_intake' => ['min' => 45, 'max' => 47, 'target' => 46],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    10 => [
                        'bobot' => ['min' => 106, 'max' => 110, 'target' => 108],
                        'feed_intake' => ['min' => 50, 'max' => 52, 'target' => 51],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    11 => [
                        'bobot' => ['min' => 118, 'max' => 122, 'target' => 120],
                        'feed_intake' => ['min' => 55, 'max' => 57, 'target' => 56],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    12 => [
                        'bobot' => ['min' => 131, 'max' => 135, 'target' => 133],
                        'feed_intake' => ['min' => 60, 'max' => 62, 'target' => 61],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    13 => [
                        'bobot' => ['min' => 145, 'max' => 149, 'target' => 147],
                        'feed_intake' => ['min' => 65, 'max' => 67, 'target' => 66],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    14 => [
                        'bobot' => ['min' => 160, 'max' => 164, 'target' => 162],
                        'feed_intake' => ['min' => 70, 'max' => 72, 'target' => 71],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    15 => [
                        'bobot' => ['min' => 176, 'max' => 180, 'target' => 178],
                        'feed_intake' => ['min' => 75, 'max' => 77, 'target' => 76],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    16 => [
                        'bobot' => ['min' => 193, 'max' => 197, 'target' => 195],
                        'feed_intake' => ['min' => 80, 'max' => 82, 'target' => 81],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    17 => [
                        'bobot' => ['min' => 211, 'max' => 215, 'target' => 213],
                        'feed_intake' => ['min' => 85, 'max' => 87, 'target' => 86],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    18 => [
                        'bobot' => ['min' => 230, 'max' => 234, 'target' => 232],
                        'feed_intake' => ['min' => 90, 'max' => 92, 'target' => 91],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    19 => [
                        'bobot' => ['min' => 250, 'max' => 254, 'target' => 252],
                        'feed_intake' => ['min' => 95, 'max' => 97, 'target' => 96],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    20 => [
                        'bobot' => ['min' => 271, 'max' => 275, 'target' => 273],
                        'feed_intake' => ['min' => 100, 'max' => 102, 'target' => 101],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    21 => [
                        'bobot' => ['min' => 293, 'max' => 297, 'target' => 295],
                        'feed_intake' => ['min' => 105, 'max' => 107, 'target' => 106],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    22 => [
                        'bobot' => ['min' => 316, 'max' => 320, 'target' => 318],
                        'feed_intake' => ['min' => 110, 'max' => 112, 'target' => 111],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    23 => [
                        'bobot' => ['min' => 340, 'max' => 344, 'target' => 342],
                        'feed_intake' => ['min' => 115, 'max' => 117, 'target' => 116],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    24 => [
                        'bobot' => ['min' => 365, 'max' => 369, 'target' => 367],
                        'feed_intake' => ['min' => 120, 'max' => 122, 'target' => 121],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    25 => [
                        'bobot' => ['min' => 391, 'max' => 395, 'target' => 393],
                        'feed_intake' => ['min' => 125, 'max' => 127, 'target' => 126],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    26 => [
                        'bobot' => ['min' => 418, 'max' => 422, 'target' => 420],
                        'feed_intake' => ['min' => 130, 'max' => 132, 'target' => 131],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    27 => [
                        'bobot' => ['min' => 446, 'max' => 450, 'target' => 448],
                        'feed_intake' => ['min' => 135, 'max' => 137, 'target' => 136],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    28 => [
                        'bobot' => ['min' => 475, 'max' => 479, 'target' => 477],
                        'feed_intake' => ['min' => 140, 'max' => 142, 'target' => 141],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    29 => [
                        'bobot' => ['min' => 505, 'max' => 509, 'target' => 507],
                        'feed_intake' => ['min' => 145, 'max' => 147, 'target' => 146],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    30 => [
                        'bobot' => ['min' => 536, 'max' => 540, 'target' => 538],
                        'feed_intake' => ['min' => 150, 'max' => 152, 'target' => 151],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    31 => [
                        'bobot' => ['min' => 568, 'max' => 572, 'target' => 570],
                        'feed_intake' => ['min' => 155, 'max' => 157, 'target' => 156],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    32 => [
                        'bobot' => ['min' => 601, 'max' => 605, 'target' => 603],
                        'feed_intake' => ['min' => 160, 'max' => 162, 'target' => 161],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    33 => [
                        'bobot' => ['min' => 635, 'max' => 639, 'target' => 637],
                        'feed_intake' => ['min' => 165, 'max' => 167, 'target' => 166],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    34 => [
                        'bobot' => ['min' => 670, 'max' => 674, 'target' => 672],
                        'feed_intake' => ['min' => 170, 'max' => 172, 'target' => 171],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    35 => [
                        'bobot' => ['min' => 706, 'max' => 710, 'target' => 708],
                        'feed_intake' => ['min' => 175, 'max' => 177, 'target' => 176],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    36 => [
                        'bobot' => ['min' => 743, 'max' => 747, 'target' => 745],
                        'feed_intake' => ['min' => 180, 'max' => 182, 'target' => 181],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    37 => [
                        'bobot' => ['min' => 781, 'max' => 785, 'target' => 783],
                        'feed_intake' => ['min' => 185, 'max' => 187, 'target' => 186],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    38 => [
                        'bobot' => ['min' => 820, 'max' => 824, 'target' => 822],
                        'feed_intake' => ['min' => 190, 'max' => 192, 'target' => 191],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    39 => [
                        'bobot' => ['min' => 860, 'max' => 864, 'target' => 862],
                        'feed_intake' => ['min' => 195, 'max' => 197, 'target' => 196],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    40 => [
                        'bobot' => ['min' => 901, 'max' => 905, 'target' => 903],
                        'feed_intake' => ['min' => 200, 'max' => 202, 'target' => 201],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    41 => [
                        'bobot' => ['min' => 943, 'max' => 947, 'target' => 945],
                        'feed_intake' => ['min' => 205, 'max' => 207, 'target' => 206],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    42 => [
                        'bobot' => ['min' => 986, 'max' => 990, 'target' => 988],
                        'feed_intake' => ['min' => 210, 'max' => 212, 'target' => 211],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                ]
            ],
            'Ross' => [
                'name' => 'Ross',
                'description' => 'Ross 308 Broiler',
                'data' => [
                    0 => [
                        'bobot' => ['min' => 38, 'max' => 42, 'target' => 40],
                        'feed_intake' => ['min' => 0, 'max' => 0, 'target' => 0],
                        'fcr' => ['min' => 0, 'max' => 0, 'target' => 0]
                    ],
                    1 => [
                        'bobot' => ['min' => 41, 'max' => 45, 'target' => 43],
                        'feed_intake' => ['min' => 5, 'max' => 7, 'target' => 6],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    2 => [
                        'bobot' => ['min' => 44, 'max' => 48, 'target' => 46],
                        'feed_intake' => ['min' => 10, 'max' => 12, 'target' => 11],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    3 => [
                        'bobot' => ['min' => 50, 'max' => 54, 'target' => 52],
                        'feed_intake' => ['min' => 15, 'max' => 17, 'target' => 16],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    4 => [
                        'bobot' => ['min' => 55, 'max' => 59, 'target' => 57],
                        'feed_intake' => ['min' => 20, 'max' => 22, 'target' => 21],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    5 => [
                        'bobot' => ['min' => 61, 'max' => 65, 'target' => 63],
                        'feed_intake' => ['min' => 25, 'max' => 27, 'target' => 26],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    6 => [
                        'bobot' => ['min' => 68, 'max' => 72, 'target' => 70],
                        'feed_intake' => ['min' => 30, 'max' => 32, 'target' => 31],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    7 => [
                        'bobot' => ['min' => 76, 'max' => 80, 'target' => 78],
                        'feed_intake' => ['min' => 35, 'max' => 37, 'target' => 36],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    8 => [
                        'bobot' => ['min' => 85, 'max' => 89, 'target' => 87],
                        'feed_intake' => ['min' => 40, 'max' => 42, 'target' => 41],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    9 => [
                        'bobot' => ['min' => 95, 'max' => 99, 'target' => 97],
                        'feed_intake' => ['min' => 45, 'max' => 47, 'target' => 46],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    10 => [
                        'bobot' => ['min' => 106, 'max' => 110, 'target' => 108],
                        'feed_intake' => ['min' => 50, 'max' => 52, 'target' => 51],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    11 => [
                        'bobot' => ['min' => 118, 'max' => 122, 'target' => 120],
                        'feed_intake' => ['min' => 55, 'max' => 57, 'target' => 56],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    12 => [
                        'bobot' => ['min' => 131, 'max' => 135, 'target' => 133],
                        'feed_intake' => ['min' => 60, 'max' => 62, 'target' => 61],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    13 => [
                        'bobot' => ['min' => 145, 'max' => 149, 'target' => 147],
                        'feed_intake' => ['min' => 65, 'max' => 67, 'target' => 66],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    14 => [
                        'bobot' => ['min' => 160, 'max' => 164, 'target' => 162],
                        'feed_intake' => ['min' => 70, 'max' => 72, 'target' => 71],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    15 => [
                        'bobot' => ['min' => 176, 'max' => 180, 'target' => 178],
                        'feed_intake' => ['min' => 75, 'max' => 77, 'target' => 76],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    16 => [
                        'bobot' => ['min' => 193, 'max' => 197, 'target' => 195],
                        'feed_intake' => ['min' => 80, 'max' => 82, 'target' => 81],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    17 => [
                        'bobot' => ['min' => 211, 'max' => 215, 'target' => 213],
                        'feed_intake' => ['min' => 85, 'max' => 87, 'target' => 86],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    18 => [
                        'bobot' => ['min' => 230, 'max' => 234, 'target' => 232],
                        'feed_intake' => ['min' => 90, 'max' => 92, 'target' => 91],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    19 => [
                        'bobot' => ['min' => 250, 'max' => 254, 'target' => 252],
                        'feed_intake' => ['min' => 95, 'max' => 97, 'target' => 96],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    20 => [
                        'bobot' => ['min' => 271, 'max' => 275, 'target' => 273],
                        'feed_intake' => ['min' => 100, 'max' => 102, 'target' => 101],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    21 => [
                        'bobot' => ['min' => 293, 'max' => 297, 'target' => 295],
                        'feed_intake' => ['min' => 105, 'max' => 107, 'target' => 106],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    22 => [
                        'bobot' => ['min' => 316, 'max' => 320, 'target' => 318],
                        'feed_intake' => ['min' => 110, 'max' => 112, 'target' => 111],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    23 => [
                        'bobot' => ['min' => 340, 'max' => 344, 'target' => 342],
                        'feed_intake' => ['min' => 115, 'max' => 117, 'target' => 116],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    24 => [
                        'bobot' => ['min' => 365, 'max' => 369, 'target' => 367],
                        'feed_intake' => ['min' => 120, 'max' => 122, 'target' => 121],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    25 => [
                        'bobot' => ['min' => 391, 'max' => 395, 'target' => 393],
                        'feed_intake' => ['min' => 125, 'max' => 127, 'target' => 126],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    26 => [
                        'bobot' => ['min' => 418, 'max' => 422, 'target' => 420],
                        'feed_intake' => ['min' => 130, 'max' => 132, 'target' => 131],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    27 => [
                        'bobot' => ['min' => 446, 'max' => 450, 'target' => 448],
                        'feed_intake' => ['min' => 135, 'max' => 137, 'target' => 136],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    28 => [
                        'bobot' => ['min' => 475, 'max' => 479, 'target' => 477],
                        'feed_intake' => ['min' => 140, 'max' => 142, 'target' => 141],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    29 => [
                        'bobot' => ['min' => 505, 'max' => 509, 'target' => 507],
                        'feed_intake' => ['min' => 145, 'max' => 147, 'target' => 146],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    30 => [
                        'bobot' => ['min' => 536, 'max' => 540, 'target' => 538],
                        'feed_intake' => ['min' => 150, 'max' => 152, 'target' => 151],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    31 => [
                        'bobot' => ['min' => 568, 'max' => 572, 'target' => 570],
                        'feed_intake' => ['min' => 155, 'max' => 157, 'target' => 156],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    32 => [
                        'bobot' => ['min' => 601, 'max' => 605, 'target' => 603],
                        'feed_intake' => ['min' => 160, 'max' => 162, 'target' => 161],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    33 => [
                        'bobot' => ['min' => 635, 'max' => 639, 'target' => 637],
                        'feed_intake' => ['min' => 165, 'max' => 167, 'target' => 166],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    34 => [
                        'bobot' => ['min' => 670, 'max' => 674, 'target' => 672],
                        'feed_intake' => ['min' => 170, 'max' => 172, 'target' => 171],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    35 => [
                        'bobot' => ['min' => 706, 'max' => 710, 'target' => 708],
                        'feed_intake' => ['min' => 175, 'max' => 177, 'target' => 176],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    36 => [
                        'bobot' => ['min' => 743, 'max' => 747, 'target' => 745],
                        'feed_intake' => ['min' => 180, 'max' => 182, 'target' => 181],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    37 => [
                        'bobot' => ['min' => 781, 'max' => 785, 'target' => 783],
                        'feed_intake' => ['min' => 185, 'max' => 187, 'target' => 186],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    38 => [
                        'bobot' => ['min' => 820, 'max' => 824, 'target' => 822],
                        'feed_intake' => ['min' => 190, 'max' => 192, 'target' => 191],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    39 => [
                        'bobot' => ['min' => 860, 'max' => 864, 'target' => 862],
                        'feed_intake' => ['min' => 195, 'max' => 197, 'target' => 196],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    40 => [
                        'bobot' => ['min' => 901, 'max' => 905, 'target' => 903],
                        'feed_intake' => ['min' => 200, 'max' => 202, 'target' => 201],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    41 => [
                        'bobot' => ['min' => 943, 'max' => 947, 'target' => 945],
                        'feed_intake' => ['min' => 205, 'max' => 207, 'target' => 206],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    42 => [
                        'bobot' => ['min' => 984, 'max' => 988, 'target' => 986],
                        'feed_intake' => ['min' => 210, 'max' => 212, 'target' => 211],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                ]
            ],
            'Grade 1' => [
                'name' => 'Grade 1',
                'description' => 'Grade 1 Broiler',
                'data' => [
                    0 => [
                        'bobot' => ['min' => 39, 'max' => 43, 'target' => 41],
                        'feed_intake' => ['min' => 0, 'max' => 0, 'target' => 0],
                        'fcr' => ['min' => 0, 'max' => 0, 'target' => 0]
                    ],
                    1 => [
                        'bobot' => ['min' => 42, 'max' => 46, 'target' => 44],
                        'feed_intake' => ['min' => 5, 'max' => 7, 'target' => 6],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    2 => [
                        'bobot' => ['min' => 45, 'max' => 49, 'target' => 47],
                        'feed_intake' => ['min' => 10, 'max' => 12, 'target' => 11],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    3 => [
                        'bobot' => ['min' => 50, 'max' => 54, 'target' => 52],
                        'feed_intake' => ['min' => 15, 'max' => 17, 'target' => 16],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    4 => [
                        'bobot' => ['min' => 55, 'max' => 59, 'target' => 57],
                        'feed_intake' => ['min' => 20, 'max' => 22, 'target' => 21],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    5 => [
                        'bobot' => ['min' => 61, 'max' => 65, 'target' => 63],
                        'feed_intake' => ['min' => 25, 'max' => 27, 'target' => 26],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    6 => [
                        'bobot' => ['min' => 68, 'max' => 72, 'target' => 70],
                        'feed_intake' => ['min' => 30, 'max' => 32, 'target' => 31],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    7 => [
                        'bobot' => ['min' => 76, 'max' => 80, 'target' => 78],
                        'feed_intake' => ['min' => 35, 'max' => 37, 'target' => 36],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    8 => [
                        'bobot' => ['min' => 85, 'max' => 89, 'target' => 87],
                        'feed_intake' => ['min' => 40, 'max' => 42, 'target' => 41],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    9 => [
                        'bobot' => ['min' => 95, 'max' => 99, 'target' => 97],
                        'feed_intake' => ['min' => 45, 'max' => 47, 'target' => 46],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    10 => [
                        'bobot' => ['min' => 106, 'max' => 110, 'target' => 108],
                        'feed_intake' => ['min' => 50, 'max' => 52, 'target' => 51],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    11 => [
                        'bobot' => ['min' => 118, 'max' => 122, 'target' => 120],
                        'feed_intake' => ['min' => 55, 'max' => 57, 'target' => 56],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    12 => [
                        'bobot' => ['min' => 131, 'max' => 135, 'target' => 133],
                        'feed_intake' => ['min' => 60, 'max' => 62, 'target' => 61],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    13 => [
                        'bobot' => ['min' => 145, 'max' => 149, 'target' => 147],
                        'feed_intake' => ['min' => 65, 'max' => 67, 'target' => 66],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    14 => [
                        'bobot' => ['min' => 160, 'max' => 164, 'target' => 162],
                        'feed_intake' => ['min' => 70, 'max' => 72, 'target' => 71],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    15 => [
                        'bobot' => ['min' => 176, 'max' => 180, 'target' => 178],
                        'feed_intake' => ['min' => 75, 'max' => 77, 'target' => 76],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    16 => [
                        'bobot' => ['min' => 193, 'max' => 197, 'target' => 195],
                        'feed_intake' => ['min' => 80, 'max' => 82, 'target' => 81],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    17 => [
                        'bobot' => ['min' => 211, 'max' => 215, 'target' => 213],
                        'feed_intake' => ['min' => 85, 'max' => 87, 'target' => 86],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    18 => [
                        'bobot' => ['min' => 230, 'max' => 234, 'target' => 232],
                        'feed_intake' => ['min' => 90, 'max' => 92, 'target' => 91],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    19 => [
                        'bobot' => ['min' => 250, 'max' => 254, 'target' => 252],
                        'feed_intake' => ['min' => 95, 'max' => 97, 'target' => 96],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    20 => [
                        'bobot' => ['min' => 271, 'max' => 275, 'target' => 273],
                        'feed_intake' => ['min' => 100, 'max' => 102, 'target' => 101],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    21 => [
                        'bobot' => ['min' => 293, 'max' => 297, 'target' => 295],
                        'feed_intake' => ['min' => 105, 'max' => 107, 'target' => 106],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    22 => [
                        'bobot' => ['min' => 316, 'max' => 320, 'target' => 318],
                        'feed_intake' => ['min' => 110, 'max' => 112, 'target' => 111],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    23 => [
                        'bobot' => ['min' => 340, 'max' => 344, 'target' => 342],
                        'feed_intake' => ['min' => 115, 'max' => 117, 'target' => 116],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    24 => [
                        'bobot' => ['min' => 365, 'max' => 369, 'target' => 367],
                        'feed_intake' => ['min' => 120, 'max' => 122, 'target' => 121],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    25 => [
                        'bobot' => ['min' => 391, 'max' => 395, 'target' => 393],
                        'feed_intake' => ['min' => 125, 'max' => 127, 'target' => 126],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    26 => [
                        'bobot' => ['min' => 418, 'max' => 422, 'target' => 420],
                        'feed_intake' => ['min' => 130, 'max' => 132, 'target' => 131],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    27 => [
                        'bobot' => ['min' => 446, 'max' => 450, 'target' => 448],
                        'feed_intake' => ['min' => 135, 'max' => 137, 'target' => 136],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    28 => [
                        'bobot' => ['min' => 475, 'max' => 479, 'target' => 477],
                        'feed_intake' => ['min' => 140, 'max' => 142, 'target' => 141],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    29 => [
                        'bobot' => ['min' => 505, 'max' => 509, 'target' => 507],
                        'feed_intake' => ['min' => 145, 'max' => 147, 'target' => 146],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    30 => [
                        'bobot' => ['min' => 536, 'max' => 540, 'target' => 538],
                        'feed_intake' => ['min' => 150, 'max' => 152, 'target' => 151],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    31 => [
                        'bobot' => ['min' => 568, 'max' => 572, 'target' => 570],
                        'feed_intake' => ['min' => 155, 'max' => 157, 'target' => 156],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    32 => [
                        'bobot' => ['min' => 601, 'max' => 605, 'target' => 603],
                        'feed_intake' => ['min' => 160, 'max' => 162, 'target' => 161],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    33 => [
                        'bobot' => ['min' => 635, 'max' => 639, 'target' => 637],
                        'feed_intake' => ['min' => 165, 'max' => 167, 'target' => 166],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    34 => [
                        'bobot' => ['min' => 670, 'max' => 674, 'target' => 672],
                        'feed_intake' => ['min' => 170, 'max' => 172, 'target' => 171],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    35 => [
                        'bobot' => ['min' => 706, 'max' => 710, 'target' => 708],
                        'feed_intake' => ['min' => 175, 'max' => 177, 'target' => 176],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    36 => [
                        'bobot' => ['min' => 743, 'max' => 747, 'target' => 745],
                        'feed_intake' => ['min' => 180, 'max' => 182, 'target' => 181],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    37 => [
                        'bobot' => ['min' => 781, 'max' => 785, 'target' => 783],
                        'feed_intake' => ['min' => 185, 'max' => 187, 'target' => 186],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    38 => [
                        'bobot' => ['min' => 820, 'max' => 824, 'target' => 822],
                        'feed_intake' => ['min' => 190, 'max' => 192, 'target' => 191],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    39 => [
                        'bobot' => ['min' => 860, 'max' => 864, 'target' => 862],
                        'feed_intake' => ['min' => 195, 'max' => 197, 'target' => 196],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    40 => [
                        'bobot' => ['min' => 901, 'max' => 905, 'target' => 903],
                        'feed_intake' => ['min' => 200, 'max' => 202, 'target' => 201],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    41 => [
                        'bobot' => ['min' => 943, 'max' => 947, 'target' => 945],
                        'feed_intake' => ['min' => 205, 'max' => 207, 'target' => 206],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    42 => [
                        'bobot' => ['min' => 985, 'max' => 989, 'target' => 987],
                        'feed_intake' => ['min' => 210, 'max' => 212, 'target' => 211],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                ]
            ],
            'Grade 2' => [
                'name' => 'Grade 2',
                'description' => 'Grade 2 Broiler',
                'data' => [
                    0 => [
                        'bobot' => ['min' => 38, 'max' => 42, 'target' => 40],
                        'feed_intake' => ['min' => 0, 'max' => 0, 'target' => 0],
                        'fcr' => ['min' => 0, 'max' => 0, 'target' => 0]
                    ],
                    1 => [
                        'bobot' => ['min' => 41, 'max' => 45, 'target' => 43],
                        'feed_intake' => ['min' => 5, 'max' => 7, 'target' => 6],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    2 => [
                        'bobot' => ['min' => 44, 'max' => 48, 'target' => 46],
                        'feed_intake' => ['min' => 10, 'max' => 12, 'target' => 11],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    3 => [
                        'bobot' => ['min' => 50, 'max' => 54, 'target' => 52],
                        'feed_intake' => ['min' => 15, 'max' => 17, 'target' => 16],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    4 => [
                        'bobot' => ['min' => 55, 'max' => 59, 'target' => 57],
                        'feed_intake' => ['min' => 20, 'max' => 22, 'target' => 21],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    5 => [
                        'bobot' => ['min' => 61, 'max' => 65, 'target' => 63],
                        'feed_intake' => ['min' => 25, 'max' => 27, 'target' => 26],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    6 => [
                        'bobot' => ['min' => 68, 'max' => 72, 'target' => 70],
                        'feed_intake' => ['min' => 30, 'max' => 32, 'target' => 31],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    7 => [
                        'bobot' => ['min' => 76, 'max' => 80, 'target' => 78],
                        'feed_intake' => ['min' => 35, 'max' => 37, 'target' => 36],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    8 => [
                        'bobot' => ['min' => 85, 'max' => 89, 'target' => 87],
                        'feed_intake' => ['min' => 40, 'max' => 42, 'target' => 41],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    9 => [
                        'bobot' => ['min' => 95, 'max' => 99, 'target' => 97],
                        'feed_intake' => ['min' => 45, 'max' => 47, 'target' => 46],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    10 => [
                        'bobot' => ['min' => 106, 'max' => 110, 'target' => 108],
                        'feed_intake' => ['min' => 50, 'max' => 52, 'target' => 51],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    11 => [
                        'bobot' => ['min' => 118, 'max' => 122, 'target' => 120],
                        'feed_intake' => ['min' => 55, 'max' => 57, 'target' => 56],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    12 => [
                        'bobot' => ['min' => 131, 'max' => 135, 'target' => 133],
                        'feed_intake' => ['min' => 60, 'max' => 62, 'target' => 61],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    13 => [
                        'bobot' => ['min' => 145, 'max' => 149, 'target' => 147],
                        'feed_intake' => ['min' => 65, 'max' => 67, 'target' => 66],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    14 => [
                        'bobot' => ['min' => 160, 'max' => 164, 'target' => 162],
                        'feed_intake' => ['min' => 70, 'max' => 72, 'target' => 71],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    15 => [
                        'bobot' => ['min' => 176, 'max' => 180, 'target' => 178],
                        'feed_intake' => ['min' => 75, 'max' => 77, 'target' => 76],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    16 => [
                        'bobot' => ['min' => 193, 'max' => 197, 'target' => 195],
                        'feed_intake' => ['min' => 80, 'max' => 82, 'target' => 81],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    17 => [
                        'bobot' => ['min' => 211, 'max' => 215, 'target' => 213],
                        'feed_intake' => ['min' => 85, 'max' => 87, 'target' => 86],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    18 => [
                        'bobot' => ['min' => 230, 'max' => 234, 'target' => 232],
                        'feed_intake' => ['min' => 90, 'max' => 92, 'target' => 91],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    19 => [
                        'bobot' => ['min' => 250, 'max' => 254, 'target' => 252],
                        'feed_intake' => ['min' => 95, 'max' => 97, 'target' => 96],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    20 => [
                        'bobot' => ['min' => 271, 'max' => 275, 'target' => 273],
                        'feed_intake' => ['min' => 100, 'max' => 102, 'target' => 101],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    21 => [
                        'bobot' => ['min' => 293, 'max' => 297, 'target' => 295],
                        'feed_intake' => ['min' => 105, 'max' => 107, 'target' => 106],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    22 => [
                        'bobot' => ['min' => 316, 'max' => 320, 'target' => 318],
                        'feed_intake' => ['min' => 110, 'max' => 112, 'target' => 111],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    23 => [
                        'bobot' => ['min' => 340, 'max' => 344, 'target' => 342],
                        'feed_intake' => ['min' => 115, 'max' => 117, 'target' => 116],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    24 => [
                        'bobot' => ['min' => 365, 'max' => 369, 'target' => 367],
                        'feed_intake' => ['min' => 120, 'max' => 122, 'target' => 121],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    25 => [
                        'bobot' => ['min' => 391, 'max' => 395, 'target' => 393],
                        'feed_intake' => ['min' => 125, 'max' => 127, 'target' => 126],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    26 => [
                        'bobot' => ['min' => 418, 'max' => 422, 'target' => 420],
                        'feed_intake' => ['min' => 130, 'max' => 132, 'target' => 131],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    27 => [
                        'bobot' => ['min' => 446, 'max' => 450, 'target' => 448],
                        'feed_intake' => ['min' => 135, 'max' => 137, 'target' => 136],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    28 => [
                        'bobot' => ['min' => 475, 'max' => 479, 'target' => 477],
                        'feed_intake' => ['min' => 140, 'max' => 142, 'target' => 141],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    29 => [
                        'bobot' => ['min' => 505, 'max' => 509, 'target' => 507],
                        'feed_intake' => ['min' => 145, 'max' => 147, 'target' => 146],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    30 => [
                        'bobot' => ['min' => 536, 'max' => 540, 'target' => 538],
                        'feed_intake' => ['min' => 150, 'max' => 152, 'target' => 151],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    31 => [
                        'bobot' => ['min' => 568, 'max' => 572, 'target' => 570],
                        'feed_intake' => ['min' => 155, 'max' => 157, 'target' => 156],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    32 => [
                        'bobot' => ['min' => 601, 'max' => 605, 'target' => 603],
                        'feed_intake' => ['min' => 160, 'max' => 162, 'target' => 161],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    33 => [
                        'bobot' => ['min' => 635, 'max' => 639, 'target' => 637],
                        'feed_intake' => ['min' => 165, 'max' => 167, 'target' => 166],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    34 => [
                        'bobot' => ['min' => 670, 'max' => 674, 'target' => 672],
                        'feed_intake' => ['min' => 170, 'max' => 172, 'target' => 171],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    35 => [
                        'bobot' => ['min' => 706, 'max' => 710, 'target' => 708],
                        'feed_intake' => ['min' => 175, 'max' => 177, 'target' => 176],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    36 => [
                        'bobot' => ['min' => 743, 'max' => 747, 'target' => 745],
                        'feed_intake' => ['min' => 180, 'max' => 182, 'target' => 181],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    37 => [
                        'bobot' => ['min' => 781, 'max' => 785, 'target' => 783],
                        'feed_intake' => ['min' => 185, 'max' => 187, 'target' => 186],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    38 => [
                        'bobot' => ['min' => 820, 'max' => 824, 'target' => 822],
                        'feed_intake' => ['min' => 190, 'max' => 192, 'target' => 191],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    39 => [
                        'bobot' => ['min' => 860, 'max' => 864, 'target' => 862],
                        'feed_intake' => ['min' => 195, 'max' => 197, 'target' => 196],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    40 => [
                        'bobot' => ['min' => 901, 'max' => 905, 'target' => 903],
                        'feed_intake' => ['min' => 200, 'max' => 202, 'target' => 201],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    41 => [
                        'bobot' => ['min' => 943, 'max' => 947, 'target' => 945],
                        'feed_intake' => ['min' => 205, 'max' => 207, 'target' => 206],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    42 => [
                        'bobot' => ['min' => 983, 'max' => 987, 'target' => 985],
                        'feed_intake' => ['min' => 210, 'max' => 212, 'target' => 211],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                ]
            ],
            'Grade 3' => [
                'name' => 'Grade 3',
                'description' => 'Grade 3 Broiler',
                'data' => [
                    0 => [
                        'bobot' => ['min' => 37, 'max' => 41, 'target' => 39],
                        'feed_intake' => ['min' => 0, 'max' => 0, 'target' => 0],
                        'fcr' => ['min' => 0, 'max' => 0, 'target' => 0]
                    ],
                    1 => [
                        'bobot' => ['min' => 40, 'max' => 44, 'target' => 42],
                        'feed_intake' => ['min' => 5, 'max' => 7, 'target' => 6],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    2 => [
                        'bobot' => ['min' => 43, 'max' => 47, 'target' => 45],
                        'feed_intake' => ['min' => 10, 'max' => 12, 'target' => 11],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    3 => [
                        'bobot' => ['min' => 50, 'max' => 54, 'target' => 52],
                        'feed_intake' => ['min' => 15, 'max' => 17, 'target' => 16],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    4 => [
                        'bobot' => ['min' => 55, 'max' => 59, 'target' => 57],
                        'feed_intake' => ['min' => 20, 'max' => 22, 'target' => 21],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    5 => [
                        'bobot' => ['min' => 61, 'max' => 65, 'target' => 63],
                        'feed_intake' => ['min' => 25, 'max' => 27, 'target' => 26],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    6 => [
                        'bobot' => ['min' => 68, 'max' => 72, 'target' => 70],
                        'feed_intake' => ['min' => 30, 'max' => 32, 'target' => 31],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    7 => [
                        'bobot' => ['min' => 76, 'max' => 80, 'target' => 78],
                        'feed_intake' => ['min' => 35, 'max' => 37, 'target' => 36],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    8 => [
                        'bobot' => ['min' => 85, 'max' => 89, 'target' => 87],
                        'feed_intake' => ['min' => 40, 'max' => 42, 'target' => 41],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    9 => [
                        'bobot' => ['min' => 95, 'max' => 99, 'target' => 97],
                        'feed_intake' => ['min' => 45, 'max' => 47, 'target' => 46],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    10 => [
                        'bobot' => ['min' => 106, 'max' => 110, 'target' => 108],
                        'feed_intake' => ['min' => 50, 'max' => 52, 'target' => 51],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    11 => [
                        'bobot' => ['min' => 118, 'max' => 122, 'target' => 120],
                        'feed_intake' => ['min' => 55, 'max' => 57, 'target' => 56],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    12 => [
                        'bobot' => ['min' => 131, 'max' => 135, 'target' => 133],
                        'feed_intake' => ['min' => 60, 'max' => 62, 'target' => 61],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    13 => [
                        'bobot' => ['min' => 145, 'max' => 149, 'target' => 147],
                        'feed_intake' => ['min' => 65, 'max' => 67, 'target' => 66],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    14 => [
                        'bobot' => ['min' => 160, 'max' => 164, 'target' => 162],
                        'feed_intake' => ['min' => 70, 'max' => 72, 'target' => 71],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    15 => [
                        'bobot' => ['min' => 176, 'max' => 180, 'target' => 178],
                        'feed_intake' => ['min' => 75, 'max' => 77, 'target' => 76],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    16 => [
                        'bobot' => ['min' => 193, 'max' => 197, 'target' => 195],
                        'feed_intake' => ['min' => 80, 'max' => 82, 'target' => 81],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    17 => [
                        'bobot' => ['min' => 211, 'max' => 215, 'target' => 213],
                        'feed_intake' => ['min' => 85, 'max' => 87, 'target' => 86],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    18 => [
                        'bobot' => ['min' => 230, 'max' => 234, 'target' => 232],
                        'feed_intake' => ['min' => 90, 'max' => 92, 'target' => 91],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    19 => [
                        'bobot' => ['min' => 250, 'max' => 254, 'target' => 252],
                        'feed_intake' => ['min' => 95, 'max' => 97, 'target' => 96],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    20 => [
                        'bobot' => ['min' => 271, 'max' => 275, 'target' => 273],
                        'feed_intake' => ['min' => 100, 'max' => 102, 'target' => 101],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    21 => [
                        'bobot' => ['min' => 293, 'max' => 297, 'target' => 295],
                        'feed_intake' => ['min' => 105, 'max' => 107, 'target' => 106],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    22 => [
                        'bobot' => ['min' => 316, 'max' => 320, 'target' => 318],
                        'feed_intake' => ['min' => 110, 'max' => 112, 'target' => 111],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    23 => [
                        'bobot' => ['min' => 340, 'max' => 344, 'target' => 342],
                        'feed_intake' => ['min' => 115, 'max' => 117, 'target' => 116],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    24 => [
                        'bobot' => ['min' => 365, 'max' => 369, 'target' => 367],
                        'feed_intake' => ['min' => 120, 'max' => 122, 'target' => 121],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    25 => [
                        'bobot' => ['min' => 391, 'max' => 395, 'target' => 393],
                        'feed_intake' => ['min' => 125, 'max' => 127, 'target' => 126],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    26 => [
                        'bobot' => ['min' => 418, 'max' => 422, 'target' => 420],
                        'feed_intake' => ['min' => 130, 'max' => 132, 'target' => 131],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    27 => [
                        'bobot' => ['min' => 446, 'max' => 450, 'target' => 448],
                        'feed_intake' => ['min' => 135, 'max' => 137, 'target' => 136],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    28 => [
                        'bobot' => ['min' => 475, 'max' => 479, 'target' => 477],
                        'feed_intake' => ['min' => 140, 'max' => 142, 'target' => 141],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    29 => [
                        'bobot' => ['min' => 505, 'max' => 509, 'target' => 507],
                        'feed_intake' => ['min' => 145, 'max' => 147, 'target' => 146],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    30 => [
                        'bobot' => ['min' => 536, 'max' => 540, 'target' => 538],
                        'feed_intake' => ['min' => 150, 'max' => 152, 'target' => 151],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    31 => [
                        'bobot' => ['min' => 568, 'max' => 572, 'target' => 570],
                        'feed_intake' => ['min' => 155, 'max' => 157, 'target' => 156],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    32 => [
                        'bobot' => ['min' => 601, 'max' => 605, 'target' => 603],
                        'feed_intake' => ['min' => 160, 'max' => 162, 'target' => 161],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    33 => [
                        'bobot' => ['min' => 635, 'max' => 639, 'target' => 637],
                        'feed_intake' => ['min' => 165, 'max' => 167, 'target' => 166],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    34 => [
                        'bobot' => ['min' => 670, 'max' => 674, 'target' => 672],
                        'feed_intake' => ['min' => 170, 'max' => 172, 'target' => 171],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    35 => [
                        'bobot' => ['min' => 706, 'max' => 710, 'target' => 708],
                        'feed_intake' => ['min' => 175, 'max' => 177, 'target' => 176],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    36 => [
                        'bobot' => ['min' => 743, 'max' => 747, 'target' => 745],
                        'feed_intake' => ['min' => 180, 'max' => 182, 'target' => 181],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    37 => [
                        'bobot' => ['min' => 781, 'max' => 785, 'target' => 783],
                        'feed_intake' => ['min' => 185, 'max' => 187, 'target' => 186],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    38 => [
                        'bobot' => ['min' => 820, 'max' => 824, 'target' => 822],
                        'feed_intake' => ['min' => 190, 'max' => 192, 'target' => 191],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    39 => [
                        'bobot' => ['min' => 860, 'max' => 864, 'target' => 862],
                        'feed_intake' => ['min' => 195, 'max' => 197, 'target' => 196],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    40 => [
                        'bobot' => ['min' => 901, 'max' => 905, 'target' => 903],
                        'feed_intake' => ['min' => 200, 'max' => 202, 'target' => 201],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    41 => [
                        'bobot' => ['min' => 943, 'max' => 947, 'target' => 945],
                        'feed_intake' => ['min' => 205, 'max' => 207, 'target' => 206],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                    42 => [
                        'bobot' => ['min' => 983, 'max' => 987, 'target' => 985],
                        'feed_intake' => ['min' => 210, 'max' => 212, 'target' => 211],
                        'fcr' => ['min' => 0.9, 'max' => 1.1, 'target' => 1.0]
                    ],
                ]
            ],
        ];

        // Create standard weight records for each strain
        foreach ($strains as $strainData) {
            $strainName = $strainData['name'];
            $strainDescription = $strainData['description'];
            $data = $strainData['data'];

            // Check if strain already exists in LivestockStrain table
            $existingStrain = LivestockStrain::where('name', $strainName)->first();
            if ($existingStrain) {
                $this->command->info("Strain '{$strainName}' already exists. Skipping standard data creation.");
                continue; // Skip to the next strain
            }

            // Create LivestockStrain entry with random code
            $randomCode = strtoupper(substr(md5(uniqid()), 0, 6));
            $livestockStrain = LivestockStrain::create([
                'code' => $randomCode,
                'name' => $strainName,
                'description' => $strainDescription,
            ]);

            // Check if LivestockStrain creation was successful
            if (!$livestockStrain) {
                $this->command->error("Failed to create LivestockStrain entry for '{$strainName}'.");
                continue; // Skip to the next strain
            }

            // Transform data into the new JSON structure for LivestockStrainStandard
            $standarData = [];
            foreach ($data as $age => $ageData) {
                $standarData[] = [
                    'umur' => $age,
                    'bobot' => [
                        'min' => $ageData['bobot']['min'],
                        'max' => $ageData['bobot']['max'],
                        'target' => $ageData['bobot']['target']
                    ],
                    'feed_intake' => [
                        'min' => $ageData['feed_intake']['min'],
                        'max' => $ageData['feed_intake']['max'],
                        'target' => $ageData['feed_intake']['target']
                    ],
                    'fcr' => [
                        'min' => $ageData['fcr']['min'],
                        'max' => $ageData['fcr']['max'],
                        'target' => $ageData['fcr']['target']
                    ]
                ];
            }

            // Create a single record in LivestockStrainStandard with all data in JSON format
            LivestockStrainStandard::create([
                'livestock_strain_id' => $livestockStrain->id,
                'livestock_strain_name' => $strainName,
                'description' => $strainDescription,
                'standar_data' => $standarData,
                'status' => 'active',
                'created_by' => $supervisor->id,
            ]);

            $this->command->info("Created LivestockStrain '{$strainName}' and standard weight data (ages 0-42).");
        }
    }
}
