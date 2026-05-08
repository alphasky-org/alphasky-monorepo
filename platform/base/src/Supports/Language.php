<?php

namespace Alphasky\Base\Supports;

use Alphasky\Base\Facades\BaseHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;

class Language
{
    /**
     * The list of flags
     *
     * for each flag:
     * the key is the flag file name (without the extension)
     * the value is the Country name
     */
    protected static array $flags = [
        'ad' => 'Andorra',
        'ae' => 'United Arab Emirates',
        'af' => 'Afghanistan',
        'ag' => 'Antigua and Barbuda',
        'ai' => 'Anguilla',
        'al' => 'Albania',
        'am' => 'Armenia',
        'ao' => 'Angola',
        'ar' => 'Argentina',
        'as' => 'American Samoa',
        'at' => 'Austria',
        'au' => 'Australia',
        'aw' => 'Aruba',
        'ax' => 'Åland Islands',
        'az' => 'Azerbaijan',
        'ba' => 'Bosnia and Herzegovina',
        'bb' => 'Barbados',
        'bd' => 'Bangladesh',
        'be' => 'Belgium',
        'bf' => 'Burkina Faso',
        'bg' => 'Bulgaria',
        'bh' => 'Bahrain',
        'bi' => 'Burundi',
        'bj' => 'Benin',
        'bm' => 'Bermuda',
        'bn' => 'Brunei',
        'bo' => 'Bolivia',
        'br' => 'Brazil',
        'bs' => 'Bahamas',
        'bt' => 'Bhutan',
        'bw' => 'Botswana',
        'by' => 'Belarus',
        'bz' => 'Belize',
        'ca' => 'Canada',
        'cc' => 'Cocos',
        'cd' => 'Democratic Republic of the Congo',
        'cf' => 'Central African Republic',
        'cg' => 'Congo',
        'ch' => 'Switzerland',
        'ci' => 'Ivory Coast',
        'ck' => 'Cook Islands',
        'cl' => 'Chile',
        'cm' => 'Cameroon',
        'cn' => 'China',
        'co' => 'Colombia',
        'cr' => 'Costa Rica',
        'cu' => 'Cuba',
        'cv' => 'Cape Verde',
        'cx' => 'Christmas Island',
        'cy' => 'Cyprus',
        'cz' => 'Czech Republic',
        'de' => 'Germany',
        'dj' => 'Djibouti',
        'dk' => 'Denmark',
        'dm' => 'Dominica',
        'do' => 'Dominican Republic',
        'dz' => 'Algeria',
        'ec' => 'Ecuador',
        'ee' => 'Estonia',
        'eg' => 'Egypt',
        'eh' => 'Western Sahara',
        'er' => 'Eritrea',
        'es' => 'Spain',
        'et' => 'Ethiopia',
        'fi' => 'Finland',
        'fj' => 'Fiji',
        'fk' => 'Falkland Islands',
        'fm' => 'Micronesia',
        'fo' => 'Faroe Islands',
        'fr' => 'France',
        'ga' => 'Gabon',
        'gb' => 'United Kingdom',
        'gd' => 'Grenada',
        'ge' => 'Georgia',
        'gh' => 'Ghana',
        'gi' => 'Gibraltar',
        'gl' => 'Greenland',
        'gm' => 'Gambia',
        'gn' => 'Guinea',
        'gp' => 'Guadeloupe',
        'gq' => 'Equatorial Guinea',
        'gr' => 'Greece',
        'gs' => 'South Georgia and the South Sandwich Islands',
        'gt' => 'Guatemala',
        'gu' => 'Guam',
        'gw' => 'Guinea-Bissau',
        'gy' => 'Guyana',
        'hk' => 'Hong Kong',
        'hm' => 'Heard Island and McDonald Islands',
        'hn' => 'Honduras',
        'hr' => 'Croatia',
        'ht' => 'Haiti',
        'hu' => 'Hungary',
        'id' => 'Indonesia',
        'ie' => 'Republic of Ireland',
        'il' => 'Israel',
        'in' => 'India',
        'io' => 'British Indian Ocean Territory',
        'iq' => 'Iraq',
        'ir' => 'Iran',
        'is' => 'Iceland',
        'it' => 'Italy',
        'jm' => 'Jamaica',
        'jo' => 'Jordan',
        'jp' => 'Japan',
        'ke' => 'Kenya',
        'kg' => 'Kyrgyzstan',
        'kh' => 'Cambodia',
        'ki' => 'Kiribati',
        'km' => 'Comoros',
        'kn' => 'Saint Kitts and Nevis',
        'kp' => 'North Korea',
        'kr' => 'South Korea',
        'kw' => 'Kuwait',
        'ky' => 'Cayman Islands',
        'kz' => 'Kazakhstan',
        'la' => 'Laos',
        'lb' => 'Lebanon',
        'lc' => 'Saint Lucia',
        'li' => 'Liechtenstein',
        'lk' => 'Sri Lanka',
        'lr' => 'Liberia',
        'ls' => 'Lesotho',
        'lt' => 'Lithuania',
        'lu' => 'Luxembourg',
        'lv' => 'Latvia',
        'ly' => 'Libya',
        'ma' => 'Morocco',
        'mc' => 'Monaco',
        'md' => 'Moldova',
        'me' => 'Montenegro',
        'mg' => 'Madagascar',
        'mh' => 'Marshall Islands',
        'mk' => 'Macedonia',
        'ml' => 'Mali',
        'mm' => 'Myanmar',
        'mn' => 'Mongolia',
        'mo' => 'Macao',
        'mp' => 'Northern Mariana Islands',
        'mq' => 'Martinique',
        'mr' => 'Mauritania',
        'ms' => 'Montserrat',
        'mt' => 'Malta',
        'mu' => 'Mauritius',
        'mv' => 'Maldives',
        'mw' => 'Malawi',
        'mx' => 'Mexico',
        'my' => 'Malaysia',
        'mz' => 'Mozambique',
        'na' => 'Namibia',
        'nc' => 'New Caledonia',
        'ne' => 'Niger',
        'nf' => 'Norfolk Island',
        'ng' => 'Nigeria',
        'ni' => 'Nicaragua',
        'nl' => 'Netherlands',
        'no' => 'Norway',
        'np' => 'Nepal',
        'nr' => 'Nauru',
        'nu' => 'Niue',
        'nz' => 'New Zealand',
        'om' => 'Oman',
        'pa' => 'Panama',
        'pe' => 'Peru',
        'pf' => 'French Polynesia',
        'pg' => 'Papua New Guinea',
        'ph' => 'Philippines',
        'pk' => 'Pakistan',
        'pl' => 'Poland',
        'pm' => 'Saint Pierre and Miquelon',
        'pn' => 'Pitcairn',
        'pr' => 'Puerto Rico',
        'ps' => 'Palestinian Territory',
        'pt' => 'Portugal',
        'pw' => 'Belau',
        'py' => 'Paraguay',
        'qa' => 'Qatar',
        'ro' => 'Romania',
        'rs' => 'Serbia',
        'ru' => 'Russia',
        'rw' => 'Rwanda',
        'sa' => 'Saudi Arabia',
        'sb' => 'Solomon Islands',
        'sc' => 'Seychelles',
        'sd' => 'Sudan',
        'se' => 'Sweden',
        'sg' => 'Singapore',
        'sh' => 'Saint Helena',
        'si' => 'Slovenia',
        'sk' => 'Slovakia',
        'sl' => 'Sierra Leone',
        'sm' => 'San Marino',
        'sn' => 'Senegal',
        'so' => 'Somalia',
        'sr' => 'Suriname',
        'ss' => 'South Sudan',
        'st' => 'São Tomé and Príncipe',
        'sv' => 'El Salvador',
        'sy' => 'Syria',
        'sz' => 'Swaziland',
        'tc' => 'Turks and Caicos Islands',
        'td' => 'Chad',
        'tf' => 'French Southern Territories',
        'tg' => 'Togo',
        'th' => 'Thailand',
        'tj' => 'Tajikistan',
        'tk' => 'Tokelau',
        'tl' => 'Timor-Leste',
        'tm' => 'Turkmenistan',
        'tn' => 'Tunisia',
        'to' => 'Tonga',
        'tr' => 'Turkey',
        'tt' => 'Trinidad and Tobago',
        'tv' => 'Tuvalu',
        'tw' => 'Taiwan',
        'tz' => 'Tanzania',
        'ua' => 'Ukraine',
        'ug' => 'Uganda',
        'us' => 'United States',
        'uy' => 'Uruguay',
        'uz' => 'Uzbekistan',
        'va' => 'Vatican',
        'vc' => 'Saint Vincent and the Grenadines',
        've' => 'Venezuela',
        'vg' => 'British Virgin Islands',
        'vi' => 'United States Virgin Islands',
        'vn' => 'Vietnam',
        'vu' => 'Vanuatu',
        'wf' => 'Wallis and Futuna',
        'ws' => 'Western Samoa',
        'ye' => 'Yemen',
        'yt' => 'Mayotte',
        'za' => 'South Africa',
        'zm' => 'Zambia',
        'zw' => 'Zimbabwe',
    ];

