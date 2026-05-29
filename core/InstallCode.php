<?php
/**
 * InstallCode — generates embed snippets for a widget loader URL.
 *
 * Produces 4 variants (matching the legacy close-window / smart-call panels):
 *   simple     — plain <script src> tag
 *   async      — non-blocking async injector (recommended)
 *   obfuscated — host + path + ".php" split into string fragments so the
 *                domain is not greppable in page source (anti-parser)
 *   gtm        — same as async, intended for a GTM Custom HTML tag
 *
 * Usage:
 *   $ic = new InstallCode($loaderUrl);   // full URL incl. ?key=...
 *   echo $ic->simple();
 *   echo $ic->obfuscated();
 */
class InstallCode
{
    private string $url;
    private string $proto;
    private string $host;
    private string $path;     // dir path, no filename
    private string $file;     // filename WITHOUT extension
    private string $ext;      // extension incl. dot, e.g. ".php"
    private string $query;    // ?key=...

    public function __construct(string $url)
    {
        $this->url   = $url;
        $this->proto = (parse_url($url, PHP_URL_SCHEME) ?: 'https') . '://';
        $this->host  = parse_url($url, PHP_URL_HOST) ?: 'localhost';
        $fullPath    = parse_url($url, PHP_URL_PATH) ?: '/';
        $this->path  = rtrim(dirname($fullPath), '/\\');
        $info        = pathinfo($fullPath);
        $this->file  = $info['filename'] ?? 'loader';
        $this->ext   = isset($info['extension']) ? '.' . $info['extension'] : '';
        $q           = parse_url($url, PHP_URL_QUERY);
        $this->query = $q ? '?' . $q : '';
    }

    /** Plain script tag. */
    public function simple(): string
    {
        return '<script src="' . $this->url . '" async></script>';
    }

    /** Async injector — does not block page load. */
    public function async(): string
    {
        return "<script>\n"
            . "(function(d,s,u){\n"
            . "  var el=d.createElement(s);el.async=1;el.src=u;\n"
            . "  d.head.appendChild(el);\n"
            . "})(document,'script','" . $this->url . "');\n"
            . "</script>";
    }

    /** GTM Custom HTML tag (identical to async). */
    public function gtm(): string
    {
        return $this->async();
    }

    /**
     * Obfuscated — host, path and extension split into concatenated fragments
     * so the domain can't be matched as a literal string in the HTML source.
     */
    public function obfuscated(): string
    {
        [$h1, $h2, $h3] = $this->splitThree($this->host);
        [$p1, $p2, $p3] = $this->splitThree($this->path);

        // Split extension ".php" → '.p'+'hp' (only when it's .php)
        $extExpr = $this->ext === '.php'
            ? "'.p'+'hp'"
            : "'" . $this->ext . "'";

        return "<script>\n"
            . "(function(){\n"
            . "var _h='" . $h1 . "'+'" . $h2 . "'+'" . $h3 . "';\n"
            . "var _p='" . $p1 . "'+'" . $p2 . "'+'" . $p3 . "';\n"
            . "var _e=" . $extExpr . ";\n"
            . "(function(d,s,u){\n"
            . "  var el=d.createElement(s);el.async=1;el.src=u;\n"
            . "  d.head.appendChild(el);\n"
            . "})(document,'script',\n"
            . "  '" . $this->proto . "'+_h+_p+'/" . $this->file . "'+_e+'" . $this->query . "');\n"
            . "})();\n"
            . "</script>";
    }

    /** Split a string into three roughly equal parts. */
    private function splitThree(string $s): array
    {
        $len = strlen($s);
        if ($len === 0) return ['', '', ''];
        $a = (int)round($len / 3);
        $b = (int)round($len * 2 / 3);
        return [substr($s, 0, $a), substr($s, $a, $b - $a), substr($s, $b)];
    }
}
