/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD's customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Kevin Hottinger <kevin.hottinger@totaralearning.com>
 * @module tui
 */

import { langString } from './i18n';

/**
 * Return array of available time zone keys with a corresponding language
 * string.
 *
 * @returns {Array<{id: string, label: *}>}
 */
export function getTimeZoneKeyStrings() {
  const zones = [];
  getCompressedZoneList().forEach(group => {
    group.entries.forEach(entry => {
      const isStr = typeof entry === 'string';
      zones.push({
        id: group.prefix + (isStr ? entry : entry.id),
        label: langString(
          isStr ? (group.prefix + entry).toLowerCase() : entry.langId,
          'timezones'
        ),
      });
    });
  });
  return zones;
}

/**
 * Time zone list.
 *
 * List is based of time zones from IANA database
 * (Excludes times zones without existing strings in lib/timezones)
 *
 * Excluded time zones ID's
 * Africa:
 * Africa/Juba
 *
 * America:
 * America/Argentina/Salta, America/Argentina/San_Luis, America/Atikokan
 * America/Atka, America/Bahia_Banderas, America/Blanc-Sablon, America/Buenos_Aires,
 * America/Catamarca, America/Coral_Harbour, America/Cordoba, America/Creston,
 * America/Ensenada, America/Fort_Nelson, America/Fort_Wayne, America/Indiana/Petersburg,
 * America/Indiana/Tell_City, America/Indiana/Vincennes, America/Indiana/Winamac,
 * America/Jujuy, America/Kentucky/Louisville, America/Knox_IN, America/Kralendijk,
 * America/Lower_Princes, America/Marigot, America/Matamoros, America/Mendoza,
 * America/Metlakatla, America/Moncton, America/North_Dakota/Beulah,
 * America/North_Dakota/New_Salem, America/Ojinaga, America/Porto_Acre
 * America/Punta_Arenas, America/Resolute, America/Rosario, America/Santa_Isabel,
 * America/Santarem, America/Shiprock, America/Sitka, America/St_Barthelemy,
 * America/Virgin
 *
 * Antarctica:
 * Antarctica/Macquarie, Antarctica/South_Pole, Antarctica/Troll
 *
 * Arctic:
 * Arctic/Longyearbyen
 *
 * Asia:
 * Asia/Ashkhabad, Asia/Atyrau, Asia/Barnau, Asia/Chita, Asia/Chungking, Asia/Dacca,
 * Asia/Famagusta, Asia/Hebron, Asia/Ho_Chi_Minh, Asia/Istanbul, Asia/Jakarta,
 * Asia/Kathmandu, Asia/Khandyga, Asia/Macao, Asia/Novokuznetsk, Asia/Srednekolymsk,
 * Asia/Tel_Aviv, Asia/Thimbu, Asia/Tomsk, Asia/Ujung_Pandang, Asia/Ulan_Bator,
 * Asia/Ust-Nera, Asia/Yangon
 *
 * Atlantic:
 * Atlantic/Faroe, Atlantic/Jan_Mayen
 *
 * Australia:
 * Australia/Canberra, Australia/Currie, Australia/Eucla, Australia/Yancowinna
 *
 * Etc:
 * Etc/GMT, Etc/GMT+1, Etc/GMT+2, Etc/GMT+3, Etc/GMT+4, Etc/GMT+5, Etc/GMT+6,
 * Etc/GMT+7, Etc/GMT+8, Etc/GMT+9, Etc/GMT+10, Etc/GMT+11, Etc/GMT+12, Etc/GMT-1
 * Etc/GMT-2, Etc/GMT-3, Etc/GMT-4, Etc/GMT-5, Etc/GMT-6, Etc/GMT-7, Etc/GMT-8,
 * Etc/GMT-9, Etc/GMT-10, Etc/GMT-11, Etc/GMT-12, Etc/GMT-13, Etc/GMT-14
 *
 * Europe:
 * Europe/Astrakhan, Europe/Bratislava, Europe/Busingen, Europe/Guernsey,
 * Europe/Isle_of_Man, Europe/Jersey, Europe/Kirov, 'Europe/Ljubljana',
 * Europe/Mariehamn, Europe/Podgorica, Europe/San_Marino, Europe/Sarajevo,
 * Europe/Saratov, Europe/Skopje, Europe/Tiraspol, Europe/Ulyanovsk,
 * Europe/Vatican, Europe/Volgograd, Europe/Zagreb
 *
 * Indian:
 * Indian/Cocos
 *
 * Pacific:
 * Pacific/Bougainville, Pacific/Chuuk, Pacific/Johnston, Pacific/Pohnpei,
 * Pacific/Samoa
 *
 * @returns {array}
 */
