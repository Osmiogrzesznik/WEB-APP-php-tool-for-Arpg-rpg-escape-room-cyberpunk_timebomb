<?php
function timezone_list() {
    static $timezones = null;

    if ($timezones === null) {
        $timezones = array();
        $offsets = array();
        $now = new DateTime('now', new DateTimeZone('UTC'));
        //create one date
        //for each timezone name change this date's timezone to it and get
        //GMT offset , trasforming it to string like GMT + andso on
        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $now->setTimezone(new DateTimeZone($timezone));
            $offsets[] = $offset = $now->getOffset();
            $timezones[$timezone] = '(' . format_GMT_offset($offset) . ') ' . format_timezone_name($timezone);
        }

        array_multisort($timezones,$offsets);
    }

    return $timezones;
}

function format_GMT_offset($offset) {
    $hours = intval($offset / 3600);
    $minutes = abs(intval($offset % 3600 / 60));
    return 'GMT' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
}

function format_timezone_name($name) {
    $name = str_replace('/', ', ', $name);
    $name = str_replace('_', ' ', $name);
    $name = str_replace('St ', 'St. ', $name);
    return $name;
}

?>
