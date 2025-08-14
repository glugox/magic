<?php

namespace Glugox\Magic\Support;

class CodeGenerationHelper
{

    /**
     * Append a code block to a method in a file.
     *
     * @param string $filePath
     * @param string $methodName
     * @param array $lines
     * @param string|null $tag
     * @return bool
     */
    public static function appendCodeBlock(string $filePath, string $methodName, array $lines, ?string $tag): bool
    {
        $code = file_get_contents($filePath);
        $tag = $tag ?? 'default';
        $tag = 'Uno:' . $tag;

        if ($code === false) return false;

        $startMarker = "//region {$tag}";
        $endMarker = "//endregion";

        $indent = '        '; // 8 spaces
        $nl = "\n";
        $newLines = "";

        $k = 0;
        foreach ($lines as $line) {
            $lineTrimmed = trim($line);
            $newLines .= "$lineTrimmed";
            if ($k++ < count($lines) - 1) {
                $newLines .= $nl . $indent;
            }
        }

        $pattern = '/(public\s+function\s+' . preg_quote($methodName, '/') . '\s*\([^)]*\)\s*(?::\s*[^{\s]+)?\s*\{)(.*?)(^\s*\})/ms';

        if (preg_match($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
            $methodBody = $matches[2][0];
            $methodStart = $matches[1][1] + strlen($matches[1][0]);
            $methodEnd = $matches[3][1];

            // Search for existing region block inside method body
            $regionPattern = '/(\/\/region ' . preg_quote($tag, '/') . ')(.*?)(\/\/endregion)/s';

            if (preg_match($regionPattern, $methodBody, $regionMatches, PREG_OFFSET_CAPTURE)) {
                // region exists: insert before //endregion
                $regionStartPos = $regionMatches[0][1];
                $regionContent = $regionMatches[0][0];

                // Position of //endregion inside the region block
                $endregionPos = strpos($regionContent, $endMarker);

                // Check if there is any content between the start and end markers
                $regionHasContent = trim(substr($regionContent, strlen($startMarker), $endregionPos - strlen($startMarker))) !== '';

                $newContentBeforeLines = '';
                if ($regionHasContent) {
                    // If there is existing content, add a new line before inserting new lines
                    $newContentBeforeLines = $nl . $indent;
                }

                // Insert new lines before //endregion
                $updatedRegionContent = substr($regionContent, 0, $endregionPos) . $newContentBeforeLines  . $newLines . $nl . $indent . $endMarker;

                // Replace old region with updated one inside methodBody
                $methodBody = substr_replace($methodBody, $updatedRegionContent, $regionStartPos, strlen($regionContent));
            } else {
                // region does not exist, add whole region block at end of method body
                $regionBlock = "{$nl}{$indent}{$startMarker}$nl{$indent}{$newLines}{$nl}{$indent}{$endMarker}{$nl}";
                $methodBody .= $regionBlock;
            }

            // Rebuild whole code
            $newCode = substr($code, 0, $methodStart) . $methodBody . substr($code, $methodEnd);

            return file_put_contents($filePath, $newCode) !== false;
        }

        return false;
    }

    /**
     * Remove a region block from a method in a file.
     *
     * @param string $filePath
     * @param string|null $tag
     * @return bool
     */
    public static function removeRegion(string $filePath, ?string $tag = null): bool
    {
        $content = file_get_contents($filePath);
        if ($tag) {
            // Escape tag and match exactly that region
            $escapedTag = preg_quote($tag, '/');
            $pattern = '/^[ \t]*\/\/region\s+' . $escapedTag . '.*?^[ \t]*\/\/endregion\s*$/ms';
        } else {
            // Match *any* tag
            $pattern = '/^[ \t]*\/\/region\s+.*?^[ \t]*\/\/endregion\s*$/ms';
        }

        // Replace in content
        $content = preg_replace($pattern, '', $content);

        return file_put_contents($filePath, $content) !== false;
    }

}