    /**
     * The list of predefined languages
     *
     * for each language:
     * [0] => ISO 639-1 language code
     * [1] => Laravel locale
     * [2] => name
     * [3] => text direction
     * [4] => flag code
     */
    protected static array $languages = [
        'af' => ['af', 'af', 'Afrikaans', 'ltr', 'za'],
        'ar' => ['ar', 'ar', 'العربية', 'rtl', 'ar'],
        'ary' => ['ar', 'ary', 'العربية المغربية', 'rtl', 'ma'],
        'az' => ['az', 'az', 'Azərbaycan', 'ltr', 'az'],
        'azb' => ['az', 'azb', 'گؤنئی آذربایجان', 'rtl', 'az'],
        'bel' => ['be', 'bel', 'Беларуская мова', 'ltr', 'by'],
        'bg' => ['bg', 'bg', 'български', 'ltr', 'bg'],
        'bn' => ['bn', 'bn', 'বাংলা', 'ltr', 'bd'],
        'bo' => ['bo', 'bo', 'བོད་སྐད', 'ltr', 'tibet'],
        'bs' => ['bs', 'bs', 'Bosanski', 'ltr', 'ba'],
        'ca' => ['ca', 'ca', 'Catalan', 'ltr', 'es'],
        'ceb' => ['ceb', 'ceb', 'Cebuano', 'ltr', 'ph'],
        'cs' => ['cs', 'cs', 'Čeština', 'ltr', 'cz'],
        'cy' => ['cy', 'cy', 'Cymraeg', 'ltr', 'gb-wls'],
        'da' => ['da', 'da', 'Dansk', 'ltr', 'dk'],
        'el' => ['el', 'el', 'Ελληνικά', 'ltr', 'gr'],
        'en' => ['en', 'en', 'English', 'ltr', 'us'],
        'es' => ['es', 'es', 'Español', 'ltr', 'cl'],
        'et' => ['et', 'et', 'Eesti', 'ltr', 'ee'],
        'eu' => ['eu', 'eu', 'Euskara', 'ltr', 'fr'],
        'fa' => ['fa', 'fa', 'فارسی', 'rtl', 'af'],
        'fi' => ['fi', 'fi', 'Suomi', 'ltr', 'fi'],
        'fo' => ['fo', 'fo', 'Føroyskt', 'ltr', 'fo'],
        'fr' => ['fr', 'fr', 'Français', 'ltr', 'be'],
        'fy' => ['fy', 'fy', 'Frysk', 'ltr', 'nl'],
        'gd' => ['gd', 'gd', 'Gàidhlig', 'ltr', 'gb-sct'],
        'gl' => ['gl', 'gl', 'Galego', 'ltr', 'gl'],
        'gu' => ['gu', 'gu', 'ગુજરાતી', 'ltr', 'in'],
        'haz' => ['haz', 'haz', 'هزاره گی', 'rtl', 'af'],
        'he' => ['he', 'he', 'עברית', 'rtl', 'il'],
        'hi' => ['hi', 'hi', 'हिन्दी', 'ltr', 'in'],
        'hr' => ['hr', 'hr', 'Hrvatski', 'ltr', 'hr'],
        'ht' => ['ht', 'ht', 'Kreyòl Ayisyen', 'ltr', 'ht'],
        'hu' => ['hu', 'hu', 'Magyar', 'ltr', 'hu'],
        'hy' => ['hy', 'hy', 'Հայերեն', 'ltr', 'am'],
        'id' => ['id', 'id', 'Bahasa Indonesia', 'ltr', 'id'],
        'is' => ['is', 'is', 'Íslenska', 'ltr', 'is'],
        'it' => ['it', 'it', 'Italiano', 'ltr', 'it'],
        'ja' => ['ja', 'ja', '日本語', 'ltr', 'jp'],
        'jv' => ['jv', 'jv', 'Basa Jawa', 'ltr', 'id'],
        'ka' => ['ka', 'ka', 'ქართული', 'ltr', 'ge'],
        'kk' => ['kk', 'kk', 'Қазақ тілі', 'ltr', 'kz'],
        'kh' => ['kh', 'kh', 'Cambodia', 'ltr', 'kh'],
        'ko' => ['ko', 'ko', '한국어', 'ltr', 'kr'],
        'ky' => ['ky', 'ky', 'Кыргызча', 'ltr', 'kg'],
        'ckb' => ['ku', 'ckb', 'کوردی', 'rtl', 'kurdistan'],
        'lo' => ['lo', 'lo', 'ພາສາລາວ', 'ltr', 'la'],
        'lt' => ['lt', 'lt', 'Lietuviškai', 'ltr', 'lt'],
        'lv' => ['lv', 'lv', 'Latviešu valoda', 'ltr', 'lv'],
        'mk' => ['mk', 'mk', 'македонски јазик', 'ltr', 'mk'],
        'mn' => ['mn', 'mn', 'Монгол хэл', 'ltr', 'mn'],
        'mr' => ['mr', 'mr', 'मराठी', 'ltr', 'in'],
        'ms' => ['ms', 'ms', 'Bahasa Melayu', 'ltr', 'my'],
        'my' => ['my', 'my', 'ဗမာစာ', 'ltr', 'mm'],
        'mv' => ['mv', 'mv', 'Maldives', 'rtl', 'mv'],
        'nb' => ['nb', 'nb', 'Norsk Bokmål', 'ltr', 'no'],
        'ne' => ['ne', 'ne', 'नेपाली', 'ltr', 'np'],
        'nl' => ['nl', 'nl', 'Nederlands', 'ltr', 'nl'],
        'nn' => ['nn', 'nn', 'Norsk Nynorsk', 'ltr', 'no'],
        'pl' => ['pl', 'pl', 'Polski', 'ltr', 'pl'],
        'ps' => ['ps', 'ps', 'پښتو', 'rtl', 'af'],
        'pt' => ['pt', 'pt', 'Português', 'ltr', 'pt'],
        'ro' => ['ro', 'ro', 'Română', 'ltr', 'ro'],
        'ru' => ['ru', 'ru', 'Русский', 'ltr', 'ru'],
        'si' => ['si', 'si', 'සිංහල', 'ltr', 'lk'],
        'sk' => ['sk', 'sk', 'Slovenčina', 'ltr', 'sk'],
        'sl' => ['sl', 'sl', 'Slovenščina', 'ltr', 'si'],
        'so' => ['so', 'so', 'Af-Soomaali', 'ltr', 'so'],
        'sq' => ['sq', 'sq', 'Shqip', 'ltr', 'al'],
        'sr' => ['sr', 'sr', 'Српски језик', 'ltr', 'rs'],
        'su' => ['su', 'su', 'Basa Sunda', 'ltr', 'id'],
        'sv' => ['sv', 'sv', 'Svenska', 'ltr', 'se'],
        'szl' => ['szl', 'szl', 'Ślōnskŏ gŏdka', 'ltr', 'pl'],
        'sw' => ['sw', 'sw', 'Swahili', 'ltr', 'tz'],
        'ta' => ['ta', 'ta', 'தமிழ்', 'ltr', 'lk'],
        'th' => ['th', 'th', 'ไทย', 'ltr', 'th'],
        'tl' => ['tl', 'tl', 'Tagalog', 'ltr', 'ph'],
        'tr' => ['tr', 'tr', 'Türkçe', 'ltr', 'tr'],
        'ug' => ['ug', 'ug', 'Uyƣurqə', 'ltr', 'cn'],
        'uk' => ['uk', 'uk', 'Українська', 'ltr', 'ua'],
        'ur' => ['ur', 'ur', 'اردو', 'rtl', 'pk'],
        'uz' => ['uz', 'uz', 'Oʻzbek', 'ltr', 'uz'],
        'vi' => ['vi', 'vi', 'Tiếng Việt', 'ltr', 'vn'],
        'zh' => ['zh', 'zh', '中文 (中国)', 'ltr', 'cn'],
        'tg' => ['tg', 'tg', 'Tajik', 'ltr', 'tj'],
    ];

