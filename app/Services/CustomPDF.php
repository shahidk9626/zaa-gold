<?php

namespace App\Services;

use Barryvdh\DomPDF\PDF as BasePDF;

class CustomPDF extends BasePDF
{
    /**
     * Load a HTML string and inject Unicode-compatible styles globally.
     *
     * @param string $string
     * @param string|null $encoding
     * @return $this
     */
    public function loadHTML(string $string, ?string $encoding = null): self
    {
        // Globally inject DejaVu Sans styling to support Unicode rendering (₹) natively without breaking SVG structural nodes
        $unicodeStyle = '
        <style>
            body, table, tr, td, th, div, span, p, h1, h2, h3, h4, h5, h6, a, li, ul, ol, input, button, textarea {
                font-family: "DejaVu Sans", sans-serif !important;
            }
        </style>';

        if (strpos($string, '</head>') !== false) {
            $string = str_replace('</head>', $unicodeStyle . '</head>', $string);
        } else {
            $string = $unicodeStyle . $string;
        }

        return parent::loadHTML($string, $encoding);
    }
}
