<?php

define( 'NCORE_HOLI_ROSE_MONDAY',    'easternI-48' );
define( 'NCORE_HOLI_ASH_WEDNESDAY',  'easternI-46' );
define( 'NCORE_HOLI_GOOD_FRIDAY',    'easternI-02' );
define( 'NCORE_HOLI_EASTER_SUNDAY',  'easternI+0'  );
define( 'NCORE_HOLI_EASTER_MONDAY',  'easternI+1'  );
define( 'NCORE_HOLI_WHIT_SUNDAY',    'easternI+49' );
define( 'NCORE_HOLI_WHIT_MONDAY',    'easternI+50' );
define( 'NCORE_HOLI_CORPUS_CHRISTI', 'easternI+60' );


define( 'NCORE_HOLI_HALLOWEEN',      'dateI10-31' );
define( 'NCORE_HOLI_CHRISTMAS_1ST',  'dateI12-25' );
define( 'NCORE_HOLI_CHRISTMAS_2ST',  'dateI12-26' );
define( 'NCORE_HOLI_NEW_YEARS_EVE',  'dateI12-31' );
define( 'NCORE_HOLI_NEW_YEAR',       'dateI01-01' );

function holiday_options()
{
    return array(
        NCORE_HOLI_NEW_YEAR       => _ncore( 'New year' ),

        NCORE_HOLI_ROSE_MONDAY    => _ncore( 'Rose monday' ),
        NCORE_HOLI_ASH_WEDNESDAY  => _ncore( 'Ash wednesday' ),
        NCORE_HOLI_GOOD_FRIDAY    => _ncore( 'Good friday' ),
        NCORE_HOLI_EASTER_SUNDAY  => _ncore( 'Easter sunday' ),
        // NCORE_HOLI_EASTER_MONDAY  => _ncore( 'Easter monday' ),
        NCORE_HOLI_WHIT_SUNDAY    => _ncore( 'Whit sunday' ),
        // NCORE_HOLI_WHIT_MONDAY    => _ncore( 'Whit monday' ),
        // NCORE_HOLI_CORPUS_CHRISTI => _ncore( 'Corpus christi' ),
        NCORE_HOLI_HALLOWEEN => _ncore( 'Halloween' ),
//        NCORE_HOLI_CHRISTMAS_EVE  => _ncore( 'Christmas eve' ),
        NCORE_HOLI_CHRISTMAS_1ST  => _ncore( 'Christmas 1st day' ),
        // NCORE_HOLI_CHRISTMAS_2ST  => _ncore( 'Christmas 2nd day' ),
        NCORE_HOLI_NEW_YEARS_EVE  => _ncore( 'New years eve' ),
    );
}

function holiday_date( $holiday, $year='upcoming' )
{
    $maybe_try_next_year = $year==='upcoming';

    $year = $year === 'current' || $maybe_try_next_year
          ? date('Y')
          : intval( $year );

    if ($year < 2012)
    {
        trigger_error( "Invalid year - please use year 2012 or later" );
        return false;
    }

    list( $type, $what ) = ncore_retrieveList( 'I', $holiday, 2, true );

    switch ($type)
    {
        case 'date':
            $mm_dd = $what;
            $offset = 0;
            break;

        case 'eastern';
            $mm_dd = holiday_easterSundayDates( $year );
            $offset = intval($what);
            if (!$mm_dd) {
                trigger_error( "Easter sunday dates missing for year $year" );
                return false;
            }
            break;
        default:
            $mm_dd = '0-0';
            $offset = 0;
    }

    list( $month, $day )= ncore_retrieveList( '-', $mm_dd, 2, true );

    if (!$month || !$day)
    {
        trigger_error( "Invalid holiday: $holiday" );
        return false;
    }

    $date = "$year-$month-$day";

    if ($offset != 0)
    {
        $date_unix = strtotime( $date );

        $date_unix += $offset * 86400;

        $date = date( 'Y-m-d', $date_unix );;
    }

    if ($maybe_try_next_year)
    {
        $date_unix = strtotime( $date );
        $is_past = $date_unix < time() - 86400;
        if ($is_past)
        {
            $date = holiday_date( $holiday, $year+1 );
        }
    }

    return $date;
}