    public static function getListLanguageFlags(): array
    {
        return self::$flags;
    }

    public static function getAvailableLocales(bool $original = false): array
    {
        $languages = [];
        $locales = BaseHelper::scanFolder(lang_path());
        if (in_array('vendor', $locales)) {
            $locales = array_merge($locales, BaseHelper::scanFolder(lang_path('vendor')));
        }

        foreach ($locales as $locale) {
            if ($locale === 'vendor') {
                continue;
            }

            foreach (Language::getListLanguages() as $key => $language) {
                if (in_array($key, [$locale, str_replace('-', '_', $locale)]) ||
                    in_array($language[1], [$locale, str_replace('-', '_', $locale)])
                ) {
                    $languages[$locale] = [
                        'locale' => $locale,
                        'code' => $language[1],
                        'name' => $language[2],
                        'flag' => $language[4],
                        'is_rtl' => $language[3] === 'rtl',
                    ];

                    break;
                }

                if (! array_key_exists($locale, $languages) &&
                    in_array($language[0], [$locale, str_replace('-', '_', $locale)])) {
                    $languages[$locale] = [
                        'locale' => $locale,
                        'code' => $language[1],
                        'name' => $language[2],
                        'flag' => $language[4],
                        'is_rtl' => $language[3] === 'rtl',
                    ];
                }
            }

            if (! array_key_exists($locale, $languages) && File::isDirectory(lang_path($locale))) {
                $languages[$locale] = [
                    'locale' => $locale,
                    'code' => $locale,
                    'name' => $locale,
                    'flag' => $locale,
                    'is_rtl' => Arr::get($languages, "$locale.3") === 'rtl',
                ];
            }
        }

        if ($original) {
            return $languages;
        }

        return apply_filters('core_available_locales', $languages);
    }

