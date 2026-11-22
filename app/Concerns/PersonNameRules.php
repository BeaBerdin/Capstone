<?php

namespace App\Concerns;

/**
 * First name, middle initial and last name: the same rules for
 * self-registration and the Super Admin's Create Account / Edit forms.
 * The combined `name` column is built with User::composeName().
 */
trait PersonNameRules
{
    /** Letters (any language), spaces, hyphens, apostrophes and periods, e.g. "Ma. Clara", "Dela Cruz", "O'Neil". */
    protected const PERSON_NAME_PATTERN = "/^[\pL\pM][\pL\pM .'\-]*$/u";

    /** One or two letters, or one letter and a period: "D", "D.", "DC". */
    protected const MIDDLE_INITIAL_PATTERN = '/^(\pL{1,2}|\pL\.)$/u';

    /**
     * Phone keyboards often type a curly apostrophe (’); it is accepted and
     * saved as a straight one, so "O’Neil" and "O'Neil" are the same.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function normalizePersonName(array $input): array
    {
        foreach (['first_name', 'last_name', 'middle_initial'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = trim(str_replace(["\u{2019}", "\u{2018}"], "'", $input[$field]));
            }
        }

        return $input;
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function personNameRules(bool $required = true): array
    {
        $presence = $required ? 'required' : 'nullable';

        return [
            'first_name' => [$presence, 'string', 'max:100', 'regex:'.self::PERSON_NAME_PATTERN],
            'middle_initial' => ['nullable', 'string', 'max:2', 'regex:'.self::MIDDLE_INITIAL_PATTERN],
            'last_name' => [$presence, 'string', 'max:100', 'regex:'.self::PERSON_NAME_PATTERN],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function personNameMessages(): array
    {
        return [
            'first_name.required' => 'Please enter the first name.',
            'first_name.regex' => 'The first name may only contain letters, spaces, hyphens, apostrophes and periods.',
            'middle_initial.regex' => 'The middle initial must be one or two letters, for example "D", "D." or "DC".',
            'middle_initial.max' => 'The middle initial must be one or two letters, for example "D", "D." or "DC".',
            'last_name.required' => 'Please enter the last name.',
            'last_name.regex' => 'The last name may only contain letters, spaces, hyphens, apostrophes and periods.',
        ];
    }
}
