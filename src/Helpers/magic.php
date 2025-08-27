<?php

if (! function_exists('exportPhpValue')) {
    function exportPhpValue($value): string
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $k => $v) {
                $items[] = var_export($k, true).' => '.exportPhpValue($v);
            }

            return '['.implode(', ', $items).']';
        }

        return var_export($value, true);
    }
}
