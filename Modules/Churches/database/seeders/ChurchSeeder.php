<?php

namespace Modules\Churches\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Churches\Models\Church;

class ChurchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $churches = [
            'Watoto Church',
            'Miracle Centre Cathedral',
            'Phaneroo Ministries International',
            'Worship Harvest Ministries',
            'Christ Embassy Uganda',
            'Deliverance Church Uganda',
            'Kampala Pentecostal Church (KPC)',
            'Watoto Ministries',
            'Liberty Worship Centre International',
            'Makerere Full Gospel Church',
            'All Saints Cathedral Kampala',
            'Namirembe Cathedral',
            'St. Francis Chapel Makerere',
            'Uganda Martyrs Catholic Shrine Namugongo',
            'Munyonyo Martyrs Shrine',
            "St. Mary's Cathedral Rubaga",
            'Christ the King Catholic Church',
            'Kampala Central Seventh-day Adventist Church',
            'Uganda Assemblies of God',
            'Full Gospel Churches of Uganda',
            'Elim Pentecostal Church Uganda',
            'Victory Christian Centre',
            'Christian Life Church',
            'Life Church Uganda',
            'House of Prayer Ministries International',
            'Zoe Ministries Uganda',
            'Robert Kayanja Ministries',
            'Kakande Ministries',
            "Christ's Heart Ministries International",
            'Apostle Grace Lubega Ministries',
            'Worship Harvest International',
            'Dove Church Kampala',
            'C3 Church Kampala',
            'Trinity Chapel Kampala',
            'Calvary Chapel Kampala',
            'Rhema Bible Church Uganda',
            'Miracle Centre Ministries',
            'Watoto Childcare Ministries',
            'Compassion International Uganda',
            'Scripture Union Uganda',
            'Bible Society of Uganda',
            'Alpha Uganda',
            'Focus on the Family Uganda',
        ];

        foreach ($churches as $churchName) {
            Church::firstOrCreate(
                ['name' => $churchName],
                ['location' => 'Uganda']
            );
        }
    }
}
