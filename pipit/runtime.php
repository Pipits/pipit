<?php

    /**
     * Outputs a formatted print_r()
     */
    function pipit_r($var) {
        echo '<pre>' . print_r($var, 1) . '</pre>';
    }




    
    /**
     * Get an element from the $_GET array
     * 
     * @param string $str           element key $_GET[$str]
     * @param bool $return_array    returns the value as an array
     * @param mixed $default        default value to be returned if $_GET[$str] does not exist
     * 
     * @return string|array
     */
    function pipit_get($str, $return_array = false, $default = false) {
        
        if(isset($_GET[$str])) {
            if(is_array($_GET[$str])) {
                
                $result = array();
                foreach($_GET[$str] as $item) {
                    $result[] = rawurldecode($item);
                }
                return $result;

            } elseif($_GET[$str] != '') {

                $result = rawurldecode($_GET[$str]);
                if($return_array) {
                    return [$result];
                } else {
                    return $result;
                }
                 
            }
        }

        return $default;
    }





    /**
     * Check if user logged into Perch control panel
     * This is not part of Perch's public API
     */
    function pipit_perch_user_logged_in(){
        $Users = new PerchUsers;
        $CurrentUser = $Users->get_current_user();
        
        if (is_object($CurrentUser) && $CurrentUser->logged_in()) {
            return true;
        }

        return false;
    }





    /**
     * Cache bust CSS and JS
     * 
     * @param string $path   Path to an asset file
     * @param string $type   Type of version
     * 
     */
    function pipit_version($path, $type = 'param') {
        $full_path = dirname(PERCH_PATH) . $path;
        if (file_exists($full_path)) {
            $path_info = pathinfo($path);
            $filename = substr($path, 0, strrpos($path, '.'));
            

            switch($type) {
                case 'param':
                    echo $path . '?v=' . filemtime($full_path);
                    break;

                case 'name':
                    echo $filename . '.' . filemtime($full_path) . '.' . $path_info['extension'];
                    break;
            }
        }
    }





    /**
     * Renders a Perch template
     * 
     * @param string $template  Template path
     * @param array $data       Array containing the data to be rendered by the template
     * @param array $opts       Options array
     * @param bool $return      Set to true to have the rendered template returned instead of echoed
     * 
     */
    function pipit_template($template, $data, $opts = array(), $return = false) {
        $API  = new PerchAPI(1.0, 'pipit');
        $Template = $API->get('Template');

        $default_opts = [
            'namespace' => 'content',
        ];

        $opts = array_merge($default_opts, $opts);
        $Template->set($template, $opts['namespace']);


        // associate array rendered as a single item ['name' => 'John Silver'],
        // otherwise render as a group [['name' => 'John Silver'], ['name' => 'Jason Bourne']]
        if (!PerchUtil::is_assoc($data)) {

            if(isset($opts['paginate']) && isset($opts['count'])) {
                $Paging = $API->get('Paging');
                $Paging->set_per_page($opts['count']);
                $Paging->set_total(PerchUtil::count($data));
                
                if(isset($opts['pagination-var'])) {
                    $Paging->set_qs_param($opts['pagination-var']);
                }
            
                $paging_array = $Paging->to_array($opts);
                
                // add paging vars to each item in $data
                if (PerchUtil::count($data)) {
                    array_walk($data, function(&$item) use($paging_array) {
                        foreach($paging_array as $key=>$val) {
                            $item[$key] = $val;
                        }
                    });
                }

                // get the ($data) array elements for a given page
                $offset = $Paging->per_page() * ($Paging->current_page()-1);
                $data = array_slice($data, $offset, $Paging->per_page());

            } elseif(isset($opts['count'])) {
                // count limit with no pagination
                $data = array_slice($data, 0, $opts['count']);
            }

            $html = $Template->render_group($data);
        }else{
            $html = $Template->render($data);
        }
        

        // layout includes, forms, etc
        $html = $Template->apply_runtime_post_processing($html);
        

        if($return) return $html;
        echo $html;
        PerchUtil::flush_output();
    }