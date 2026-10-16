<?php

/*
| Every view() name used in the controllers must match a Blade file with
| EXACTLY the same upper/lower case. Windows ignores case, but Linux web
| hosting (for example Hostinger) does not, so a mismatch works on a
| laptop and crashes on the live server.
|
| When something is missing, the failure message explains exactly which
| folder or file could not be found and what IS in that folder.
*/

/**
 * Returns null when the view exists with the exact letter case,
 * otherwise a short explanation of what is missing.
 */
function explainMissingView(string $name): ?string
{
    $directory = resource_path('views');
    $segments = explode('.', $name);
    $file = array_pop($segments).'.blade.php';

    foreach ($segments as $segment) {
        $entries = is_dir($directory) ? scandir($directory) : [];

        if (! in_array($segment, $entries, true)) {
            $similar = array_values(array_filter(
                $entries,
                fn ($entry) => strcasecmp($entry, $segment) === 0
            ));

            return $similar
                ? "folder \"{$segment}\" has different letter case on disk: \"{$similar[0]}\" (inside {$directory})"
                : "folder \"{$segment}\" does not exist (inside {$directory})";
        }

        $directory .= DIRECTORY_SEPARATOR.$segment;
    }

    $entries = is_dir($directory) ? scandir($directory) : [];

    if (in_array($file, $entries, true)) {
        return null;
    }

    $similar = array_values(array_filter(
        $entries,
        fn ($entry) => strcasecmp($entry, $file) === 0
    ));

    return $similar
        ? "file \"{$file}\" has different letter case on disk: \"{$similar[0]}\" (inside {$directory})"
        : "file \"{$file}\" does not exist (inside {$directory})";
}

test('every view used by the application exists with the exact same letter case', function () {
    $missing = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(app_path()));

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $code = file_get_contents($file->getPathname());

        preg_match_all('/\b(?:view|loadView)\(\s*[\'"]([A-Za-z0-9_\-\.]+)[\'"]/', $code, $matches);

        foreach ($matches[1] as $viewName) {
            $problem = explainMissingView($viewName);

            if ($problem !== null) {
                $missing[] = $viewName.' (used in '.basename($file->getPathname()).'): '.$problem;
            }
        }
    }

    expect($missing)->toBe([]);
});
