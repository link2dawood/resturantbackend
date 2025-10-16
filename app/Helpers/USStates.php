<?php

namespace App\Helpers;

class USStates
{
    /**
     * Get all US states as an associative array
     * Key = state abbreviation, Value = full state name
     */
    public static function getStates(): array
    {
        return [
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
            'DC' => 'District of Columbia',
            'AS' => 'American Samoa',
            'GU' => 'Guam',
            'MP' => 'Northern Mariana Islands',
            'PR' => 'Puerto Rico',
            'VI' => 'U.S. Virgin Islands',
        ];
    }

    /**
     * Get state name by abbreviation
     */
    public static function getStateName(string $abbreviation): ?string
    {
        $states = self::getStates();

        return $states[strtoupper($abbreviation)] ?? null;
    }

    /**
     * Get state abbreviation by name
     */
    public static function getStateAbbreviation(string $name): ?string
    {
        $states = array_flip(self::getStates());

        return $states[$name] ?? null;
    }

    /**
     * Validate if a state abbreviation is valid
     */
    public static function isValidState(string $abbreviation): bool
    {
        return array_key_exists(strtoupper($abbreviation), self::getStates());
    }

    /**
     * Get states formatted for select options
     */
    public static function getStatesForSelect(): array
    {
        $states = self::getStates();
        $options = ['' => 'Select State'];

        foreach ($states as $abbr => $name) {
            $options[$abbr] = $name.' ('.$abbr.')';
        }

        return $options;
    }

    /**
     * Get states from database formatted for select options
     */
    public static function getStatesFromDatabaseForSelect(): array
    {
        try {
            $states = \App\Models\State::orderBy('name')->get();
            $options = ['' => 'Select State'];

            foreach ($states as $state) {
                $options[$state->code] = $state->name.' ('.$state->code.')';
            }

            return $options;
        } catch (\Exception $e) {
            // Fallback to hardcoded states if database is not available
            return self::getStatesForSelect();
        }
    }
}
