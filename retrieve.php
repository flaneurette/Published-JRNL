<?php

$file = $_GET['entry'] ?? '';

if(!$file || !is_string($file)) {
 http_response_code(404);
 exit;
}

if(strlen($file) >= 100) {
 http_response_code(403);
 exit;
}

$file = str_replace(['../','./'],['',''],$file);

if (!preg_match('/^[a-zA-Z0-9\-]+$/D', $file)) {
 http_response_code(403);
 exit;
}

$filename = __DIR__ . '/' . $file . '.jrnl';

if (!is_file($filename)) {
    http_response_code(403);
    exit;
}

$contents = file_get_contents($filename);

if ($contents === false) {
    http_response_code(404);
    exit;
}

// Check if we have jrnl
if (!strstr($contents,'#jrnl')) {
 http_response_code(403);
 exit;
}

// If here, all things are good and we have a "jrnl" file.

function safe_url($u) {
    return preg_match('~^(https?://|mailto:|/|#)|^[\w./-]+$~i', $u) ? $u : '#';
}

function inline($text) {
    return preg_replace_callback(
        '/(?<!\S)#([apublished])\s+(\S+)/i',
        function ($m) {
            $word = $m[2];
            [$a, $b] = array_pad(explode('|', $word, 2), 2, '');

            switch (strtolower($m[1])) {
                case 'a': return '<a href="' . safe_url($a) . '">' . ($b !== '' ? $b : $a) . '</a>';
                case 'u': return "<ul>$word</ul>";
                case 'b': return "<strong>$word</strong>";
                case 'l': return "<li>$word</li>";
                case 'i': return '<img src="' . safe_url($a) . '" alt="' . $b . '">';
                case 's': return "<del>$word</del>";
                case 'e': return "<em>$word</em>";
                case 'd': return '<time datetime="' . $word . '">' . $word . '</time>';
                default:  return $m[0];
            }
        },
        $text
    );
}

$lines = preg_split('/\R/', $contents);
array_shift($lines); // remove the @jrnl line

$tags = [
    'h1' => 'h1', 'h2' => 'h2', 'h3' => 'h3',
    'h4' => 'h4', 'h5' => 'h5', 'h6' => 'h6',
    'p'  => 'p',
];

$markup = '';

foreach ($lines as $line) {
    if (preg_match('/^#(\w+)\s+(.*)$/', $line, $m)) {
        $tag  = strtolower($m[1]);
        $text = inline(htmlspecialchars($m[2]));

        if (isset($tags[$tag])) {
            $html = $tags[$tag];
            $markup .= "<$html>$text</$html>\n";
        }
    }
}

$markup = '';
$inList = false;

foreach ($lines as $line) {
    if (!preg_match('/^#(\w+)\s+(.*)$/', $line, $m)) continue;

    $tag  = strtolower($m[1]);
    $text = htmlspecialchars($m[2]);

    // any line that is not #l closes an open list
    if ($tag !== 'l' && $inList) {
        $markup .= "</ul>\n";
        $inList = false;
    }

    // headings and paragraphs
    if (isset($tags[$tag])) {
        $markup .= "<{$tags[$tag]}>" . inline($text) . "</{$tags[$tag]}>\n";
        continue;
    }

    // for #a and #i: "first | second" (spaces around | are fine here)
    [$a, $b] = array_pad(array_map('trim', explode('|', $text, 2)), 2, '');

    switch ($tag) {
        case 'a':
            $markup .= '<p><a href="' . safe_url($a) . '">' . ($b !== '' ? $b : $a) . "</a></p>\n";
            break;
        case 'i':
            $markup .= '<img src="' . safe_url($a) . '" alt="' . $b . "\">\n";
            break;
        case 'l':
            if (!$inList) {
                $markup .= "<ul>\n";
                $inList = true;
            }
            $markup .= '<li>' . inline($text) . "</li>\n";
            break;
        case 'b': $markup .= '<p><strong>' . $text . "</strong></p>\n"; break;
        case 'e': $markup .= '<p><em>' . $text . "</em></p>\n"; break;
        case 's': $markup .= '<p><del>' . $text . "</del></p>\n"; break;
        case 'd': $markup .= '<time datetime="' . $text . '">' . $text . "</time>\n"; break;
    }
}

if ($inList) $markup .= "</ul>\n";

header('Content-Type: text/html; charset=utf-8');

echo $markup;

?>
