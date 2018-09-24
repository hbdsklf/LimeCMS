<?php

/**
 * Load custom fonts for PDF generation
 *
 * @param mPdf $mPdf mPdf
 *
 * @return void
 */
function getCustomFonts($mPdf) {
    $fontStyles = array(
        'Bold' => 'B',
        'Regular' => 'R',
        'Italic' => 'I',
        'BoldItalic' => 'BI'
    );

    $dir = ltrim(
        \Cx\Core\Core\Controller\Cx::FOLDER_NAME_MEDIA,
        '/'
    ) . '/Pdf/ttfonts/';
    $fileNames = array_diff(scandir($dir), array('..', '.'));
    $newFonts = array();
    foreach ($fileNames as $file) {
        $splitFont = explode('-', $file);
        $fontName = $splitFont[0];
        $splitFontStyle = explode('.', $splitFont[1]);
        $fontStyle = $splitFontStyle[0];
        if (!isset($newFonts[strtolower($fontName)])) {
            $newFonts[strtolower($fontName)] = array();
        }

        if (isset($fontStyles[$fontStyle])) {
            $newFonts[strtolower($fontName)][$fontStyles[$fontStyle]] =
                $fontName . '-' . $fontStyle . '.ttf';
        }
    }
    $mPdf->fontdata += $newFonts;
}
getCustomFonts($this);
