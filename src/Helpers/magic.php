<?php

if (! function_exists('exportPhpValue')) {
    /**
     * Export a PHP value into well-formatted PHP code.
     *
     * Features:
     *  - Uses short array syntax [] instead of array()
     *  - Top-level arrays (indent=0) always multiline
     *  - Nested arrays are compacted into one line if their string representation
     *    is shorter than $inlineLimit (default 40 chars)
     *  - Commas always appear at the end of the line, before the newline
     *  - Handles lists (0,1,2,...) differently from associative arrays
     *  - Recursively formats nested arrays with proper indentation
     *
     * @param  mixed  $value  The value to export (array, string, int, bool, null, etc.)
     * @param  int  $indent  Current indentation level (how many "4-space" steps)
     * @param  int  $inlineLimit  Max line length to keep nested arrays inline
     */
    function exportPhpValue($value, int $indent = 0, int $inlineLimit = 40): string
    {
        // 1️⃣ Check if the value is an array
        if (is_array($value)) {

            // 1a. Determine if array is a "list":
            // keys are sequential 0..n-1 → can omit the keys when printing
            $isList = array_keys($value) === range(0, count($value) - 1);

            // 1b. Prepare array to hold formatted items
            $items = [];

            // 1c. Loop over each element and recursively format
            foreach ($value as $k => $v) {

                // Indentation prefix: each nested level adds 4 spaces
                // $indent+1 because child elements are one level deeper than parent
                $prefix = str_repeat('    ', $indent + 1);

                if ($isList) {
                    // Lists: just the value
                    // Recursive call increases indent for nested arrays
                    $items[] = $prefix.exportPhpValue($v, $indent + 1, $inlineLimit);
                } else {
                    // Associative arrays: print key => value
                    $items[] = $prefix.var_export($k, true).' => '.exportPhpValue($v, $indent + 1, $inlineLimit);
                }
            }

            // 2️⃣ Attempt to compact small nested arrays into one line
            // Only for nested arrays (indent > 0)
            if ($indent > 0) {

                // Build "flat" version without indentation or newlines
                $flatItems = [];
                foreach ($value as $k => $v) {
                    if ($isList) {
                        $flatItems[] = exportPhpValue($v, 0, $inlineLimit);
                    } else {
                        $flatItems[] = var_export($k, true).' => '.exportPhpValue($v, 0, $inlineLimit);
                    }
                }
                $flat = '['.implode(', ', $flatItems).']';

                // If the flat version is short enough → return it
                if (mb_strlen($flat) <= $inlineLimit) {
                    return $flat;
                }
            }

            // 3️⃣ Otherwise, produce multi-line output
            // Join items with ",\n" → comma stays on same line, newline after
            $glue = ",\n";
            $body = implode($glue, $items);

            // Wrap the array with opening and closing brackets
            // Closing bracket aligned with parent indentation
            return "[\n"                   // opening bracket on new line
                .$body                   // all items, already indented
                ."\n".str_repeat('    ', $indent) // closing bracket aligned with parent
                .']';
        }

        // 4️⃣ For non-arrays: delegate to var_export() for correct PHP syntax
        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return var_export((string) $value, true);
            }

            return '/* Object of '.get_class($value).' */';
        }

        return var_export($value, true);
    }

}