function getCompressedZoneList() {
  return [
    {
      prefix: 'Africa/',
      entries: [
        'Abidjan',
        'Accra',
        'Addis_Ababa',
        'Algiers',
        { id: 'Asmara', langId: 'africa/asmera' },
        'Bamako',
        'Bangui',
        'Banjul',
        'Bissau',
        'Blantyre',
        'Brazzaville',
        'Bujumbura',
        'Cairo',
        'Casablanca',
        'Ceuta',
        'Conakry',
        'Dakar',
        'Dar_es_Salaam',
        'Djibouti',
        'Douala',
        'El_Aaiun',
        'Freetown',
        'Gaborone',
        'Harare',
        'Johannesburg',
        'Kampala',
        'Khartoum',
        'Kigali',
        'Kinshasa',
        'Lagos',
        'Libreville',
        'Lome',
        'Luanda',
        'Lubumbashi',
        'Lusaka',
        'Malabo',
        'Maputo',
        'Maseru',
        'Mbabane',
        'Mogadishu',
        'Monrovia',
        'Nairobi',
        'Ndjamena',
        'Niamey',
        'Nouakchott',
        'Ouagadougou',
        'Porto-Novo',
        'Sao_Tome',
        'Timbuktu',
        'Tripoli',
        'Tunis',
        'Windhoek',
      ],
    },
    {
      prefix: 'America/',
      entries: [
        'Adak',
        'Anchorage',
        'Anguilla',
        'Antigua',
        'Araguaina',
        'Argentina/Buenos_Aires',
        'Argentina/Catamarca',
        'Argentina/ComodRivadavia',
        'Argentina/Cordoba',
        'Argentina/Jujuy',
        'Argentina/La_Rioja',
        'Argentina/Mendoza',
        'Argentina/Rio_Gallegos',
        'Argentina/San_Juan',
        'Argentina/Tucuman',
        'Argentina/Ushuaia',
        'Aruba',
        'Asuncion',
        'Bahia',
        'Barbados',
        'Belem',
        'Belize',
        'Boa_Vista',
        'Bogota',
        'Boise',
        'Cambridge_Bay',
        'Campo_Grande',
        'Cancun',
        'Caracas',
        'Cayenne',
        'Cayman',
        'Chicago',
        'Chihuahua',
        'Costa_Rica',
        'Cuiaba',
        'Curacao',
        'Danmarkshavn',
        'Dawson',
        'Dawson_Creek',
        'Denver',
        'Detroit',
        'Dominica',
        'Edmonton',
        'Eirunepe',
        'El_Salvador',
        'Fortaleza',
        'Glace_Bay',
        'Godthab',
        'Goose_Bay',
        'Grand_Turk',
        'Grenada',
        'Guadeloupe',
        'Guatemala',
        'Guayaquil',
        'Guyana',
        'Halifax',
        'Havana',
        'Hermosillo',
        'Indiana/Indianapolis',
        'Indiana/Knox',
        'Indiana/Marengo',
        'Indiana/Vevay',
        'Indianapolis',
        'Inuvik',
        'Iqaluit',
        'Jamaica',
        'Juneau',
        'Kentucky/Monticello',
        'La_Paz',
        'Lima',
        'Los_Angeles',
        'Louisville',
        'Maceio',
        'Managua',
        'Manaus',
        'Martinique',
        'Mazatlan',
        'Menominee',
        'Merida',
        'Mexico_City',
        'Miquelon',
        'Monterrey',
        'Montevideo',
        'Montreal',
        'Montserrat',
        'Nassau',
        'New_York',
        'Nipigon',
        'Nome',
        'Noronha',
        'North_Dakota/Center',
        'Panama',
        'Pangnirtung',
        'Paramaribo',
        'Phoenix',
        'Port_of_Spain',
        'Port-au-Prince',
        'Porto_Velho',
        'Puerto_Rico',
        'Rainy_River',
        'Rankin_Inlet',
        'Recife',
        'Regina',
        'Rio_Branco',
        'Santiago',
        'Santo_Domingo',
        'Sao_Paulo',
        'Scoresbysund',
        'St_Johns',
        'St_Kitts',
        'St_Lucia',
        'St_Thomas',
        'St_Vincent',
        'Swift_Current',
        'Tegucigalpa',
        'Thule',
        'Thunder_Bay',
        'Tijuana',
        'Toronto',
        'Tortola',
        'Vancouver',
        'Whitehorse',
        'Winnipeg',
        'Yakutat',
        'Yellowknife',
      ],
    },
    {
      prefix: 'Antarctica/',
      entries: [
        'Casey',
        'Davis',
        'DumontDUrville',
        'Mawson',
        'McMurdo',
        'Palmer',
        'Rothera',
        'Syowa',
        'Vostok',
      ],
    },
    {
      prefix: 'Asia/',
      entries: [
        'Aden',
        'Almaty',
        'Amman',
        'Anadyr',
        'Aqtau',
        'Aqtobe',
        'Ashgabat',
        'Baghdad',
        'Bahrain',
        'Baku',
        'Bangkok',
        'Beirut',
        'Bishkek',
        'Brunei',
        'Calcutta',
        'Choibalsan',
        'Chongqing',
        'Colombo',
        'Damascus',
        'Dhaka',
        'Dili',
        'Dubai',
        'Dushanbe',
        'Gaza',
        'Harbin',
        'Hong_Kong',
        'Hovd',
        'Irkutsk',
        'Jayapura',
        'Jerusalem',
        'Kabul',
        'Kamchatka',
        'Karachi',
        'Kashgar',
        'Katmandu',
        'Kolkata',
        'Krasnoyarsk',
        'Kuala_Lumpur',
        'Kuching',
        'Kuwait',
        'Macau',
        'Magadan',
        'Makassar',
        'Manila',
        'Muscat',
        'Nicosia',
        'Novosibirsk',
        'Omsk',
        'Oral',
        'Phnom_Penh',
        'Pontianak',
        'Pyongyang',
        'Qatar',
        'Qyzylorda',
        'Rangoon',
        'Riyadh',
        'Saigon',
        'Sakhalin',
        'Samarkand',
        'Seoul',
        'Shanghai',
        'Singapore',
        'Taipei',
        'Tashkent',
        'Tbilisi',
        'Tehran',
        'Thimphu',
        'Tokyo',
        'Ulaanbaatar',
        'Urumqi',
        'Vientiane',
        'Vladivostok',
        'Yakutsk',
        'Yekaterinburg',
        'Yerevan',
      ],
    },
    {
      prefix: 'Atlantic/',
      entries: [
        'Azores',
        'Bermuda',
        'Canary',
        'Cape_Verde',
        'Faeroe',
        'Madeira',
        'Reykjavik',
        'South_Georgia',
        'St_Helena',
        'Stanley',
      ],
    },
    {
      prefix: 'Australia/',
      entries: [
        'Adelaide',
        'Brisbane',
        'Broken_Hill',
        'Darwin',
        'Hobart',
        'Lindeman',
        'Lord_Howe',
        'Melbourne',
        'Perth',
        'Sydney',
      ],
    },
    {
      prefix: 'Europe/',
      entries: [
        'Amsterdam',
        'Andorra',
        'Athens',
        'Belfast',
        'Belgrade',
        'Berlin',
        'Brussels',
        'Bucharest',
        'Budapest',
        'Chisinau',
        'Copenhagen',
        'Dublin',
        'Gibraltar',
        'Helsinki',
        'Istanbul',
        'Kaliningrad',
        'Kiev',
        'Lisbon',
        'London',
        'Luxembourg',
        'Madrid',
        'Malta',
        'Minsk',
        'Monaco',
        'Moscow',
        'Oslo',
        'Paris',
        'Prague',
        'Riga',
        'Rome',
        'Samara',
        'Simferopol',
        'Sofia',
        'Stockholm',
        'Tallinn',
        'Tirane',
        'Uzhgorod',
        'Vaduz',
        'Vienna',
        'Vilnius',
        'Warsaw',
        'Zaporozhye',
        'Zurich',
      ],
    },
    {
      prefix: 'Indian/',
      entries: [
        'Antananarivo',
        'Chagos',
        'Christmas',
        'Comoro',
        'Kerguelen',
        'Mahe',
        'Maldives',
        'Mauritius',
        'Mayotte',
        'Reunion',
      ],
    },
    {
      prefix: 'Pacific/',
      entries: [
        'Apia',
        'Auckland',
        'Chatham',
        'Easter',
        'Efate',
        'Enderbury',
        'Fakaofo',
        'Fiji',
        'Funafuti',
        'Galapagos',
        'Gambier',
        'Guadalcanal',
        'Guam',
        'Honolulu',
        'Kiritimati',
        'Kosrae',
        'Kwajalein',
        'Majuro',
        'Marquesas',
        'Midway',
        'Nauru',
        'Niue',
        'Norfolk',
        'Noumea',
        'Pago_Pago',
        'Palau',
        'Pitcairn',
        'Ponape',
        'Port_Moresby',
        'Rarotonga',
        'Saipan',
        'Tahiti',
        'Tarawa',
        'Tongatapu',
        'Truk',
        'Wake',
        'Wallis',
        'Yap',
      ],
    },
    {
      prefix: '',
      entries: ['UTC'],
    },
  ];
}
