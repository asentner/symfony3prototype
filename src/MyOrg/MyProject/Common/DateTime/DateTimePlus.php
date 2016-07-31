<?php

namespace MyOrg\MyProject\Common\DateTime;

use DateTime;

/**
 * Extends DateTime().
 *
 * This class extends the PHP DateTime class with more flexible initialization
 * parameters, allowing a date to be created from an existing date object,
 * a timestamp, a string with an unknown format, a string with a known
 * format, or an array of date parts. It also adds an errors array
 * and a __toString() method to the date object.
 *
 * This class is less lenient than the parent DateTime class. It changes
 * the default behavior for handling date values like '2011-00-00'.
 * The parent class would convert that value to '2010-11-30' and report
 * a warning but not an error. This extension treats that as an error.
 *22
 * As with the base class, a date object may be created even if it has
 * errors. It has an errors array attached to it that explains what the
 * errors are. This is less disruptive than allowing datetime exceptions
 * to abort processing. The calling script can decide what to do about
 * errors using hasErrors() and getErrors().
 */

class DateTimePlus extends DateTime
{
    const FORMAT   = 'Y-m-d H:i:s';

    /**
     * A RFC7231 Compliant date.
     *
     * http://tools.ietf.org/html/rfc7231#section-7.1.1.1
     *
     * Example: Sun, 06 Nov 1994 08:49:37 GMT
     */
    const RFC7231 = 'D, d M Y H:i:s \G\M\T';

    /**
     * An array of possible date parts.
     */
    protected static $dateParts = [
        'year',
        'month',
        'day',
        'hour',
        'minute',
        'second',
    ];

    /**
     * The value of the time value passed to the constructor.
     */
    protected $inputTimeRaw = '';

    /**
     * The prepared time, without timezone, for this date.
     */
    protected $inputTimeAdjusted = '';

    /**
     * The value of the timezone passed to the constructor.
     */
    protected $inputTimeZoneRaw = '';

    /**
     * The prepared timezone object used to construct this date.
     */
    protected $inputTimeZoneAdjusted = '';

    /**
     * The value of the format passed to the constructor.
     */
    protected $inputFormatRaw = '';

    /**
     * The prepared format, if provided.
     */
    protected $inputFormatAdjusted = '';

    /**
     * The value of the language code passed to the constructor.
     */
    protected $langcode = NULL;

    /**
     * An array of errors encountered when creating this date.
     */
    protected $errors = [];

    /**
     * @param DateTime $datetime
     * @param \DateTimeZone|null $timeZone
     * @param array $settings
     * @return static
     */
    public static function createFromDateTime(\DateTime $datetime, \DateTimeZone $timeZone = null, $settings = []) {
        if (null === $timeZone) {
            return new static($datetime->format(static::FORMAT), $datetime->getTimezone(), $settings);
        }
        return new static($datetime->format(static::FORMAT), $timeZone, $settings);
    }

    /**
     * @param array $date_parts
     * @param mixed $timezone
     * @param array $settings
     * @return static
     * @throws \Exception
     */
    public static function createFromArray(array $date_parts, $timezone = NULL, $settings = []) {
        $date_parts = static::prepareArray($date_parts, TRUE);
        if (static::checkArray($date_parts)) {
            // Even with validation, we can end up with a value that the
            // parent class won't handle, like a year outside the range
            // of -9999 to 9999, which will pass checkdate() but
            // fail to construct a date object.
            $iso_date = static::arrayToISO($date_parts);
            return new static($iso_date, $timezone, $settings);
        }
        else {
            throw new \Exception('The array contains invalid values.');
        }
    }

    /**
     * @param $timestamp
     * @param mixed $timezone
     * @param array $settings
     * @return static
     * @throws \Exception
     */
    public static function createFromTimestamp($timestamp, $timezone = NULL, $settings = []) {
        if (!is_numeric($timestamp)) {
            throw new \Exception('The timestamp must be numeric.');
        }
        $datetime = new static('', $timezone, $settings);
        $datetime->setTimestamp($timestamp);
        return $datetime;
    }

    /**
     * @param string $format
     * @param string $time
     * @param mixed $timezone
     * @param array $settings
     * @return static
     * @throws \Exception
     */
    public static function createFromFormat($format, $time, $timezone = NULL, $settings = []) {
        if (!isset($settings['validate_format'])) {
            $settings['validate_format'] = TRUE;
        }

        // Tries to create a date from the format and use it if possible.
        // A regular try/catch won't work right here, if the value is
        // invalid it doesn't return an exception.
        $dateTimePlus = new static('', $timezone, $settings);

        $date = \DateTime::createFromFormat($format, $time, $dateTimePlus->getTimezone());
        if (!$date instanceOf \DateTime) {
            throw new \Exception('The date cannot be created from a format.');
        }
        else {
            // Functions that parse date is forgiving, it might create a date that
            // is not exactly a match for the provided value, so test for that by
            // re-creating the date/time formatted string and comparing it to the input. For
            // instance, an input value of '11' using a format of Y (4 digits) gets
            // created as '0011' instead of '2011'.
            if ($date instanceOf DateTimePlus) {
                $test_time = $date->format($format, $settings);
            }
            elseif ($date instanceOf \DateTime) {
                $test_time = $date->format($format);
            }
            $dateTimePlus->setTimestamp($date->getTimestamp());
            $dateTimePlus->setTimezone($date->getTimezone());

            if ($settings['validate_format'] && $test_time != $time) {
                throw new \Exception('The created date does not match the input value.');
            }
        }
        return $dateTimePlus;
    }

