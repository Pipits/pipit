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




    /**
     * 
     */
    public static function get_form_js_error_messages($key, $errors) {
        $API = new PerchAPI(1.0, 'pipit');
        $Template = $API->get('Template');
        
        $template_path = Pipit_Util::get_form_key_parts($key, 'template');

        // remove /templates/ prefix from path
        $prefix = '/templates/';
        $prefix_alt = PerchUtil::file_path($prefix);

        if (substr($template_path, 0, strlen($prefix)) == $prefix) {
            $template_path = substr($template_path, strlen($prefix));
        } elseif (substr($template_path, 0, strlen($prefix_alt)) == $prefix_alt) {
            $template_path = substr($template_path, strlen($prefix_alt));
        }


        if( $Template->set($template_path, 'forms') == 404 ) {
            return $errors;
        } 


        
        $error_tags = $Template->find_all_tags('error');

        if(PerchUtil::count($error_tags)) {
            foreach($error_tags as $Tag) {

                if(!$Tag->is_set('for') || !$Tag->is_set('type')) continue;
                $for = $Tag->attributes['for'];
                $type = $Tag->attributes['type'];

                if(isset($errors[$for]) && $errors[$for]['type'] == $type) {
                    $response_attrs = $Tag->search_attributes_for('response-');
                    
                    foreach($response_attrs as $key => $val) {
                        $errors[$for][str_replace('response-', '', $key)] = $val;
                    }
                }

            }
        }
        
        
        return $errors;
    }




    /**
     * 
     */
    public static function get_form_key_parts($key, $part = false) {
        $out = [];
        $key       = base64_decode($key);
        $parts     = explode(':', $key);

        $out['id'] = $parts[0];
        $out['apps'] = $parts[1];
        $out['template']  = $parts[2];
        $out['timestamp'] = (isset($parts[3]) ? $parts[3] : false);

        if($part && isset($out[$part])) return $out[$part];
        return $out;
    }
}