function holiday_easterSundayDates( $year='all' ) {

    $easter_sundays = array(
        2012 => '04-08',        2013 => '03-31',        2014 => '04-20',        2015 => '04-05',
        2016 => '03-27',        2017 => '04-16',        2018 => '04-01',        2019 => '04-21',
        2020 => '04-12',        2021 => '04-04',        2022 => '04-17',        2023 => '04-09',
        2024 => '03-31',        2025 => '04-20',        2026 => '04-05',        2027 => '03-28',
        2028 => '04-16',        2029 => '04-01',        2030 => '04-21',        2031 => '04-13',
        2032 => '03-28',        2033 => '04-17',        2034 => '04-09',        2035 => '03-25',
        2036 => '04-13',        2037 => '04-05',        2038 => '04-25',        2039 => '04-10',
        2040 => '04-01',        2041 => '04-21',        2042 => '04-06',        2043 => '03-29',
        2044 => '04-17',        2045 => '04-09',        2046 => '03-25',        2047 => '04-14',
        2048 => '04-05',        2049 => '04-18',        2050 => '04-10',        2051 => '04-02',
        2052 => '04-21',        2053 => '04-06',        2054 => '03-29',        2055 => '04-18',
        2056 => '04-02',        2057 => '04-22',        2058 => '04-14',        2059 => '03-30',
        2060 => '04-18',        2061 => '04-10',        2062 => '03-26',        2063 => '04-15',
        2064 => '04-06',        2065 => '03-29',        2066 => '04-11',        2067 => '04-03',
        2068 => '04-22',        2069 => '04-14',        2070 => '03-30',        2071 => '04-19',
        2072 => '04-10',        2073 => '03-26',        2074 => '04-15',        2075 => '04-07',
        2076 => '04-19',        2077 => '04-11',        2078 => '04-03',        2079 => '04-23',
        2080 => '04-07',        2081 => '03-30',        2082 => '04-19',        2083 => '04-04',
        2084 => '03-26',        2085 => '04-15',        2086 => '03-31',        2087 => '04-20',
        2088 => '04-11',        2089 => '04-03',        2090 => '04-16',        2091 => '04-08',
        2092 => '03-30',        2093 => '04-12',        2094 => '04-04',        2095 => '04-24',
        2096 => '04-15',        2097 => '03-31',        2098 => '04-20',        2099 => '04-12',
        2100 => '03-28',        2101 => '04-17',        2102 => '04-09',        2103 => '03-25',
        2104 => '04-13',        2105 => '04-05',        2106 => '04-18',        2107 => '04-10',
        2108 => '04-01',        2109 => '04-21',        2110 => '04-06',        2111 => '03-29',
        2112 => '04-17',        2113 => '04-02',        2114 => '04-22',        2115 => '04-14',
        2116 => '03-29',        2117 => '04-18',        2118 => '04-10',        2119 => '03-26',
        2120 => '04-14',        2121 => '04-06',        2122 => '03-29',        2123 => '04-11',
        2124 => '04-02',        2125 => '04-22',        2126 => '04-14',        2127 => '03-30',
        2128 => '04-18',        2129 => '04-10',        2130 => '03-26',        2131 => '04-15',
        2132 => '04-06',        2133 => '04-19',        2134 => '04-11',        2135 => '04-03',
        2136 => '04-22',        2137 => '04-07',        2138 => '03-30',        2139 => '04-19',
        2140 => '04-03',        2141 => '03-26',        2142 => '04-15',        2143 => '03-31',
        2144 => '04-19',        2145 => '04-11',        2146 => '04-03',        2147 => '04-16',
        2148 => '04-07',        2149 => '03-30',        2150 => '04-12',        2151 => '04-04',
        2152 => '04-23',        2153 => '04-15',        2154 => '03-31',        2155 => '04-20',
        2156 => '04-11',        2157 => '03-27',        2158 => '04-16',        2159 => '04-08',
        2160 => '03-23',        2161 => '04-12',        2162 => '04-04',        2163 => '04-24',
        2164 => '04-08',        2165 => '03-31',        2166 => '04-20',        2167 => '04-05',
        2168 => '03-27',        2169 => '04-16',        2170 => '04-01',        2171 => '04-21',
        2172 => '04-12',        2173 => '04-04',        2174 => '04-17',        2175 => '04-09',
        2176 => '03-31',        2177 => '04-20',        2178 => '04-05',        2179 => '03-28',
        2180 => '04-16',        2181 => '04-01',        2182 => '04-21',        2183 => '04-13',
        2184 => '03-28',        2185 => '04-17',        2186 => '04-09',        2187 => '03-25',
        2188 => '04-13',        2189 => '04-05',        2190 => '04-25',        2191 => '04-10',
        2192 => '04-01',        2193 => '04-21',        2194 => '04-06',        2195 => '03-29',
        2196 => '04-17',        2197 => '04-09',        2198 => '03-25',        2199 => '04-14',
        2200 => '04-06'
    );

    return $year === 'all'
           ? $easter_sundays
           : ncore_retrieve( $easter_sundays, $year, false );
}