    /**
     * @param int $occurrence
     * @param int $day
     * @param int|null $month
     * @param int|null $year
     * @return DateTime|DateTimePlus
     * @throws \Exception
     */
    public static function createFromDayOfMonth($occurrence = 1, $day = 0, $month = null, $year = null)
    {
        $month = (null === $month) ? date('m') : $month;
        $year = (null === $year) ? date('Y') : $year;

        $dateTimePlus = static::createFromFormat('Y-m-d', sprintf('%d-%02d-01', $year, $month));

        while($dateTimePlus->format('w') != $day) {
            $dateTimePlus->add(new \DateInterval('P1D'));
        }

        $dateTimePlus->add(new \DateInterval('P'.($occurrence-1).'W'));

        while($dateTimePlus->format('m') != sprintf('%02d', $month)) {
            $dateTimePlus->sub(new \DateInterval('P1W'));
        }

        return $dateTimePlus;
    }

    /**
     * @param string $time
     * @param mixed $timezone
     * @param array $settings
     */
    public function __construct($time = 'now', $timezone = NULL, $settings = []) {

        // Unpack settings.
        $this->langcode = !empty($settings['langcode']) ? $settings['langcode'] : NULL;

        // Massage the input values as necessary.
        $prepared_time = $this->prepareTime($time);
        $prepared_timezone = $this->prepareTimezone($timezone);

        try {
            if (!empty($prepared_time)) {
                $test = date_parse($prepared_time);
                if (!empty($test['errors'])) {
                    $this->errors[] = $test['errors'];
                }
            }

            if (empty($this->errors)) {
                parent::__construct($prepared_time, $prepared_timezone);
            }
        }
        catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }

