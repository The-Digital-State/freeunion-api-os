<?php

use Dompdf\Dompdf;
use FontLib\Font;

require_once 'vendor/autoload.php';

$dompdf = new Dompdf();

foreach (glob('resources/fonts/*') as $directory) {
    $family = explode('/', $directory)[2];
    $familyWOSpace = str_replace(' ', '', $family);

    $filename = [];
    foreach (['Regular', 'Bold', 'Italic', 'BoldItalic'] as $variant) {
        $filename[$variant] = file_exists("resources/fonts/$family/$familyWOSpace-$variant.ttf") ? "resources/fonts/$family/$familyWOSpace-$variant.ttf" : null;
    }

    if ($filename['Regular'] === null) {
        continue;
    }
    /** @noinspection PhpUnhandledExceptionInspection */
    install_font_family(
        $dompdf,
        $family,
        $filename['Regular'],
        $filename['Bold'],
        $filename['Italic'],
        $filename['BoldItalic']
    );
}

/**
 * Installs a new font family
 * This function maps a font-family name to a font.  It tries to locate the
 * bold, italic, and bold italic versions of the font as well.  Once the
 * files are located, ttf versions of the font are copied to the fonts
 * directory.  Changes to the font lookup table are saved to the cache.
 *
 * @param  Dompdf  $dompdf  dompdf main object
 * @param  string  $fontname  the font-family name
 * @param  string  $normal  the filename of the normal face font subtype
 * @param  string  $bold  the filename of the bold face font subtype
 * @param  string  $italic  the filename of the italic face font subtype
 * @param  string  $bold_italic  the filename of the bold italic face font subtype
 *
 * @throws Exception
 */
function install_font_family($dompdf, $fontname, $normal, $bold = null, $italic = null, $bold_italic = null)
{
    $fontMetrics = $dompdf->getFontMetrics();

    // Check if the base filename is readable
    if (! is_readable($normal)) {
        throw new Exception("Unable to read '$normal'.");
    }

    $dir = dirname($normal);
    $basename = basename($normal);
    $last_dot = strrpos($basename, '.');
    if ($last_dot !== false) {
        $file = substr($basename, 0, $last_dot);
        $ext = strtolower(substr($basename, $last_dot));
    } else {
        $file = $basename;
        $ext = '';
    }

    if (! in_array($ext, ['.ttf', '.otf'])) {
        throw new Exception("Unable to process fonts of type '$ext'.");
    }

    // Try $file_Bold.$ext etc.
    $path = "$dir/$file";

    $patterns = [
        'bold' => ['_Bold', 'b', 'B', 'bd', 'BD'],
        'italic' => ['_Italic', 'i', 'I'],
        'bold_italic' => ['_Bold_Italic', 'bi', 'BI', 'ib', 'IB'],
    ];

    foreach ($patterns as $type => $_patterns) {
        if (! isset($$type) || ! is_readable($$type)) {
            foreach ($_patterns as $_pattern) {
                if (is_readable("$path$_pattern$ext")) {
                    $$type = "$path$_pattern$ext";
                    break;
                }
            }

            if ($$type === null) {
                echo "Unable to find $type face file.\n";
            }
        }
    }

    $fonts = compact('normal', 'bold', 'italic', 'bold_italic');
    $entry = [];

    // Copy the files to the font directory.
    foreach ($fonts as $var => $src) {
        if ($src === null) {
            $entry[$var] = $dompdf->getOptions()->get('fontDir').'/'.mb_substr(basename($normal), 0, -4);

            continue;
        }

        // Verify that the fonts exist and are readable
        if (! is_readable($src)) {
            throw new Exception("Requested font '$src' is not readable");
        }

        $dest = $dompdf->getOptions()->get('fontDir').'/'.basename($src);

        if (! is_writable(dirname($dest))) {
            throw new Exception("Unable to write to destination '$dest'.");
        }

        echo "Copying $src to $dest...\n";

        if (! copy($src, $dest)) {
            throw new Exception("Unable to copy '$src' to '$dest'");
        }

        $entry_name = mb_substr($dest, 0, -4);

        echo "Generating Adobe Font Metrics for $entry_name...\n";

        $font_obj = Font::load($dest);
        if ($font_obj === null) {
            throw new Exception('Cannot load font');
        }
        $font_obj->saveAdobeFontMetrics("$entry_name.ufm");
        $font_obj->close();

        $entry[$var] = $entry_name;
    }

    // Store the fonts in the lookup table
    $fontMetrics->setFontFamily($fontname, $entry);

    // Save the changes
    $fontMetrics->saveFontFamilies();
}
