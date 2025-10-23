<?php

namespace Glugox\Magic\Support;

use Glugox\Magic\Actions\Files\BackupOriginalFileAction;
use Illuminate\Support\Facades\Log;

class CodeGenerationHelper
{
    /**
     * Append a code block to a method in a file.
     */
    public function appendCodeBlock(string $filePath, string $methodName, array $lines, ?string $tag): bool
    {
        Log::channel('magic')->info("Appending code block to {$methodName} in {$filePath} with tag '{$tag}'");
        $code = file_get_contents($filePath);
        $tag = $tag ?? 'default';
        $tag = 'Uno:'.$tag;

        if ($code === false) {
            return false;
        }

        $startMarker = "//region {$tag}";
        $endMarker = '//endregion';

        $indent = '        '; // 8 spaces
        $nl = "\n";
        $newLines = '';

        $k = 0;
        foreach ($lines as $line) {
            $lineTrimmed = mb_trim($line);
            $newLines .= "$lineTrimmed";
            if ($k++ < count($lines) - 1) {
                $newLines .= $nl.$indent;
            }
        }

        $pattern = '/(public\s+function\s+'.preg_quote($methodName, '/').'\s*\([^)]*\)\s*(?::\s*[^{\s]+)?\s*\{)(.*?)(^\s*\})/ms';

        if (preg_match($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
            $methodBody = $matches[2][0];
            $methodStart = $matches[1][1] + mb_strlen($matches[1][0]);
            $methodEnd = $matches[3][1];

            // Search for existing region block inside method body
            $regionPattern = '/(\/\/region '.preg_quote($tag, '/').')(.*?)(\/\/endregion)/s';

            if (preg_match($regionPattern, $methodBody, $regionMatches, PREG_OFFSET_CAPTURE)) {
                // region exists: insert before //endregion
                $regionStartPos = $regionMatches[0][1];
                $regionContent = $regionMatches[0][0];

                // Position of //endregion inside the region block
                $endregionPos = mb_strpos($regionContent, $endMarker);

                // Check if there is any content between the start and end markers
                $regionHasContent = mb_trim(mb_substr($regionContent, mb_strlen($startMarker), $endregionPos - mb_strlen($startMarker))) !== '';

                $newContentBeforeLines = '';
                if ($regionHasContent) {
                    // If there is existing content, add a new line before inserting new lines
                    $newContentBeforeLines = $nl.$indent;
                }

                // Insert new lines before //endregion
                $updatedRegionContent = mb_substr($regionContent, 0, $endregionPos).$newContentBeforeLines.$newLines.$nl.$indent.$endMarker;

                // Replace old region with updated one inside methodBody
                $methodBody = substr_replace($methodBody, $updatedRegionContent, $regionStartPos, mb_strlen($regionContent));
            } else {
                // region does not exist, add whole region block at end of method body
                $regionBlock = "{$nl}{$indent}{$startMarker}$nl{$indent}{$newLines}{$nl}{$indent}{$endMarker}{$nl}";
                $methodBody .= $regionBlock;
            }

            // Rebuild whole code
            $newCode = mb_substr($code, 0, $methodStart).$methodBody.mb_substr($code, $methodEnd);

            app(BackupOriginalFileAction::class)($filePath);

            return file_put_contents($filePath, $newCode) !== false;
        }
        Log::channel('magic')->error("Method {$methodName} not found in {$filePath}");

        return false;
    }

    /**
     * Remove a region block from a method in a file.
     */
    public function removeRegion(string $filePath, ?string $tag = null): bool
    {
        $content = file_get_contents($filePath);
        if ($tag) {
            // Escape tag and match exactly that region
            $escapedTag = preg_quote($tag, '/');
            $pattern = '/^[ \t]*\/\/region\s+'.$escapedTag.'.*?^[ \t]*\/\/endregion\s*$/ms';
        } else {
            // Match *any* tag
            $pattern = '/^[ \t]*\/\/region\s+.*?^[ \t]*\/\/endregion\s*$/ms';
        }

        // Replace in content
        $content = preg_replace($pattern, '', $content);

        app(BackupOriginalFileAction::class)($filePath);

        return file_put_contents($filePath, $content) !== false;
    }
}