        // Clean up the error messages.
        $this->checkErrors();
        $this->errors = array_unique($this->errors);

    }

    /**
     * @return string
     */
    public function __toString() {
        $format = static::FORMAT;
        return $this->format($format) . ' ' . $this->getTimeZone()->getName();
    }

    /**
     * @param $time
     * @return mixed
     */
    protected function prepareTime($time) {
        return $time;
    }

    /**
     * @param mixed $timezone
     * @return \DateTimezone
     */
    protected function prepareTimezone($timezone) {
        // If the input timezone is a valid timezone object, use it.
        if ($timezone instanceOf \DateTimezone) {
            $timezone_adjusted = $timezone;
        }

        // Allow string timezone input, and create a timezone from it.
        elseif (!empty($timezone) && is_string($timezone)) {
            $timezone_adjusted = new \DateTimeZone($timezone);
        }

        // Default to the system timezone when not explicitly provided.
        // If the system timezone is missing, use 'UTC'.
        if (empty($timezone_adjusted) || !$timezone_adjusted instanceOf \DateTimezone) {
            $system_timezone = date_default_timezone_get();
            $timezone_name = !empty($system_timezone) ? $system_timezone : 'UTC';
            $timezone_adjusted = new \DateTimeZone($timezone_name);
        }

        // We are finally certain that we have a usable timezone.
        return $timezone_adjusted;
    }

    /**
     * @param string $format
     * @return string
     */
    protected function prepareFormat($format) {
        return $format;
    }



    /**
     * Examines getLastErrors() to see what errors to report.
     *
     * Two kinds of errors are important: anything that DateTime
     * considers an error, and also a warning that the date was invalid.
     * PHP creates a valid date from invalid data with only a warning,
     * 2011-02-30 becomes 2011-03-03, for instance, but we don't want that.
     *
     * @see http://us3.php.net/manual/en/time.getlasterrors.php
     */
    public function checkErrors() {
        $errors = $this->getLastErrors();
        if (!empty($errors['errors'])) {
            $this->errors += $errors['errors'];
        }
        // Most warnings are messages that the date could not be parsed
        // which causes it to be altered. For validation purposes, a warning
        // as bad as an error, because it means the constructed date does
        // not match the input value.
        if (!empty($errors['warnings'])) {
            $this->errors[] = 'The date is invalid.';
        }
    }

    /**
     * Detects if there were errors in the processing of this date.
     * @return bool
     */
    public function hasErrors() {
        return (bool) count($this->errors);
    }

    /**
     * Retrieves error messages.
     *
     * Public function to return the error messages.
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Creates an ISO date from an array of values.
     *
     * @param array $array
     *   An array of date values keyed by date part.
     * @param bool $force_valid_date
     *   (optional) Whether to force a full date by filling in missing
     *   values. Defaults to FALSE.
     *
     * @return string
     *   The date as an ISO string.
     */
    public static function arrayToISO($array, $force_valid_date = FALSE) {
        $array = static::prepareArray($array, $force_valid_date);
        $input_time = '';
        if ($array['year'] !== '') {
            $input_time = static::datePad(intval($array['year']), 4);
            if ($force_valid_date || $array['month'] !== '') {
                $input_time .= '-' . static::datePad(intval($array['month']));
                if ($force_valid_date || $array['day'] !== '') {
                    $input_time .= '-' . static::datePad(intval($array['day']));
                }
            }
        }
        if ($array['hour'] !== '') {
            $input_time .= $input_time ? 'T' : '';
            $input_time .= static::datePad(intval($array['hour']));
            if ($force_valid_date || $array['minute'] !== '') {
                $input_time .= ':' . static::datePad(intval($array['minute']));
                if ($force_valid_date || $array['second'] !== '') {
                    $input_time .= ':' . static::datePad(intval($array['second']));
                }
            }
        }
        return $input_time;
    }

    /**
     * Creates a complete array from a possibly incomplete array of date parts.
     *
     * @param array $array
     *   An array of date values keyed by date part.
     * @param bool $force_valid_date
     *   (optional) Whether to force a valid date by filling in missing
     *   values with valid values or just to use empty values instead.
     *   Defaults to FALSE.
     *
     * @return array
     *   A complete array of date parts.
     */
    public static function prepareArray($array, $force_valid_date = FALSE) {
        if ($force_valid_date) {
            $now = new \DateTime();
            $array += [
                'year'   => $now->format('Y'),
                'month'  => 1,
                'day'    => 1,
                'hour'   => 0,
                'minute' => 0,
                'second' => 0,
            ];
        }
        else {
            $array += [
                'year'   => '',
                'month'  => '',
                'day'    => '',
                'hour'   => '',
                'minute' => '',
                'second' => '',
            ];
        }
        return $array;
    }

    /**
     * Checks that arrays of date parts will create a valid date.
     *
     * Checks that an array of date parts has a year, month, and day,
     * and that those values create a valid date. If time is provided,
     * verifies that the time values are valid. Sort of an
     * equivalent to checkdate().
     *
     * @param array $array
     *   An array of datetime values keyed by date part.
     *
     * @return boolean
     *   TRUE if the datetime parts contain valid values, otherwise FALSE.
     */
    public static function checkArray($array) {
        $valid_date = FALSE;
        $valid_time = TRUE;
        // Check for a valid date using checkdate(). Only values that
        // meet that test are valid.
        if (array_key_exists('year', $array) && array_key_exists('month', $array) && array_key_exists('day', $array)) {
            if (@checkdate($array['month'], $array['day'], $array['year'])) {
                $valid_date = TRUE;
            }
        }
        // Testing for valid time is reversed. Missing time is OK,
        // but incorrect values are not.
        foreach (['hour', 'minute', 'second'] as $key) {
            if (array_key_exists($key, $array)) {
                $value = $array[$key];
                switch ($key) {
                    case 'hour':
                        if (!preg_match('/^([1-2][0-3]|[01]?[0-9])$/', $value)) {
                            $valid_time = FALSE;
                        }
                        break;
                    case 'minute':
                    case 'second':
                    default:
                        if (!preg_match('/^([0-5][0-9]|[0-9])$/', $value)) {
                            $valid_time = FALSE;
                        }
                        break;
                }
            }
        }
        return $valid_date && $valid_time;
    }

    /**
     * Pads date parts with zeros.
     *
     * Helper function for a task that is often required when working with dates.
     *
     * @param int $value
     *   The value to pad.
     * @param int $size
     *   (optional) Size expected, usually 2 or 4. Defaults to 2.
     *
     * @return string
     *   The padded value.
     */
    public static function datePad($value, $size = 2) {
        return sprintf("%0" . $size . "d", $value);
    }

    /**
     * @param string $format
     * @param array $settings
     * @return string|void
     */
    public function format($format, $settings = []) {

        // If there were construction errors, we can't format the date.
        if ($this->hasErrors()) {
            return;
        }

        // Format the date and catch errors.
        try {
            $value = parent::format($format);
        }
        catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
        return $value;
    }

    /**
     * Sets the time to midnight
     *
     * @return $this
     */
    public function midnight()
    {
        $this->modify('midnight');
        return $this;
    }

    /**
     * Alias of midnight
     *
     * @return $this
     */
    public function beginningOfDay()
    {
        $this->midnight();
        return $this;
    }

    /**
     * Sets the time to 11:59:59pm
     *
     * @return $this
     */
    public function endOfDay()
    {
        //$this->midnight();
        //$this->add(new \DateInterval('P1D'));
        //$this->setTimestamp($this->getTimestamp() - 1);
        $this->modify('23:59:59');
        return $this;
    }

    /**
     * Gets the occurrence of current day in the month.
     *
     * @return int
     */
    public function getDayOccurrenceOfMonth()
    {
        $dayOfWeek = $this->format('l');
        $date = self::createFromDateTime($this);
        $date->modify('First '.$dayOfWeek.' of this month');

        for($i = 1; ; $i++) {
            if ($date->format('mdY') == $this->format('mdY')) {
                return $i;
            }
            $date->add(new \DateInterval('P1W'));
        }
    }
}