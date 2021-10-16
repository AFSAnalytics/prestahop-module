<?php

//  Misc Utils

class AFSATools
{
    /**
     * Output JS data
     *
     * @param array $arr data
     *
     * @return string js code
     */
    public static function renderJSData(array $arr)
    {
        $js = '';

        if (count($arr)) {
            foreach ($arr as $k => $v) {
                if (is_array($v) || is_string($v)) {
                    $js .= 'var ' . $k . '=' . json_encode($v, JSON_UNESCAPED_SLASHES) . ';';
                } elseif (is_string($v)) {
                    $js .= 'var ' . $k . '=' . static::jsEscape($v) . ';';
                } else {
                    $js .= 'var ' . $k . '="' . $v . '";';
                }
            }
        }

        return static::renderJSSCript($js);
    }

    public static function renderJSScript($js)
    {
        return empty($js) ?
            '' :
            "<script>\n$js\n</script>\n";
    }

    private static function renderDebugMessage($str, $data)
    {
        $d_str = ' ';
        if ($data) {
            $d_str .= is_array($data) ?
                json_encode($data, JSON_PRETTY_PRINT) :
                $data;
        }

        return $str . $d_str;
    }

    public static function log($str, $data = null)
    {
        if (!AFSAConfig::isDebug()) {
            return;
        }

        $msg = static::renderDebugMessage($str, $data);
        error_log($msg);

        return $msg;
    }

    public static function forcedLog($str, $data = null)
    {
        if (!AFSAConfig::isDebug()) {
            return;
        }

        $msg = static::renderDebugMessage($str, $data);
        error_log($msg);
        echo '<pre>' . $msg . '</pre>';
    }

    public static function jsEscape($string)
    {
        return str_replace("\n", '\n', str_replace('"', '\"', addcslashes(str_replace("\r", '', (string) $string), "\0..\37'\\")));
    }

    public static function normalizeString($str)
    {
        //return Tools::str2url($str);
        if (!is_string($str) || empty($str)) {
            return '';
        }

        $ret = trim(str_replace(['"', "'"], ' ', $str));

        if (function_exists('mb_strtolower')) {
            $ret = mb_strtolower($ret);
        }

        return $ret;
    }

    public static function buildURL($u, $extra_args)
    {
        if (empty($extra_args)) {
            return $u;
        }

        // Get current args in an asso array
        $args = [];
        $parts = parse_url($u);

        $path = empty($parts['path']) ? '' : $parts['path'];

        $base_url = empty($parts['scheme']) ?
            $path :
            $parts['scheme'] . '://' . $parts['host'] . $path;

        // Retrieve current args
        if (!empty($parts['query'])) {
            parse_str($parts['query'], $args);
        }

        // Add new args
        foreach ($extra_args as $k => $v) {
            $args[$k] = $v;
        }

        return $base_url . '?' . http_build_query($args);
    }

    public static function redirect($u)
    {
        Tools::redirect($u);
        exit();
    }

    public static function getAllHeaders()
    {
        try {
            if (function_exists('getallheaders')) {
                return getallheaders();
            }

            if (!empty($_SERVER)) {
                $ret = [];
                foreach ($_SERVER as $k => $v) {
                    if (substr($k, 0, 5) == 'HTTP_') {
                        $ret[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))))] = $v;
                    }
                }

                return $ret;
            }
        } catch (Exception $e) {
        }

        return [];
    }
}
