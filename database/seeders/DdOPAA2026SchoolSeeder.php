<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DdOPAA2026SchoolSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->schools() as $row) {
                $school = School::query()->firstOrNew(['school_id_code' => $row['school_id_code']]);
                $school->name = $row['name'];
                $school->school_type = $row['school_type'];

                if (! $school->exists) {
                    $school->district_id = null;
                    $school->school_district_id = null;
                    $school->level = null;
                    $school->active = true;
                }

                $school->save();
            }
        });
    }

    /** @return list<array{school_id_code:string,name:string,school_type:string}> */
    private function schools(): array
    {
        return [
            0 => [
                'school_id_code' => '464059',
                'name' => 'A.G.L. Montevista Baptist Christian Academy, Inc.',
                'school_type' => 'Private',
            ],
            1 => [
                'school_id_code' => '410357',
                'name' => 'Academia De Maria Mediatrix, Inc.',
                'school_type' => 'Private',
            ],
            2 => [
                'school_id_code' => '405331',
                'name' => 'Agape Christian Academy of Monkayo',
                'school_type' => 'Private',
            ],
            3 => [
                'school_id_code' => '501053',
                'name' => 'Alimadmad Integrated School',
                'school_type' => 'Public',
            ],
            4 => [
                'school_id_code' => '137006',
                'name' => 'Amogad Elementary School',
                'school_type' => 'Public',
            ],
            5 => [
                'school_id_code' => '128279',
                'name' => 'Amorcruz ES',
                'school_type' => 'Public',
            ],
            6 => [
                'school_id_code' => '501912',
                'name' => 'Anagase Integrated School',
                'school_type' => 'Public',
            ],
            7 => [
                'school_id_code' => '128510',
                'name' => 'Andap ES',
                'school_type' => 'Public',
            ],
            8 => [
                'school_id_code' => '128280',
                'name' => 'Andap ES',
                'school_type' => 'Public',
            ],
            9 => [
                'school_id_code' => '315814',
                'name' => 'Andap NHS',
                'school_type' => 'Public',
            ],
            10 => [
                'school_id_code' => '128405',
                'name' => 'Andili ES',
                'school_type' => 'Public',
            ],
            11 => [
                'school_id_code' => '304172',
                'name' => 'Andili NHS',
                'school_type' => 'Public',
            ],
            12 => [
                'school_id_code' => '128344',
                'name' => 'Anibongan ES',
                'school_type' => 'Public',
            ],
            13 => [
                'school_id_code' => '304173',
                'name' => 'Anibongan NHS',
                'school_type' => 'Public',
            ],
            14 => [
                'school_id_code' => '128363',
                'name' => 'Anislagan ES',
                'school_type' => 'Public',
            ],
            15 => [
                'school_id_code' => '128480',
                'name' => 'Anislagan ES',
                'school_type' => 'Public',
            ],
            16 => [
                'school_id_code' => '128281',
                'name' => 'Anitap ES',
                'school_type' => 'Public',
            ],
            17 => [
                'school_id_code' => '128328',
                'name' => 'Anitapan ES',
                'school_type' => 'Public',
            ],
            18 => [
                'school_id_code' => '304174',
                'name' => 'Anitapan NHS',
                'school_type' => 'Public',
            ],
            19 => [
                'school_id_code' => '502373',
                'name' => 'Antequera Integrated School',
                'school_type' => 'Public',
            ],
            20 => [
                'school_id_code' => '128532',
                'name' => 'Araibo ES',
                'school_type' => 'Public',
            ],
            21 => [
                'school_id_code' => '304175',
                'name' => 'Araibo NHS',
                'school_type' => 'Public',
            ],
            22 => [
                'school_id_code' => '405320',
                'name' => 'Assumption Academy of Compostela',
                'school_type' => 'Private',
            ],
            23 => [
                'school_id_code' => '405328',
                'name' => 'Assumption Academy of Mawab, Inc.',
                'school_type' => 'Private',
            ],
            24 => [
                'school_id_code' => '405332',
                'name' => 'Assumption Academy of Monkayo, Inc.',
                'school_type' => 'Private',
            ],
            25 => [
                'school_id_code' => '405335',
                'name' => 'Assumption College of Nabunturan',
                'school_type' => 'Private',
            ],
            26 => [
                'school_id_code' => '304193',
                'name' => 'Atty. Orlando S. Rimando NHS',
                'school_type' => 'Public',
            ],
            27 => [
                'school_id_code' => '128259',
                'name' => 'Aurora ES',
                'school_type' => 'Public',
            ],
            28 => [
                'school_id_code' => '128424',
                'name' => 'Awao ES',
                'school_type' => 'Public',
            ],
            29 => [
                'school_id_code' => '304200',
                'name' => 'Awao NHS',
                'school_type' => 'Public',
            ],
            30 => [
                'school_id_code' => '128534',
                'name' => 'Ayan ES',
                'school_type' => 'Public',
            ],
            31 => [
                'school_id_code' => '128425',
                'name' => 'Babag ES',
                'school_type' => 'Public',
            ],
            32 => [
                'school_id_code' => '304176',
                'name' => 'Babag NHS',
                'school_type' => 'Public',
            ],
            33 => [
                'school_id_code' => '128282',
                'name' => 'Bagong Silang ES',
                'school_type' => 'Public',
            ],
            34 => [
                'school_id_code' => '128378',
                'name' => 'Bagong Silang ES',
                'school_type' => 'Public',
            ],
            35 => [
                'school_id_code' => '300577',
                'name' => 'Bagong Silang National High School',
                'school_type' => 'Public',
            ],
            36 => [
                'school_id_code' => '128426',
                'name' => 'Bagong Taas ES',
                'school_type' => 'Public',
            ],
            37 => [
                'school_id_code' => '128260',
                'name' => 'Bagongon ES',
                'school_type' => 'Public',
            ],
            38 => [
                'school_id_code' => '128379',
                'name' => 'Bahi ES',
                'school_type' => 'Public',
            ],
            39 => [
                'school_id_code' => '305771',
                'name' => 'Bahi National High School',
                'school_type' => 'Public',
            ],
            40 => [
                'school_id_code' => '128459',
                'name' => 'Banagbanag ES',
                'school_type' => 'Public',
            ],
            41 => [
                'school_id_code' => '128283',
                'name' => 'Banbanon ES',
                'school_type' => 'Public',
            ],
            42 => [
                'school_id_code' => '128460',
                'name' => 'Banglasan ES',
                'school_type' => 'Public',
            ],
            43 => [
                'school_id_code' => '128261',
                'name' => 'Bango ES',
                'school_type' => 'Public',
            ],
            44 => [
                'school_id_code' => '315819',
                'name' => 'Bango NHS',
                'school_type' => 'Public',
            ],
            45 => [
                'school_id_code' => '128461',
                'name' => 'Bankerohan ES',
                'school_type' => 'Public',
            ],
            46 => [
                'school_id_code' => '204007',
                'name' => 'Bankerohan Sur ES',
                'school_type' => 'Public',
            ],
            47 => [
                'school_id_code' => '128427',
                'name' => 'Banlag ES',
                'school_type' => 'Public',
            ],
            48 => [
                'school_id_code' => '128511',
                'name' => 'Bantacan ES',
                'school_type' => 'Public',
            ],
            49 => [
                'school_id_code' => '304177',
                'name' => 'Bantacan NHS',
                'school_type' => 'Public',
            ],
            50 => [
                'school_id_code' => '128512',
                'name' => 'Barabat ES',
                'school_type' => 'Public',
            ],
            51 => [
                'school_id_code' => '137397',
                'name' => 'Barez Elementary School',
                'school_type' => 'Public',
            ],
            52 => [
                'school_id_code' => '128284',
                'name' => 'Barubo ES',
                'school_type' => 'Public',
            ],
            53 => [
                'school_id_code' => '501365',
                'name' => 'Basak Integrated School',
                'school_type' => 'Public',
            ],
            54 => [
                'school_id_code' => '128513',
                'name' => 'Batinao ES',
                'school_type' => 'Public',
            ],
            55 => [
                'school_id_code' => '128406',
                'name' => 'Bawani ES',
                'school_type' => 'Public',
            ],
            56 => [
                'school_id_code' => '128501',
                'name' => 'Bayabas ES',
                'school_type' => 'Public',
            ],
            57 => [
                'school_id_code' => '304178',
                'name' => 'Bayabas NHS',
                'school_type' => 'Public',
            ],
            58 => [
                'school_id_code' => '128285',
                'name' => 'Bayanihan ES',
                'school_type' => 'Public',
            ],
            59 => [
                'school_id_code' => '128428',
                'name' => 'Baylo ES',
                'school_type' => 'Public',
            ],
            60 => [
                'school_id_code' => '501368',
                'name' => 'Belmonte Integrated School',
                'school_type' => 'Public',
            ],
            61 => [
                'school_id_code' => '128536',
                'name' => 'Biasong ES',
                'school_type' => 'Public',
            ],
            62 => [
                'school_id_code' => '128287',
                'name' => 'Binasbas ES',
                'school_type' => 'Public',
            ],
            63 => [
                'school_id_code' => '128537',
                'name' => 'Binogsayan ES',
                'school_type' => 'Public',
            ],
            64 => [
                'school_id_code' => '409422',
                'name' => 'Bishop Joemar M. Soriano Academy of Comval, Inc.',
                'school_type' => 'Private',
            ],
            65 => [
                'school_id_code' => '128440',
                'name' => 'Bliss ES',
                'school_type' => 'Public',
            ],
            66 => [
                'school_id_code' => '128429',
                'name' => 'Boay ES',
                'school_type' => 'Public',
            ],
            67 => [
                'school_id_code' => '128535',
                'name' => 'Bon-Temple ES',
                'school_type' => 'Public',
            ],
            68 => [
                'school_id_code' => '128538',
                'name' => 'Bongabong ES',
                'school_type' => 'Public',
            ],
            69 => [
                'school_id_code' => '304179',
                'name' => 'Bongabong NHS',
                'school_type' => 'Public',
            ],
            70 => [
                'school_id_code' => '128539',
                'name' => 'Bongbong ES',
                'school_type' => 'Public',
            ],
            71 => [
                'school_id_code' => '501054',
                'name' => 'Bongkilaton Integrated School',
                'school_type' => 'Public',
            ],
            72 => [
                'school_id_code' => '128540',
                'name' => 'Boringot ES',
                'school_type' => 'Public',
            ],
            73 => [
                'school_id_code' => '300588',
                'name' => 'Boringot National High School',
                'school_type' => 'Public',
            ],
            74 => [
                'school_id_code' => '128345',
                'name' => 'Bucana ES',
                'school_type' => 'Public',
            ],
            75 => [
                'school_id_code' => '128288',
                'name' => 'Buhi ES',
                'school_type' => 'Public',
            ],
            76 => [
                'school_id_code' => '128484',
                'name' => 'Bukal ES',
                'school_type' => 'Public',
            ],
            77 => [
                'school_id_code' => '128289',
                'name' => 'Bullokan ES',
                'school_type' => 'Public',
            ],
            78 => [
                'school_id_code' => '128507',
                'name' => 'C.M. Recto ES',
                'school_type' => 'Public',
            ],
            79 => [
                'school_id_code' => '128485',
                'name' => 'Cabacungan ES',
                'school_type' => 'Public',
            ],
            80 => [
                'school_id_code' => '204019',
                'name' => 'Cabanggatan ES',
                'school_type' => 'Public',
            ],
            81 => [
                'school_id_code' => '501778',
                'name' => 'Cabangkalan Integrated School',
                'school_type' => 'Public',
            ],
            82 => [
                'school_id_code' => '128486',
                'name' => 'Cabidianan ES',
                'school_type' => 'Public',
            ],
            83 => [
                'school_id_code' => '300607',
                'name' => 'Cabidianan National High School',
                'school_type' => 'Public',
            ],
            84 => [
                'school_id_code' => '128514',
                'name' => 'Cabinuangan CES',
                'school_type' => 'Public',
            ],
            85 => [
                'school_id_code' => '128329',
                'name' => 'Cabuyoan ES',
                'school_type' => 'Public',
            ],
            86 => [
                'school_id_code' => '128330',
                'name' => 'Cadunan ES',
                'school_type' => 'Public',
            ],
            87 => [
                'school_id_code' => '300608',
                'name' => 'Cagan National High School',
                'school_type' => 'Public',
            ],
            88 => [
                'school_id_code' => '128364',
                'name' => 'Calabcab ES',
                'school_type' => 'Public',
            ],
            89 => [
                'school_id_code' => '137015',
                'name' => 'Calinogan Elementary School',
                'school_type' => 'Public',
            ],
            90 => [
                'school_id_code' => '128515',
                'name' => 'Camanlangan ES',
                'school_type' => 'Public',
            ],
            91 => [
                'school_id_code' => '304180',
                'name' => 'Camanlangan NHS',
                'school_type' => 'Public',
            ],
            92 => [
                'school_id_code' => '128462',
                'name' => 'Camansi ES',
                'school_type' => 'Public',
            ],
            93 => [
                'school_id_code' => '300640',
                'name' => 'Camansi National High School',
                'school_type' => 'Public',
            ],
            94 => [
                'school_id_code' => '128463',
                'name' => 'Camantangan ES',
                'school_type' => 'Public',
            ],
            95 => [
                'school_id_code' => '128380',
                'name' => 'Cambagang ES',
                'school_type' => 'Public',
            ],
            96 => [
                'school_id_code' => '128290',
                'name' => 'Candiis ES',
                'school_type' => 'Public',
            ],
            97 => [
                'school_id_code' => '128331',
                'name' => 'Candinuyan ES',
                'school_type' => 'Public',
            ],
            98 => [
                'school_id_code' => '501055',
                'name' => 'Canidkid Integrated School',
                'school_type' => 'Public',
            ],
            99 => [
                'school_id_code' => '128381',
                'name' => 'Caragan ES',
                'school_type' => 'Public',
            ],
            100 => [
                'school_id_code' => '405333',
                'name' => 'Casa Amazing Grace School, Inc.',
                'school_type' => 'Private',
            ],
            101 => [
                'school_id_code' => '464001',
                'name' => 'Casa Amazing Grade School, Inc. - Mt. Diwata Branch',
                'school_type' => 'Private',
            ],
            102 => [
                'school_id_code' => '128431',
                'name' => 'Casoon ES',
                'school_type' => 'Public',
            ],
            103 => [
                'school_id_code' => '315801',
                'name' => 'Casoon NHS',
                'school_type' => 'Public',
            ],
            104 => [
                'school_id_code' => '128291',
                'name' => 'Ceboleda ES',
                'school_type' => 'Public',
            ],
            105 => [
                'school_id_code' => '464052',
                'name' => 'Christ Centered Christian Baptist Academy, Inc.',
                'school_type' => 'Private',
            ],
            106 => [
                'school_id_code' => '501056',
                'name' => 'Cogonon Integrated School',
                'school_type' => 'Public',
            ],
            107 => [
                'school_id_code' => '128263',
                'name' => 'Compostela Central Elementary School SPED Center',
                'school_type' => 'Public',
            ],
            108 => [
                'school_id_code' => '405321',
                'name' => 'Compostela Christian School, Inc.',
                'school_type' => 'Private',
            ],
            109 => [
                'school_id_code' => '304181',
                'name' => 'Compostela NHS',
                'school_type' => 'Public',
            ],
            110 => [
                'school_id_code' => '403727',
                'name' => 'Compostela Valley Institute of Technology (COMVIT), Inc.',
                'school_type' => 'Private',
            ],
            111 => [
                'school_id_code' => '411665',
                'name' => 'Compostela, Davao de Oro Fundamental Baptist Learning Academy, Inc.',
                'school_type' => 'Private',
            ],
            112 => [
                'school_id_code' => '405337',
                'name' => 'Comval Christian Academy',
                'school_type' => 'Private',
            ],
            113 => [
                'school_id_code' => '128346',
                'name' => 'Concepcion ES',
                'school_type' => 'Public',
            ],
            114 => [
                'school_id_code' => '128407',
                'name' => 'Concepcion ES',
                'school_type' => 'Public',
            ],
            115 => [
                'school_id_code' => '502197',
                'name' => 'Concepcion Integrated School',
                'school_type' => 'Public',
            ],
            116 => [
                'school_id_code' => '304184',
                'name' => 'Consuelo M. Valderrama NHS',
                'school_type' => 'Public',
            ],
            117 => [
                'school_id_code' => '405324',
                'name' => 'Cor Jesu Institute of Mabini, Inc.',
                'school_type' => 'Private',
            ],
            118 => [
                'school_id_code' => '204020',
                'name' => 'Corazon C. Aquino ES',
                'school_type' => 'Public',
            ],
            119 => [
                'school_id_code' => '315820',
                'name' => 'Corazon C. Aquino NHS',
                'school_type' => 'Public',
            ],
            120 => [
                'school_id_code' => '409610',
                'name' => 'Corban Learnin Center, Inc.',
                'school_type' => 'Private',
            ],
            121 => [
                'school_id_code' => '501774',
                'name' => 'Coronobe Integrated School',
                'school_type' => 'Public',
            ],
            122 => [
                'school_id_code' => '204016',
                'name' => 'Dalimdim ES',
                'school_type' => 'Public',
            ],
            123 => [
                'school_id_code' => '501652',
                'name' => 'Danggayon Integrated School',
                'school_type' => 'Public',
            ],
            124 => [
                'school_id_code' => '128293',
                'name' => 'Datu Ampunan ES',
                'school_type' => 'Public',
            ],
            125 => [
                'school_id_code' => '128294',
                'name' => 'Datu Davao ES',
                'school_type' => 'Public',
            ],
            126 => [
                'school_id_code' => '305775',
                'name' => 'Datu Davao National High School',
                'school_type' => 'Public',
            ],
            127 => [
                'school_id_code' => '128465',
                'name' => 'Dauman ES',
                'school_type' => 'Public',
            ],
            128 => [
                'school_id_code' => '128332',
                'name' => 'Del Pilar ES',
                'school_type' => 'Public',
            ],
            129 => [
                'school_id_code' => '204010',
                'name' => 'Depot ES',
                'school_type' => 'Public',
            ],
            130 => [
                'school_id_code' => '300667',
                'name' => 'Depot Ancestral Domain National High School',
                'school_type' => 'Public',
            ],
            131 => [
                'school_id_code' => '128541',
                'name' => 'Diat ES',
                'school_type' => 'Public',
            ],
            132 => [
                'school_id_code' => '204001',
                'name' => 'Diosdado Macapagal ES',
                'school_type' => 'Public',
            ],
            133 => [
                'school_id_code' => '300927',
                'name' => 'Diosdado Macapagal National High School',
                'school_type' => 'Public',
            ],
            134 => [
                'school_id_code' => '304185',
                'name' => 'Don Vicente Romualdez NHS',
                'school_type' => 'Public',
            ],
            135 => [
                'school_id_code' => '128333',
                'name' => 'Don William Gemperle ES',
                'school_type' => 'Public',
            ],
            136 => [
                'school_id_code' => '128295',
                'name' => 'Doña Josefa ES',
                'school_type' => 'Public',
            ],
            137 => [
                'school_id_code' => '128542',
                'name' => 'Doroteo De Castro ES',
                'school_type' => 'Public',
            ],
            138 => [
                'school_id_code' => '128365',
                'name' => 'Dumlan ES',
                'school_type' => 'Public',
            ],
            139 => [
                'school_id_code' => '128533',
                'name' => 'Eduardo H. Maquidato, Sr. ES',
                'school_type' => 'Public',
            ],
            140 => [
                'school_id_code' => '128296',
                'name' => 'El Katipunan ES',
                'school_type' => 'Public',
            ],
            141 => [
                'school_id_code' => '128347',
                'name' => 'Elizalde ES',
                'school_type' => 'Public',
            ],
            142 => [
                'school_id_code' => '304186',
                'name' => 'Elizalde National High School',
                'school_type' => 'Public',
            ],
            143 => [
                'school_id_code' => '137348',
                'name' => 'Florencio M. Guay Elementary School',
                'school_type' => 'Public',
            ],
            144 => [
                'school_id_code' => '411666',
                'name' => 'Fundamental Baptist Christian Academy of Mawab-Comval, Inc.',
                'school_type' => 'Private',
            ],
            145 => [
                'school_id_code' => '128264',
                'name' => 'Gabi ES',
                'school_type' => 'Public',
            ],
            146 => [
                'school_id_code' => '304187',
                'name' => 'Gabi National High School',
                'school_type' => 'Public',
            ],
            147 => [
                'school_id_code' => '128348',
                'name' => 'Gayab ES',
                'school_type' => 'Public',
            ],
            148 => [
                'school_id_code' => '315822',
                'name' => 'Golden Valley NHS',
                'school_type' => 'Public',
            ],
            149 => [
                'school_id_code' => '137317',
                'name' => 'Gov. Vicente G. Duterte Elementary School',
                'school_type' => 'Public',
            ],
            150 => [
                'school_id_code' => '128349',
                'name' => 'Gubatan ES',
                'school_type' => 'Public',
            ],
            151 => [
                'school_id_code' => '128408',
                'name' => 'Guisok ES',
                'school_type' => 'Public',
            ],
            152 => [
                'school_id_code' => '501367',
                'name' => 'Gumayan Integrated School',
                'school_type' => 'Public',
            ],
            153 => [
                'school_id_code' => '128432',
                'name' => 'Haguimitan ES',
                'school_type' => 'Public',
            ],
            154 => [
                'school_id_code' => '411657',
                'name' => 'High Ram Academe, Inc.',
                'school_type' => 'Private',
            ],
            155 => [
                'school_id_code' => '128350',
                'name' => 'Hijo ES',
                'school_type' => 'Public',
            ],
            156 => [
                'school_id_code' => '128297',
                'name' => 'Hinagtungan ES',
                'school_type' => 'Public',
            ],
            157 => [
                'school_id_code' => '128298',
                'name' => 'Ilpapa ES',
                'school_type' => 'Public',
            ],
            158 => [
                'school_id_code' => '128299',
                'name' => 'Imelda ES',
                'school_type' => 'Public',
            ],
            159 => [
                'school_id_code' => '128300',
                'name' => 'Inakayan ES',
                'school_type' => 'Public',
            ],
            160 => [
                'school_id_code' => '128433',
                'name' => 'Inambatan ES',
                'school_type' => 'Public',
            ],
            161 => [
                'school_id_code' => '137067',
                'name' => 'Inopawan Elementary School',
                'school_type' => 'Public',
            ],
            162 => [
                'school_id_code' => '405329',
                'name' => 'Institution of Northern Davao, Inc.',
                'school_type' => 'Private',
            ],
            163 => [
                'school_id_code' => '410741',
                'name' => 'Interface Technological College of Davao De Oro, Inc.',
                'school_type' => 'Private',
            ],
            164 => [
                'school_id_code' => '410743',
                'name' => 'Interface Technological College of Davao Region, Inc.',
                'school_type' => 'Private',
            ],
            165 => [
                'school_id_code' => '128366',
                'name' => 'Kaburakanan ES',
                'school_type' => 'Public',
            ],
            166 => [
                'school_id_code' => '501770',
                'name' => 'Kaligutan Integrated School',
                'school_type' => 'Public',
            ],
            167 => [
                'school_id_code' => '204004',
                'name' => 'Kaluyapi ES',
                'school_type' => 'Public',
            ],
            168 => [
                'school_id_code' => '128487',
                'name' => 'Kao ES',
                'school_type' => 'Public',
            ],
            169 => [
                'school_id_code' => '315806',
                'name' => 'Kao NHS',
                'school_type' => 'Public',
            ],
            170 => [
                'school_id_code' => '128302',
                'name' => 'Kapatagan ES',
                'school_type' => 'Public',
            ],
            171 => [
                'school_id_code' => '304188',
                'name' => 'Kapatagan National High School',
                'school_type' => 'Public',
            ],
            172 => [
                'school_id_code' => '204013',
                'name' => 'Kapoc ES',
                'school_type' => 'Public',
            ],
            173 => [
                'school_id_code' => '131139',
                'name' => 'Katipunan ES',
                'school_type' => 'Public',
            ],
            174 => [
                'school_id_code' => '128409',
                'name' => 'Katipunan ES',
                'school_type' => 'Public',
            ],
            175 => [
                'school_id_code' => '128517',
                'name' => 'Katipunan ES',
                'school_type' => 'Public',
            ],
            176 => [
                'school_id_code' => '128488',
                'name' => 'Katipunan ES',
                'school_type' => 'Public',
            ],
            177 => [
                'school_id_code' => '501769',
                'name' => 'Katipunan Integrated School',
                'school_type' => 'Public',
            ],
            178 => [
                'school_id_code' => '128303',
                'name' => 'Kibaguio ES',
                'school_type' => 'Public',
            ],
            179 => [
                'school_id_code' => '128304',
                'name' => 'Kidawa ES',
                'school_type' => 'Public',
            ],
            180 => [
                'school_id_code' => '304190',
                'name' => 'Kidawa NHS',
                'school_type' => 'Public',
            ],
            181 => [
                'school_id_code' => '128305',
                'name' => 'Kilagdeng ES',
                'school_type' => 'Public',
            ],
            182 => [
                'school_id_code' => '305774',
                'name' => 'Kilagding National High School',
                'school_type' => 'Public',
            ],
            183 => [
                'school_id_code' => '137435',
                'name' => 'Kinabuhian Elementary School',
                'school_type' => 'Public',
            ],
            184 => [
                'school_id_code' => '128544',
                'name' => 'Kingking CES',
                'school_type' => 'Public',
            ],
            185 => [
                'school_id_code' => '128351',
                'name' => 'Kinuban ES',
                'school_type' => 'Public',
            ],
            186 => [
                'school_id_code' => '128306',
                'name' => 'Kiokmay ES',
                'school_type' => 'Public',
            ],
            187 => [
                'school_id_code' => '128307',
                'name' => 'L.S. Sarmiento ES',
                'school_type' => 'Public',
            ],
            188 => [
                'school_id_code' => '411271',
                'name' => 'La Escuela Global Academy of Davao de Oro, Inc.',
                'school_type' => 'Private',
            ],
            189 => [
                'school_id_code' => '128518',
                'name' => 'La Purisima ES',
                'school_type' => 'Public',
            ],
            190 => [
                'school_id_code' => '128308',
                'name' => 'Laak CES',
                'school_type' => 'Public',
            ],
            191 => [
                'school_id_code' => '464005',
                'name' => 'Laak Institute Foundation, Inc.',
                'school_type' => 'Private',
            ],
            192 => [
                'school_id_code' => '304189',
                'name' => 'Laak NHS',
                'school_type' => 'Public',
            ],
            193 => [
                'school_id_code' => '128265',
                'name' => 'Lagab ES',
                'school_type' => 'Public',
            ],
            194 => [
                'school_id_code' => '128384',
                'name' => 'Lahi ES',
                'school_type' => 'Public',
            ],
            195 => [
                'school_id_code' => '128545',
                'name' => 'Lahi ES',
                'school_type' => 'Public',
            ],
            196 => [
                'school_id_code' => '204003',
                'name' => 'Langgam ES',
                'school_type' => 'Public',
            ],
            197 => [
                'school_id_code' => '315813',
                'name' => 'Langgawisan NHS',
                'school_type' => 'Public',
            ],
            198 => [
                'school_id_code' => '128309',
                'name' => 'Langtud ES',
                'school_type' => 'Public',
            ],
            199 => [
                'school_id_code' => '128352',
                'name' => 'Lapulapu ES',
                'school_type' => 'Public',
            ],
            200 => [
                'school_id_code' => '128546',
                'name' => 'Las Arenas ES',
                'school_type' => 'Public',
            ],
            201 => [
                'school_id_code' => '301052',
                'name' => 'Lawaan National High School',
                'school_type' => 'Public',
            ],
            202 => [
                'school_id_code' => '464031',
                'name' => 'Legacy College of Compostela, Inc.',
                'school_type' => 'Private',
            ],
            203 => [
                'school_id_code' => '405325',
                'name' => 'Letran de Davao of Maco, Inc.',
                'school_type' => 'Private',
            ],
            204 => [
                'school_id_code' => '128489',
                'name' => 'Libasan ES',
                'school_type' => 'Public',
            ],
            205 => [
                'school_id_code' => '301294',
                'name' => 'Libaylibay NHS',
                'school_type' => 'Public',
            ],
            206 => [
                'school_id_code' => '128334',
                'name' => 'Libudon ES',
                'school_type' => 'Public',
            ],
            207 => [
                'school_id_code' => '128310',
                'name' => 'Libuton ES',
                'school_type' => 'Public',
            ],
            208 => [
                'school_id_code' => '128368',
                'name' => 'Limbo ES',
                'school_type' => 'Public',
            ],
            209 => [
                'school_id_code' => '204022',
                'name' => 'Limot ES',
                'school_type' => 'Public',
            ],
            210 => [
                'school_id_code' => '502242',
                'name' => 'Linda Integrated School',
                'school_type' => 'Public',
            ],
            211 => [
                'school_id_code' => '128547',
                'name' => 'Liniputan ES',
                'school_type' => 'Public',
            ],
            212 => [
                'school_id_code' => '128466',
                'name' => 'Linoan ES',
                'school_type' => 'Public',
            ],
            213 => [
                'school_id_code' => '128434',
                'name' => 'Liwanag ES',
                'school_type' => 'Public',
            ],
            214 => [
                'school_id_code' => '501779',
                'name' => 'Longanapan Integrated School',
                'school_type' => 'Public',
            ],
            215 => [
                'school_id_code' => '128410',
                'name' => 'Lonolono ES',
                'school_type' => 'Public',
            ],
            216 => [
                'school_id_code' => '304191',
                'name' => 'Lorenzo S. Sarmiento Sr. NHS',
                'school_type' => 'Public',
            ],
            217 => [
                'school_id_code' => '128312',
                'name' => 'Lower Ampawid ES',
                'school_type' => 'Public',
            ],
            218 => [
                'school_id_code' => '204008',
                'name' => 'Lower Panansalan ES',
                'school_type' => 'Public',
            ],
            219 => [
                'school_id_code' => '204006',
                'name' => 'Lumatab ES',
                'school_type' => 'Public',
            ],
            220 => [
                'school_id_code' => '128467',
                'name' => 'Mabanda ES',
                'school_type' => 'Public',
            ],
            221 => [
                'school_id_code' => '128335',
                'name' => 'Mabini CES',
                'school_type' => 'Public',
            ],
            222 => [
                'school_id_code' => '304192',
                'name' => 'Mabini NHS',
                'school_type' => 'Public',
            ],
            223 => [
                'school_id_code' => '128387',
                'name' => 'Mabugnao ES',
                'school_type' => 'Public',
            ],
            224 => [
                'school_id_code' => '128313',
                'name' => 'Mabuhay ES',
                'school_type' => 'Public',
            ],
            225 => [
                'school_id_code' => '128435',
                'name' => 'Mabuhay ES',
                'school_type' => 'Public',
            ],
            226 => [
                'school_id_code' => '301358',
                'name' => 'Mabuhay National High School',
                'school_type' => 'Public',
            ],
            227 => [
                'school_id_code' => '128353',
                'name' => 'Maco CES',
                'school_type' => 'Public',
            ],
            228 => [
                'school_id_code' => '464011',
                'name' => 'Maco Christian Learning Center',
                'school_type' => 'Private',
            ],
            229 => [
                'school_id_code' => '128369',
                'name' => 'Maco Heights CES',
                'school_type' => 'Public',
            ],
            230 => [
                'school_id_code' => '128436',
                'name' => 'Macopa ES',
                'school_type' => 'Public',
            ],
            231 => [
                'school_id_code' => '128490',
                'name' => 'Magading ES',
                'school_type' => 'Public',
            ],
            232 => [
                'school_id_code' => '128354',
                'name' => 'Magangit ES',
                'school_type' => 'Public',
            ],
            233 => [
                'school_id_code' => '501057',
                'name' => 'Magangit Integrated School',
                'school_type' => 'Public',
            ],
            234 => [
                'school_id_code' => '128385',
                'name' => 'Magcagong ES',
                'school_type' => 'Public',
            ],
            235 => [
                'school_id_code' => '305778',
                'name' => 'Magcagong National High School',
                'school_type' => 'Public',
            ],
            236 => [
                'school_id_code' => '128548',
                'name' => 'Magnaga Elementary School',
                'school_type' => 'Public',
            ],
            237 => [
                'school_id_code' => '315807',
                'name' => 'Magnaga National High School',
                'school_type' => 'Public',
            ],
            238 => [
                'school_id_code' => '128503',
                'name' => 'Magsaysay ES',
                'school_type' => 'Public',
            ],
            239 => [
                'school_id_code' => '128520',
                'name' => 'Magsaysay ES',
                'school_type' => 'Public',
            ],
            240 => [
                'school_id_code' => '315821',
                'name' => 'Magsaysay NHS',
                'school_type' => 'Public',
            ],
            241 => [
                'school_id_code' => '128468',
                'name' => 'Magtaya ES',
                'school_type' => 'Public',
            ],
            242 => [
                'school_id_code' => '128411',
                'name' => 'Mahayag ES',
                'school_type' => 'Public',
            ],
            243 => [
                'school_id_code' => '128386',
                'name' => 'Mahayahay ES',
                'school_type' => 'Public',
            ],
            244 => [
                'school_id_code' => '128491',
                'name' => 'Mainit ES',
                'school_type' => 'Public',
            ],
            245 => [
                'school_id_code' => '304194',
                'name' => 'Mainit NHS',
                'school_type' => 'Public',
            ],
            246 => [
                'school_id_code' => '305773',
                'name' => 'Major Angel V. Fajardo National High School',
                'school_type' => 'Public',
            ],
            247 => [
                'school_id_code' => '128314',
                'name' => 'Makopa ES',
                'school_type' => 'Public',
            ],
            248 => [
                'school_id_code' => '128315',
                'name' => 'Malinao ES',
                'school_type' => 'Public',
            ],
            249 => [
                'school_id_code' => '128412',
                'name' => 'Malinawon ES',
                'school_type' => 'Public',
            ],
            250 => [
                'school_id_code' => '128336',
                'name' => 'Mambatang ES',
                'school_type' => 'Public',
            ],
            251 => [
                'school_id_code' => '128266',
                'name' => 'Mambusao ES',
                'school_type' => 'Public',
            ],
            252 => [
                'school_id_code' => '128437',
                'name' => 'Mamonga ES',
                'school_type' => 'Public',
            ],
            253 => [
                'school_id_code' => '128337',
                'name' => 'Manasa ES',
                'school_type' => 'Public',
            ],
            254 => [
                'school_id_code' => '128492',
                'name' => 'Manat CES',
                'school_type' => 'Public',
            ],
            255 => [
                'school_id_code' => '304195',
                'name' => 'Manat NHS',
                'school_type' => 'Public',
            ],
            256 => [
                'school_id_code' => '128267',
                'name' => 'Mangayon ES',
                'school_type' => 'Public',
            ],
            257 => [
                'school_id_code' => '304182',
                'name' => 'Mangayon NHS',
                'school_type' => 'Public',
            ],
            258 => [
                'school_id_code' => '128316',
                'name' => 'Mangloy ES',
                'school_type' => 'Public',
            ],
            259 => [
                'school_id_code' => '306038',
                'name' => 'Mangloy National High School',
                'school_type' => 'Public',
            ],
            260 => [
                'school_id_code' => '128370',
                'name' => 'Manipongol ES',
                'school_type' => 'Public',
            ],
            261 => [
                'school_id_code' => '137005',
                'name' => 'Mansinao-An Elementary School',
                'school_type' => 'Public',
            ],
            262 => [
                'school_id_code' => '500533',
                'name' => 'Manurigao Integrated School',
                'school_type' => 'Public',
            ],
            263 => [
                'school_id_code' => '128371',
                'name' => 'Mapaang ES',
                'school_type' => 'Public',
            ],
            264 => [
                'school_id_code' => '502912',
                'name' => 'Mapaca Integrated School',
                'school_type' => 'Public',
            ],
            265 => [
                'school_id_code' => '128269',
                'name' => 'Maparat ES',
                'school_type' => 'Public',
            ],
            266 => [
                'school_id_code' => '304196',
                'name' => 'Maparat NHS',
                'school_type' => 'Public',
            ],
            267 => [
                'school_id_code' => '128522',
                'name' => 'Mapaso ES',
                'school_type' => 'Public',
            ],
            268 => [
                'school_id_code' => '128388',
                'name' => 'Mapawa Central Elementary School',
                'school_type' => 'Public',
            ],
            269 => [
                'school_id_code' => '304197',
                'name' => 'Mapawa NHS',
                'school_type' => 'Public',
            ],
            270 => [
                'school_id_code' => '128389',
                'name' => 'Maragusan CES',
                'school_type' => 'Public',
            ],
            271 => [
                'school_id_code' => '304198',
                'name' => 'Maragusan NHS',
                'school_type' => 'Public',
            ],
            272 => [
                'school_id_code' => '464022',
                'name' => 'Maragusan Seventh Day Adventist Learning Center, Inc.',
                'school_type' => 'Private',
            ],
            273 => [
                'school_id_code' => '464054',
                'name' => 'Maranatha Bible Baptist Academy of Compostela Valley, Inc.',
                'school_type' => 'Private',
            ],
            274 => [
                'school_id_code' => '128338',
                'name' => 'Masicareg ES',
                'school_type' => 'Public',
            ],
            275 => [
                'school_id_code' => '128493',
                'name' => 'Matagdungan ES',
                'school_type' => 'Public',
            ],
            276 => [
                'school_id_code' => '128438',
                'name' => 'Matangad ES',
                'school_type' => 'Public',
            ],
            277 => [
                'school_id_code' => '128549',
                'name' => 'Matiao ES',
                'school_type' => 'Public',
            ],
            278 => [
                'school_id_code' => '128494',
                'name' => 'Matilo ES',
                'school_type' => 'Public',
            ],
            279 => [
                'school_id_code' => '128550',
                'name' => 'Maubog ES',
                'school_type' => 'Public',
            ],
            280 => [
                'school_id_code' => '204021',
                'name' => 'Maugat ES',
                'school_type' => 'Public',
            ],
            281 => [
                'school_id_code' => '128390',
                'name' => 'Mauswagon ES',
                'school_type' => 'Public',
            ],
            282 => [
                'school_id_code' => '128413',
                'name' => 'Mawab Central Elementary School SPED Center',
                'school_type' => 'Public',
            ],
            283 => [
                'school_id_code' => '128469',
                'name' => 'Mayaon ES',
                'school_type' => 'Public',
            ],
            284 => [
                'school_id_code' => '304204',
                'name' => 'Mayaon NHS',
                'school_type' => 'Public',
            ],
            285 => [
                'school_id_code' => '128470',
                'name' => 'Mayobe ES',
                'school_type' => 'Public',
            ],
            286 => [
                'school_id_code' => '128317',
                'name' => 'Melale ES',
                'school_type' => 'Public',
            ],
            287 => [
                'school_id_code' => '315804',
                'name' => 'Melale NHS',
                'school_type' => 'Public',
            ],
            288 => [
                'school_id_code' => '128504',
                'name' => 'Mipangi ES',
                'school_type' => 'Public',
            ],
            289 => [
                'school_id_code' => '128439',
                'name' => 'Monkayo CES',
                'school_type' => 'Public',
            ],
            290 => [
                'school_id_code' => '304199',
                'name' => 'Monkayo NHS',
                'school_type' => 'Public',
            ],
            291 => [
                'school_id_code' => '128471',
                'name' => 'Montevista CES',
                'school_type' => 'Public',
            ],
            292 => [
                'school_id_code' => '304202',
                'name' => 'Montevista NHS',
                'school_type' => 'Public',
            ],
            293 => [
                'school_id_code' => '304203',
                'name' => 'Montevista Stand Alone Senior High School',
                'school_type' => 'Public',
            ],
            294 => [
                'school_id_code' => '128441',
                'name' => 'Moria ES',
                'school_type' => 'Public',
            ],
            295 => [
                'school_id_code' => '128442',
                'name' => 'Mt. Diwata ES',
                'school_type' => 'Public',
            ],
            296 => [
                'school_id_code' => '128444',
                'name' => 'Naboc ES',
                'school_type' => 'Public',
            ],
            297 => [
                'school_id_code' => '464029',
                'name' => 'Nabunturan Central Baptist Academy',
                'school_type' => 'Private',
            ],
            298 => [
                'school_id_code' => '128505',
                'name' => 'Nabunturan Central Elementary School SPED Center',
                'school_type' => 'Public',
            ],
            299 => [
                'school_id_code' => '304205',
                'name' => 'Nabunturan NCHS',
                'school_type' => 'Public',
            ],
            300 => [
                'school_id_code' => '128318',
                'name' => 'Naga ES',
                'school_type' => 'Public',
            ],
            301 => [
                'school_id_code' => '128551',
                'name' => 'Nagas ES',
                'school_type' => 'Public',
            ],
            302 => [
                'school_id_code' => '128552',
                'name' => 'Napnapan ES',
                'school_type' => 'Public',
            ],
            303 => [
                'school_id_code' => '315816',
                'name' => 'Napnapan NHS',
                'school_type' => 'Public',
            ],
            304 => [
                'school_id_code' => '411310',
                'name' => 'NCBA Grade School of Davao de Oro, Inc.',
                'school_type' => 'Private',
            ],
            305 => [
                'school_id_code' => '409219',
                'name' => 'Nestor Fausta Memorial College',
                'school_type' => 'Private',
            ],
            306 => [
                'school_id_code' => '128391',
                'name' => 'New Albay ES',
                'school_type' => 'Public',
            ],
            307 => [
                'school_id_code' => '315815',
                'name' => 'New Albay NHS',
                'school_type' => 'Public',
            ],
            308 => [
                'school_id_code' => '128270',
                'name' => 'New Alegria ES',
                'school_type' => 'Public',
            ],
            309 => [
                'school_id_code' => '128355',
                'name' => 'New Asturias ES',
                'school_type' => 'Public',
            ],
            310 => [
                'school_id_code' => '128356',
                'name' => 'New Barili ES',
                'school_type' => 'Public',
            ],
            311 => [
                'school_id_code' => '304206',
                'name' => 'New Bataan NHS',
                'school_type' => 'Public',
            ],
            312 => [
                'school_id_code' => '128319',
                'name' => 'New Bethlehem ES',
                'school_type' => 'Public',
            ],
            313 => [
                'school_id_code' => '128472',
                'name' => 'New Calape ES',
                'school_type' => 'Public',
            ],
            314 => [
                'school_id_code' => '128473',
                'name' => 'New Dalaguete ES',
                'school_type' => 'Public',
            ],
            315 => [
                'school_id_code' => '128495',
                'name' => 'New Dauis ES',
                'school_type' => 'Public',
            ],
            316 => [
                'school_id_code' => '128445',
                'name' => 'New Kapatagan ES',
                'school_type' => 'Public',
            ],
            317 => [
                'school_id_code' => '315823',
                'name' => 'New Kapatagan NHS',
                'school_type' => 'Public',
            ],
            318 => [
                'school_id_code' => '128392',
                'name' => 'New Katipunan ES',
                'school_type' => 'Public',
            ],
            319 => [
                'school_id_code' => '128373',
                'name' => 'New Leyte ES',
                'school_type' => 'Public',
            ],
            320 => [
                'school_id_code' => '315811',
                'name' => 'New Leyte NHS',
                'school_type' => 'Public',
            ],
            321 => [
                'school_id_code' => '128393',
                'name' => 'New Manay ES',
                'school_type' => 'Public',
            ],
            322 => [
                'school_id_code' => '128394',
                'name' => 'New Negros ES',
                'school_type' => 'Public',
            ],
            323 => [
                'school_id_code' => '501771',
                'name' => 'New Panay Integrated School',
                'school_type' => 'Public',
            ],
            324 => [
                'school_id_code' => '128506',
                'name' => 'New Sibonga ES',
                'school_type' => 'Public',
            ],
            325 => [
                'school_id_code' => '315818',
                'name' => 'New Sibonga NHS',
                'school_type' => 'Public',
            ],
            326 => [
                'school_id_code' => '128357',
                'name' => 'New Visayas ES',
                'school_type' => 'Public',
            ],
            327 => [
                'school_id_code' => '128474',
                'name' => 'New Visayas ES',
                'school_type' => 'Public',
            ],
            328 => [
                'school_id_code' => '305772',
                'name' => 'New Visayas National High School',
                'school_type' => 'Public',
            ],
            329 => [
                'school_id_code' => '128271',
                'name' => 'Ngan ES',
                'school_type' => 'Public',
            ],
            330 => [
                'school_id_code' => '405339',
                'name' => 'Nueva Estrella Santiago Dulos Academy, Inc.',
                'school_type' => 'Private',
            ],
            331 => [
                'school_id_code' => '128414',
                'name' => 'Nueva Visayas ES',
                'school_type' => 'Public',
            ],
            332 => [
                'school_id_code' => '128415',
                'name' => 'Nuevo Iloco ES',
                'school_type' => 'Public',
            ],
            333 => [
                'school_id_code' => '315802',
                'name' => 'Nuevo Iloco National High School',
                'school_type' => 'Public',
            ],
            334 => [
                'school_id_code' => '128496',
                'name' => 'Ogao ES',
                'school_type' => 'Public',
            ],
            335 => [
                'school_id_code' => '500455',
                'name' => 'Olaycon Integrated School',
                'school_type' => 'Public',
            ],
            336 => [
                'school_id_code' => '128272',
                'name' => 'Osmeña ES',
                'school_type' => 'Public',
            ],
            337 => [
                'school_id_code' => '128553',
                'name' => 'P. Fuentes Sr. ES',
                'school_type' => 'Public',
            ],
            338 => [
                'school_id_code' => '204011',
                'name' => 'Paco ES',
                'school_type' => 'Public',
            ],
            339 => [
                'school_id_code' => '128523',
                'name' => 'Pagsabangan ES',
                'school_type' => 'Public',
            ],
            340 => [
                'school_id_code' => '128524',
                'name' => 'Pagsilaan ES',
                'school_type' => 'Public',
            ],
            341 => [
                'school_id_code' => '128396',
                'name' => 'Paloc ES',
                'school_type' => 'Public',
            ],
            342 => [
                'school_id_code' => '304207',
                'name' => 'Paloc NHS',
                'school_type' => 'Public',
            ],
            343 => [
                'school_id_code' => '501772',
                'name' => 'Pamintaran Integrated School',
                'school_type' => 'Public',
            ],
            344 => [
                'school_id_code' => '128525',
                'name' => 'Panag ES',
                'school_type' => 'Public',
            ],
            345 => [
                'school_id_code' => '128339',
                'name' => 'Panamin ES',
                'school_type' => 'Public',
            ],
            346 => [
                'school_id_code' => '128320',
                'name' => 'Panamoren ES',
                'school_type' => 'Public',
            ],
            347 => [
                'school_id_code' => '128374',
                'name' => 'Panangan ES',
                'school_type' => 'Public',
            ],
            348 => [
                'school_id_code' => '500456',
                'name' => 'Panansalan Integrated School',
                'school_type' => 'Public',
            ],
            349 => [
                'school_id_code' => '128554',
                'name' => 'Panganason ES',
                'school_type' => 'Public',
            ],
            350 => [
                'school_id_code' => '128358',
                'name' => 'Pangi ES',
                'school_type' => 'Public',
            ],
            351 => [
                'school_id_code' => '301526',
                'name' => 'Pangi National High School',
                'school_type' => 'Public',
            ],
            352 => [
                'school_id_code' => '128497',
                'name' => 'Pangutosan ES',
                'school_type' => 'Public',
            ],
            353 => [
                'school_id_code' => '128359',
                'name' => 'Panibasan ES',
                'school_type' => 'Public',
            ],
            354 => [
                'school_id_code' => '301585',
                'name' => 'Panibasan National High School',
                'school_type' => 'Public',
            ],
            355 => [
                'school_id_code' => '128375',
                'name' => 'Panoraon ES',
                'school_type' => 'Public',
            ],
            356 => [
                'school_id_code' => '464028',
                'name' => 'Pantukan Baptist Christian School, Inc.',
                'school_type' => 'Private',
            ],
            357 => [
                'school_id_code' => '128555',
                'name' => 'Pantukan ES',
                'school_type' => 'Public',
            ],
            358 => [
                'school_id_code' => '304208',
                'name' => 'Pantukan NHS',
                'school_type' => 'Public',
            ],
            359 => [
                'school_id_code' => '501768',
                'name' => 'Parasanon Integrated School',
                'school_type' => 'Public',
            ],
            360 => [
                'school_id_code' => '128447',
                'name' => 'Pasian ES',
                'school_type' => 'Public',
            ],
            361 => [
                'school_id_code' => '304201',
                'name' => 'Pasian National High School',
                'school_type' => 'Public',
            ],
            362 => [
                'school_id_code' => '128556',
                'name' => 'Piasusuan ES',
                'school_type' => 'Public',
            ],
            363 => [
                'school_id_code' => '128448',
                'name' => 'Pilar ES',
                'school_type' => 'Public',
            ],
            364 => [
                'school_id_code' => '128340',
                'name' => 'Pindasan ES',
                'school_type' => 'Public',
            ],
            365 => [
                'school_id_code' => '315805',
                'name' => 'Pindasan NHS',
                'school_type' => 'Public',
            ],
            366 => [
                'school_id_code' => '501366',
                'name' => 'Pongpong Integrated School',
                'school_type' => 'Public',
            ],
            367 => [
                'school_id_code' => '128475',
                'name' => 'Prosperidad ES',
                'school_type' => 'Public',
            ],
            368 => [
                'school_id_code' => '128476',
                'name' => 'Prosperidad Tribal ES',
                'school_type' => 'Public',
            ],
            369 => [
                'school_id_code' => '128557',
                'name' => 'Pulang Lupa ES',
                'school_type' => 'Public',
            ],
            370 => [
                'school_id_code' => '136925',
                'name' => 'Puting Bato Elementary School',
                'school_type' => 'Public',
            ],
            371 => [
                'school_id_code' => '128449',
                'name' => 'Rizal Memorial ES',
                'school_type' => 'Public',
            ],
            372 => [
                'school_id_code' => '128321',
                'name' => 'Sabud ES',
                'school_type' => 'Public',
            ],
            373 => [
                'school_id_code' => '464032',
                'name' => 'Sacred Angel Learning center of Pantukan, Inc.',
                'school_type' => 'Private',
            ],
            374 => [
                'school_id_code' => '405327',
                'name' => 'Saint Vincent Academy of Maragusan, Inc.',
                'school_type' => 'Private',
            ],
            375 => [
                'school_id_code' => '128450',
                'name' => 'Salvacion ES',
                'school_type' => 'Public',
            ],
            376 => [
                'school_id_code' => '502371',
                'name' => 'Salvacion Integrated School',
                'school_type' => 'Public',
            ],
            377 => [
                'school_id_code' => '128477',
                'name' => 'Sambayon ES',
                'school_type' => 'Public',
            ],
            378 => [
                'school_id_code' => '128322',
                'name' => 'San Antonio ES',
                'school_type' => 'Public',
            ],
            379 => [
                'school_id_code' => '128341',
                'name' => 'San Antonio ES',
                'school_type' => 'Public',
            ],
            380 => [
                'school_id_code' => '315812',
                'name' => 'San Antonio NHS',
                'school_type' => 'Public',
            ],
            381 => [
                'school_id_code' => '128527',
                'name' => 'San Isidro ES',
                'school_type' => 'Public',
            ],
            382 => [
                'school_id_code' => '501773',
                'name' => 'San Isidro Integrated School',
                'school_type' => 'Public',
            ],
            383 => [
                'school_id_code' => '501776',
                'name' => 'San Isidro Integrated School',
                'school_type' => 'Public',
            ],
            384 => [
                'school_id_code' => '128274',
                'name' => 'San Jose ES',
                'school_type' => 'Public',
            ],
            385 => [
                'school_id_code' => '128452',
                'name' => 'San Jose ES',
                'school_type' => 'Public',
            ],
            386 => [
                'school_id_code' => '128483',
                'name' => 'San Juan ES',
                'school_type' => 'Public',
            ],
            387 => [
                'school_id_code' => '128275',
                'name' => 'San Miguel ES',
                'school_type' => 'Public',
            ],
            388 => [
                'school_id_code' => '304183',
                'name' => 'San Miguel NHS',
                'school_type' => 'Public',
            ],
            389 => [
                'school_id_code' => '137105',
                'name' => 'San Roque Elementary School',
                'school_type' => 'Public',
            ],
            390 => [
                'school_id_code' => '128509',
                'name' => 'San Roque ES',
                'school_type' => 'Public',
            ],
            391 => [
                'school_id_code' => '128528',
                'name' => 'San Roque ES',
                'school_type' => 'Public',
            ],
            392 => [
                'school_id_code' => '128498',
                'name' => 'San Vicente ES',
                'school_type' => 'Public',
            ],
            393 => [
                'school_id_code' => '501058',
                'name' => 'San Vicente Integrated School',
                'school_type' => 'Public',
            ],
            394 => [
                'school_id_code' => '128417',
                'name' => 'San Vicente PS',
                'school_type' => 'Public',
            ],
            395 => [
                'school_id_code' => '128360',
                'name' => 'Sangab ES',
                'school_type' => 'Public',
            ],
            396 => [
                'school_id_code' => '502372',
                'name' => 'Saosao Integrated School',
                'school_type' => 'Public',
            ],
            397 => [
                'school_id_code' => '204009',
                'name' => 'Sapawan ES',
                'school_type' => 'Public',
            ],
            398 => [
                'school_id_code' => '128399',
                'name' => 'Saranga ES',
                'school_type' => 'Public',
            ],
            399 => [
                'school_id_code' => '128558',
                'name' => 'Sarog ES',
                'school_type' => 'Public',
            ],
            400 => [
                'school_id_code' => '204005',
                'name' => 'Sasa ES',
                'school_type' => 'Public',
            ],
            401 => [
                'school_id_code' => '128419',
                'name' => 'Sawangan ES',
                'school_type' => 'Public',
            ],
            402 => [
                'school_id_code' => '128529',
                'name' => 'Simsimen ES',
                'school_type' => 'Public',
            ],
            403 => [
                'school_id_code' => '128361',
                'name' => 'Singanan ES',
                'school_type' => 'Public',
            ],
            404 => [
                'school_id_code' => '128276',
                'name' => 'Siocon ES',
                'school_type' => 'Public',
            ],
            405 => [
                'school_id_code' => '304210',
                'name' => 'Siocon NHS',
                'school_type' => 'Public',
            ],
            406 => [
                'school_id_code' => '128323',
                'name' => 'Sisimon ES',
                'school_type' => 'Public',
            ],
            407 => [
                'school_id_code' => '128324',
                'name' => 'Sta. Emilia ES',
                'school_type' => 'Public',
            ],
            408 => [
                'school_id_code' => '128499',
                'name' => 'Sta. Maria ES',
                'school_type' => 'Public',
            ],
            409 => [
                'school_id_code' => '128559',
                'name' => 'Sta. Teresa ES',
                'school_type' => 'Public',
            ],
            410 => [
                'school_id_code' => '411658',
                'name' => 'Steward Learning Academy, Inc.',
                'school_type' => 'Private',
            ],
            411 => [
                'school_id_code' => '128453',
                'name' => 'Sugod ES',
                'school_type' => 'Public',
            ],
            412 => [
                'school_id_code' => '128277',
                'name' => 'T.H. Valderrama ES',
                'school_type' => 'Public',
            ],
            413 => [
                'school_id_code' => '128420',
                'name' => 'Tabontabon ES',
                'school_type' => 'Public',
            ],
            414 => [
                'school_id_code' => '204012',
                'name' => 'Tadya ES',
                'school_type' => 'Public',
            ],
            415 => [
                'school_id_code' => '204002',
                'name' => 'Tagaytay ES',
                'school_type' => 'Public',
            ],
            416 => [
                'school_id_code' => '128343',
                'name' => 'Tagbalabao ES',
                'school_type' => 'Public',
            ],
            417 => [
                'school_id_code' => '128376',
                'name' => 'Tagbaros ES',
                'school_type' => 'Public',
            ],
            418 => [
                'school_id_code' => '128560',
                'name' => 'Tagdangua ES',
                'school_type' => 'Public',
            ],
            419 => [
                'school_id_code' => '128362',
                'name' => 'Taglawig ES',
                'school_type' => 'Public',
            ],
            420 => [
                'school_id_code' => '128500',
                'name' => 'Tagnocon ES',
                'school_type' => 'Public',
            ],
            421 => [
                'school_id_code' => '128561',
                'name' => 'Tagugpo ES',
                'school_type' => 'Public',
            ],
            422 => [
                'school_id_code' => '304209',
                'name' => 'Tagugpo NHS',
                'school_type' => 'Public',
            ],
            423 => [
                'school_id_code' => '405322',
                'name' => 'Tagum SDA Elementary School, Inc. - Compostela Branch',
                'school_type' => 'Private',
            ],
            424 => [
                'school_id_code' => '405323',
                'name' => 'Tagum SDA Elementary School, Inc. - Laak Branch',
                'school_type' => 'Private',
            ],
            425 => [
                'school_id_code' => '464014',
                'name' => 'Tagum SDA Elementary School, Inc. - Pantukan Branch',
                'school_type' => 'Private',
            ],
            426 => [
                'school_id_code' => '128400',
                'name' => 'Talian ES',
                'school_type' => 'Public',
            ],
            427 => [
                'school_id_code' => '304211',
                'name' => 'Tambongon NHS',
                'school_type' => 'Public',
            ],
            428 => [
                'school_id_code' => '128278',
                'name' => 'Tamia ES',
                'school_type' => 'Public',
            ],
            429 => [
                'school_id_code' => '501780',
                'name' => 'Tan-Awan Integrated School',
                'school_type' => 'Public',
            ],
            430 => [
                'school_id_code' => '501777',
                'name' => 'Tandawan Integrated School',
                'school_type' => 'Public',
            ],
            431 => [
                'school_id_code' => '128401',
                'name' => 'Tandik ES',
                'school_type' => 'Public',
            ],
            432 => [
                'school_id_code' => '128479',
                'name' => 'Tapia ES',
                'school_type' => 'Public',
            ],
            433 => [
                'school_id_code' => '128562',
                'name' => 'Tapis ES',
                'school_type' => 'Public',
            ],
            434 => [
                'school_id_code' => '128531',
                'name' => 'Taytayan ES',
                'school_type' => 'Public',
            ],
            435 => [
                'school_id_code' => '128377',
                'name' => 'Teresa ES',
                'school_type' => 'Public',
            ],
            436 => [
                'school_id_code' => '305637',
                'name' => 'Teresa National High School',
                'school_type' => 'Public',
            ],
            437 => [
                'school_id_code' => '464002',
                'name' => 'The Kiddie Math Science Grade School of Comval, Inc.',
                'school_type' => 'Private',
            ],
            438 => [
                'school_id_code' => '128563',
                'name' => 'Tibagon ES',
                'school_type' => 'Public',
            ],
            439 => [
                'school_id_code' => '128325',
                'name' => 'Tigasa ES',
                'school_type' => 'Public',
            ],
            440 => [
                'school_id_code' => '501775',
                'name' => 'Tigbao Integrated School',
                'school_type' => 'Public',
            ],
            441 => [
                'school_id_code' => '128454',
                'name' => 'Totoy ES',
                'school_type' => 'Public',
            ],
            442 => [
                'school_id_code' => '128455',
                'name' => 'Tubo-Tubo ES',
                'school_type' => 'Public',
            ],
            443 => [
                'school_id_code' => '304212',
                'name' => 'Tubo-Tubo NHS',
                'school_type' => 'Public',
            ],
            444 => [
                'school_id_code' => '128422',
                'name' => 'Tuboran ES',
                'school_type' => 'Public',
            ],
            445 => [
                'school_id_code' => '315803',
                'name' => 'Tuboran National High School',
                'school_type' => 'Public',
            ],
            446 => [
                'school_id_code' => '128403',
                'name' => 'Tuburan ES',
                'school_type' => 'Public',
            ],
            447 => [
                'school_id_code' => '128456',
                'name' => 'Tuburan ES',
                'school_type' => 'Public',
            ],
            448 => [
                'school_id_code' => '137125',
                'name' => 'Tugas Elementary School',
                'school_type' => 'Public',
            ],
            449 => [
                'school_id_code' => '128564',
                'name' => 'Tugop ES',
                'school_type' => 'Public',
            ],
            450 => [
                'school_id_code' => '204014',
                'name' => 'Tugunan ES',
                'school_type' => 'Public',
            ],
            451 => [
                'school_id_code' => '128326',
                'name' => 'Tuk-an ES',
                'school_type' => 'Public',
            ],
            452 => [
                'school_id_code' => '128404',
                'name' => 'Tupas ES',
                'school_type' => 'Public',
            ],
            453 => [
                'school_id_code' => '304213',
                'name' => 'Tupaz NHS',
                'school_type' => 'Public',
            ],
            454 => [
                'school_id_code' => '102234',
                'name' => 'Uduan ES',
                'school_type' => 'Public',
            ],
            455 => [
                'school_id_code' => '128457',
                'name' => 'Ulip ES',
                'school_type' => 'Public',
            ],
            456 => [
                'school_id_code' => '315808',
                'name' => 'Ulip National High School',
                'school_type' => 'Public',
            ],
            457 => [
                'school_id_code' => '128443',
                'name' => 'Union Central Elementary School',
                'school_type' => 'Public',
            ],
            458 => [
                'school_id_code' => '315809',
                'name' => 'Union National High School - Mt. Diwata High School Annex',
                'school_type' => 'Public',
            ],
            459 => [
                'school_id_code' => '304214',
                'name' => 'Union NHS',
                'school_type' => 'Public',
            ],
            460 => [
                'school_id_code' => '204015',
                'name' => 'Upper Camili ES',
                'school_type' => 'Public',
            ],
        ];
    }
}
