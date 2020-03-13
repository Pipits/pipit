<?php

class Pipit_Util {
    
    /**
     * 
     */
    public static function template_exists($template_path) {
        $template_path = Pipit_Util::format_template_path($template_path, false);
        $template_file  = PerchUtil::file_path(PERCH_TEMPLATE_PATH.'/'.$template_path);
        if(!is_file($template_file)) return false;
        return true;
    }



    
    /**
     * 
     */
    public static function format_template_path($template_path, $include_dir = true) {
        if ($template_path && substr($template_path, -5)!=='.html') $template_path .= '.html';
        if($include_dir) $template_path = "/templates/$template_path";

        return $template_path;
    }




    /**
     * 
     */
    public static function is_json_content_type() {
        $content_type = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
        if (strpos( $content_type, "application/json" ) !== false) return true;
        return false;
    }




    /**
     * 
     */
    public static function get_formID_from_key($key) {
        $key = base64_decode($key);
        $parts = explode(':', $key);
        return isset($parts[0]) ? $parts[0] : '';
    }

}