    public static function getListLanguages(): array
    {
        return self::$languages;
    }

    public static function getDefaultLanguage(): array
    {
        return apply_filters('core_default_language', [
            'locale' => 'en',
            'code' => 'en_US',
            'name' => 'English',
            'flag' => 'us',
            'is_rtl' => false,
        ]);
    }

    public static function getLocales(): array
    {
        $locales = collect(static::getListLanguages())->pluck('2', '0')->unique()->all();

        $locales = [
            ...$locales,
            'de' => 'Deutsch',
            'pt' => 'Português',
            'sr' => 'Srpski',
            'uz' => 'Ўзбек',
            'uz' => 'O‘zbek',
            'zh' => '中文',         
        ];

        ksort($locales);

        return $locales;
    }

    public static function getLocaleKeys(): array
    {
        $locales = array_unique(array_keys(static::getLocales()));

        return apply_filters('core_locales', $locales);
    }

    public static function getLanguageCodes(): array
    {
        return collect(static::getListLanguages())->pluck('1')->unique()->all();
    }

    public static function getCurrentLocale(): array
    {
        $locale = static::getDefaultLanguage();

        if (array_key_exists($currentLocale = App::getLocale(), $availableLocales = static::getAvailableLocales())) {
            return Arr::get($availableLocales, $currentLocale, $locale);
        }

        return $locale;
    }
}
